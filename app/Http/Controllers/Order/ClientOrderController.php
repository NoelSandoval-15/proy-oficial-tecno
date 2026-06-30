<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales_Note;
use App\Models\Table;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ClientOrderController extends Controller
{
    public function index()
    {
        $orders = Sales_Note::query()
            ->with([
                'tables:id,name,amount,state',
                'users_admin:id,name,email',
                'details.product:id,name,price,url_photo,amount',
            ])
            ->where('users_client_id', auth()->id())
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn($order) => [
                'id' => $order->id,
                'date' => optional($order->date)->format('Y-m-d'),
                'date_formatted' => optional($order->date)->format('d/m/Y'),
                'hour' => substr($order->hour, 0, 5),
                'total_price' => $order->total_price,
                'status' => $order->status,
                'order_type' => $order->order_type,
                'table' => $order->tables,
                'admin' => $order->users_admin,
                'details' => $order->details->map(fn($detail) => [
                    'id' => $detail->id,
                    'products_id' => $detail->products_id,
                    'amount' => $detail->amount,
                    'price_sale' => $detail->price_sale,
                    'product' => $detail->product,
                ])->values(),
            ]);

        return Inertia::render('Orders/Client/Index', [
            'orders' => $orders,
            'products' => Product::query()
                ->select('id', 'name', 'price', 'url_photo', 'amount', 'sub_categories_id')
                ->with('subCategorie:id,name,categories_id')
                ->where('amount', '>', 0)
                ->orderBy('name')
                ->get(),
            'categories' => $this->categoriesPayload(),
            'tables' => Table::query()
                ->select('id', 'name', 'amount', 'state')
                ->where(function ($query) {
                    $query->whereNull('state')
                        ->orWhere('state', Table::STATE_AVAILABLE);
                })
                ->orderBy('name')
                ->get(),
            'orderTypes' => [
                Sales_Note::TYPE_TABLE,
                Sales_Note::TYPE_TAKEAWAY,
            ],
        ]);
    }

    public function store(Request $request, OrderService $orderService)
    {
        $data = $request->validate([
            'order_type' => ['required', Rule::in([Sales_Note::TYPE_TABLE, Sales_Note::TYPE_TAKEAWAY])],
            'tables_id' => ['nullable', 'exists:tables,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.products_id' => ['required', 'exists:products,id'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ], [
            'order_type.required' => 'Debe seleccionar si el pedido es en mesa o para llevar.',
            'tables_id.required' => 'Debe seleccionar la mesa donde se encuentra.',
            'items.required' => 'Debe seleccionar al menos un producto.',
            'items.min' => 'Debe seleccionar al menos un producto.',
        ]);

        if ($data['order_type'] === Sales_Note::TYPE_TABLE && empty($data['tables_id'])) {
            return back()->withErrors([
                'tables_id' => 'Debe seleccionar la mesa donde se encuentra.',
            ])->withInput();
        }

        if ($data['order_type'] === Sales_Note::TYPE_TAKEAWAY) {
            $data['tables_id'] = null;
        }

        $data['status'] = Sales_Note::STATUS_PENDING;
        $data['users_admin_id'] = null;
        $data['users_client_id'] = auth()->id();
        $data['reservations_id'] = null;

        $orderService->createOrder($data);

        return redirect()
            ->route('client.orders.index')
            ->with('success', 'Pedido enviado correctamente. Está pendiente de atención.');
    }

    public function cancel(Sales_Note $order, OrderService $orderService)
    {
        if ($order->users_client_id !== auth()->id()) {
            abort(403);
        }

        if (!in_array($order->status, [
            Sales_Note::STATUS_PENDING,
        ])) {
            return back()->with('error', 'Solo puedes cancelar pedidos pendientes.');
        }

        $orderService->cancelOrder($order);

        return redirect()
            ->route('client.orders.index')
            ->with('success', 'Pedido cancelado correctamente.');
    }

    private function categoriesPayload()
    {
        $subCategories = DB::table('sub_categories')
            ->select('id', 'name', 'categories_id')
            ->orderBy('name')
            ->get()
            ->groupBy('categories_id');

        return DB::table('categories')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(function ($category) use ($subCategories) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'sub_categories' => ($subCategories[$category->id] ?? collect())
                        ->map(fn($subCategory) => [
                            'id' => $subCategory->id,
                            'name' => $subCategory->name,
                            'categories_id' => $subCategory->categories_id,
                        ])
                        ->values(),
                ];
            })
            ->values();
    }
}
