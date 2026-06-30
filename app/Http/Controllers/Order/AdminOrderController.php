<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Sales_Note;
use App\Models\Table;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;


class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', '10');

        $query = Sales_Note::query()
            ->with([
                'users_admin:id,name,email',
                'users_client:id,name,email',
                'users_client.profile:id,users_id,ci,name,last_name,telephone',
                'tables:id,name,amount,state',
                'details.product:id,name,price,url_photo,amount',
            ])
            ->latest();

        if ($request->filled('search')) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery
                    ->where('status', 'ILIKE', '%' . $request->search . '%')
                    ->orWhere('order_type', 'ILIKE', '%' . $request->search . '%')
                    ->orWhereHas('users_client', function ($clientQuery) use ($request) {
                        $clientQuery
                            ->where('name', 'ILIKE', '%' . $request->search . '%')
                            ->orWhere('email', 'ILIKE', '%' . $request->search . '%')
                            ->orWhereHas('profile', function ($profileQuery) use ($request) {
                                $profileQuery
                                    ->whereRaw('CAST(ci AS TEXT) ILIKE ?', ['%' . $request->search . '%'])
                                    ->orWhere('name', 'ILIKE', '%' . $request->search . '%')
                                    ->orWhere('last_name', 'ILIKE', '%' . $request->search . '%');
                            });
                    })
                    ->orWhereHas('tables', function ($tableQuery) use ($request) {
                        $tableQuery->where('name', 'ILIKE', '%' . $request->search . '%');
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('order_type')) {
            $query->where('order_type', $request->order_type);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $orders = $this->paginate($query, $perPage)
            ->withQueryString()
            ->through(fn($order) => $this->orderPayload($order));

        return Inertia::render('Orders/Admin/Index', [
            'orders' => $orders,
            'statuses' => Sales_Note::statuses(),
            'orderTypes' => Sales_Note::orderTypes(),
            'products' => $this->productsPayload(),
            'categories' => $this->categoriesPayload(),
            'tables' => $this->tablesPayload(),
            'filters' => [
                'search' => $request->input('search', ''),
                'status' => $request->input('status', ''),
                'order_type' => $request->input('order_type', ''),
                'date' => $request->input('date', ''),
                'per_page' => $perPage,
            ],
            'stats' => [
                'total' => Sales_Note::count(),
                'pending' => Sales_Note::where('status', Sales_Note::STATUS_PENDING)->count(),
                'preparing' => Sales_Note::where('status', Sales_Note::STATUS_PREPARING)->count(),
                'ready' => Sales_Note::where('status', Sales_Note::STATUS_READY)->count(),
                'today_total' => Sales_Note::whereDate('date', now()->toDateString())->sum('total_price'),
            ],
        ]);
    }

    public function searchClients(Request $request)
    {
        $search = trim($request->input('search', ''));

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $clients = User::role('Cliente')
            ->with('profile:id,users_id,ci,name,last_name,telephone')
            ->select('id', 'name', 'email')
            ->where(function ($query) use ($search) {
                $query->where('name', 'ILIKE', '%' . $search . '%')
                    ->orWhere('email', 'ILIKE', '%' . $search . '%')
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery
                            ->whereRaw('CAST(ci AS TEXT) ILIKE ?', ['%' . $search . '%'])
                            ->orWhere('name', 'ILIKE', '%' . $search . '%')
                            ->orWhere('last_name', 'ILIKE', '%' . $search . '%');
                    });
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'ci' => $client->profile?->ci,
                'profile_name' => $client->profile?->name,
                'last_name' => $client->profile?->last_name,
                'telephone' => $client->profile?->telephone,
                'label' => trim(($client->profile?->name ?: $client->name) . ' ' . ($client->profile?->last_name ?: '')),
            ])
            ->values();

        return response()->json($clients);
    }

    public function store(Request $request, OrderService $orderService)
    {
        $data = $request->validate($this->rules(), $this->messages());

        if ($data['order_type'] === Sales_Note::TYPE_TABLE && empty($data['tables_id'])) {
            return back()->withErrors([
                'tables_id' => 'Debe seleccionar una mesa para pedidos en mesa.',
            ])->withInput();
        }

        if ($data['order_type'] !== Sales_Note::TYPE_TABLE) {
            $data['tables_id'] = null;
        }

        $data['users_admin_id'] = auth()->id();

        $orderService->createOrder($data);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido registrado correctamente.');
    }

    public function update(Request $request, Sales_Note $order, OrderService $orderService)
    {
        if ($order->status === Sales_Note::STATUS_CANCELLED || $order->status === Sales_Note::STATUS_PAID) {
            return back()->with('error', 'No se puede editar un pedido cancelado o pagado.');
        }

        $data = $request->validate($this->rules(), $this->messages());

        if ($data['order_type'] === Sales_Note::TYPE_TABLE && empty($data['tables_id'])) {
            return back()->withErrors([
                'tables_id' => 'Debe seleccionar una mesa para pedidos en mesa.',
            ])->withInput();
        }

        if ($data['order_type'] !== Sales_Note::TYPE_TABLE) {
            $data['tables_id'] = null;
        }

        $data['users_admin_id'] = auth()->id();

        $orderService->updateOrder($order, $data);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido actualizado correctamente.');
    }

    public function changeStatus(Request $request, Sales_Note $order)
    {
        $request->validate([
            'status' => ['required', Rule::in(Sales_Note::statuses())],
        ]);

        $order->update([
            'status' => $request->status,
            'users_admin_id' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Estado del pedido actualizado correctamente.');
    }

    public function cancel(Sales_Note $order, OrderService $orderService)
    {
        if ($order->status === Sales_Note::STATUS_PAID) {
            return back()->with('error', 'No se puede cancelar un pedido pagado.');
        }

        $orderService->cancelOrder($order);

        return redirect()
            ->route('admin.orders.index')
            ->with('success', 'Pedido cancelado correctamente.');
    }

    private function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Sales_Note::statuses())],
            'order_type' => ['required', Rule::in(Sales_Note::orderTypes())],
            'users_client_id' => ['nullable', 'exists:users,id'],
            'tables_id' => ['nullable', 'exists:tables,id'],
            'reservations_id' => ['nullable', 'exists:reservations,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.products_id' => ['required', 'exists:products,id'],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ];
    }

    private function messages(): array
    {
        return [
            'status.required' => 'Debe seleccionar el estado del pedido.',
            'order_type.required' => 'Debe seleccionar el tipo de pedido.',
            'items.required' => 'Debe seleccionar al menos un producto.',
            'items.min' => 'Debe seleccionar al menos un producto.',
            'items.*.products_id.required' => 'Debe seleccionar un producto.',
            'items.*.amount.required' => 'Debe ingresar la cantidad.',
            'items.*.amount.min' => 'La cantidad debe ser mayor a 0.',
        ];
    }

    private function orderPayload(Sales_Note $order): array
    {
        return [
            'id' => $order->id,
            'date' => optional($order->date)->format('Y-m-d'),
            'date_formatted' => optional($order->date)->format('d/m/Y'),
            'hour' => substr($order->hour, 0, 5),
            'total_price' => $order->total_price,
            'status' => $order->status,
            'order_type' => $order->order_type,
            'users_admin_id' => $order->users_admin_id,
            'users_client_id' => $order->users_client_id,
            'tables_id' => $order->tables_id,
            'reservations_id' => $order->reservations_id,
            'admin' => $order->users_admin,
            'client' => $order->users_client ? [
                'id' => $order->users_client->id,
                'name' => $order->users_client->name,
                'email' => $order->users_client->email,
                'ci' => $order->users_client->profile?->ci,
                'profile_name' => $order->users_client->profile?->name,
                'last_name' => $order->users_client->profile?->last_name,
                'telephone' => $order->users_client->profile?->telephone,
                'label' => trim(($order->users_client->profile?->name ?: $order->users_client->name) . ' ' . ($order->users_client->profile?->last_name ?: '')),
            ] : null,
            'table' => $order->tables,
            'details' => $order->details->map(fn($detail) => [
                'id' => $detail->id,
                'products_id' => $detail->products_id,
                'amount' => $detail->amount,
                'price_sale' => $detail->price_sale,
                'product' => $detail->product,
            ])->values(),
        ];
    }

    private function productsPayload()
    {
        return Product::query()
            ->select('id', 'name', 'price', 'url_photo', 'amount', 'sub_categories_id')
            ->with('subCategorie:id,name,categories_id')
            ->where('amount', '>', 0)
            ->orderBy('name')
            ->get();
    }

    private function tablesPayload()
    {
        return Table::query()
            ->select('id', 'name', 'amount', 'state')
            ->where(function ($query) {
                $query->whereNull('state')
                    ->orWhere('state', Table::STATE_AVAILABLE);
            })
            ->orderBy('name')
            ->get();
    }

    private function paginate($query, string $perPage)
    {
        if ($perPage === 'all') {
            return $query->paginate(max($query->count(), 1));
        }

        return $query->paginate((int) $perPage);
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
