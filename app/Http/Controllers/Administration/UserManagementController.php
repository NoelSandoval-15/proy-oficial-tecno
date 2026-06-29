<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Insumo;
use App\Models\Product;
use App\Models\Profile;
use App\Models\Reservation;
use App\Models\Sales_Note;
use App\Models\Table;
use App\Models\Theme;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserManagementController extends Controller
{
    public function employees(Request $request): Response
    {
        return $this->indexByRole(
            request: $request,
            roleName: 'Mesero',
            title: 'Empleados',
            subtitle: 'Gestiona los empleados de Churrasquería Roberto.',
            createLabel: 'Crear empleado',
            storeRoute: 'administracion.empleados.store'
        );
    }

    public function clients(Request $request): Response
    {
        return $this->indexByRole(
            request: $request,
            roleName: 'Cliente',
            title: 'Clientes',
            subtitle: 'Gestiona los clientes registrados en el sistema.',
            createLabel: 'Crear cliente',
            storeRoute: 'administracion.clientes.store'
        );
    }

    public function administrators(Request $request): Response
    {
        return $this->indexByRole(
            request: $request,
            roleName: 'Administrador',
            title: 'Administradores',
            subtitle: 'Gestiona usuarios administradores del negocio.',
            createLabel: 'Crear administrador',
            storeRoute: 'administracion.administradores.store'
        );
    }

    public function storeEmployee(Request $request): RedirectResponse
    {
        return $this->storeUserWithRole($request, 'Mesero');
    }

    public function storeClient(Request $request): RedirectResponse
    {
        return $this->storeUserWithRole($request, 'Cliente');
    }

    public function storeAdministrator(Request $request): RedirectResponse
    {
        return $this->storeUserWithRole($request, 'Administrador');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $profileId = $user->profile?->id;
        $roleName = $user->getRoleNames()->first() ?? 'Usuario';

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'integer', Rule::unique('profiles', 'ci')->ignore($profileId)],
            'telephone' => ['nullable', 'integer'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg', 'max:4096'],
        ], $this->validationMessages());

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            ...($request->filled('password')
                ? ['password' => Hash::make($validated['password'])]
                : []),
        ]);

        $profileData = [
            'ci' => $validated['ci'],
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'telephone' => $validated['telephone'] ?? null,
        ];

        if ($request->hasFile('photo')) {
            $this->deleteOldPublicPhoto($user->profile?->url_photo);

            $profileData['url_photo'] = $this->storeProfilePhoto(
                request: $request,
                roleName: $roleName,
                name: $validated['name'],
                lastName: $validated['last_name']
            );
        }

        Profile::updateOrCreate(
            ['users_id' => $user->id],
            $profileData
        );

        return back()->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->withErrors([
                'user' => 'No puedes eliminar tu propia cuenta desde esta vista.',
            ]);
        }

        $this->deleteOldPublicPhoto($user->profile?->url_photo);

        $user->delete();

        return back()->with('success', 'Usuario eliminado correctamente.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:users,id'],
        ], [
            'ids.required' => 'Debes seleccionar al menos un usuario.',
            'ids.array' => 'La selección de usuarios no es válida.',
            'ids.*.integer' => 'Uno de los usuarios seleccionados no es válido.',
            'ids.*.exists' => 'Uno de los usuarios seleccionados no existe.',
        ]);

        $ids = collect($validated['ids'])
            ->filter(fn ($id) => (int) $id !== auth()->id())
            ->values();

        if ($ids->isEmpty()) {
            return back()->withErrors([
                'ids' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        User::query()
            ->whereIn('id', $ids)
            ->with('profile')
            ->get()
            ->each(function (User $user) {
                $this->deleteOldPublicPhoto($user->profile?->url_photo);
                $user->delete();
            });

        return back()->with('success', 'Usuarios seleccionados eliminados correctamente.');
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $roleName = $request->get('role');
        $ids = $this->parseIds($request);
        $search = trim($request->get('search', ''));

        $users = $this->usersQueryForExport($roleName, $ids, $search ?: null)
            ->get()
            ->map(fn (User $user) => $this->mapUser($user))
            ->values();

        $fileName = 'usuarios-churrasqueria-roberto.csv';

        return response()->streamDownload(function () use ($users) {
            echo "\xEF\xBB\xBF";

            $output = fopen('php://output', 'w');

            fputcsv($output, [
                'ID',
                'Nombre completo',
                'Correo electrónico',
                'CI',
                'Teléfono',
                'Rol',
            ], ';');

            foreach ($users as $user) {
                $fullName = trim(
                    ($user['profile']['name'] ?? '') . ' ' . ($user['profile']['last_name'] ?? '')
                );

                if ($fullName === '') {
                    $fullName = $user['name'];
                }

                fputcsv($output, [
                    $user['id'],
                    $fullName,
                    $user['email'],
                    $user['profile']['ci'] ?? '',
                    $user['profile']['telephone'] ?? '',
                    $user['roles'][0] ?? 'Sin rol',
                ], ';');
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $roleName = $request->get('role');
        $ids = $this->parseIds($request);
        $search = trim($request->get('search', ''));

        $users = $this->usersQueryForExport($roleName, $ids, $search ?: null)
            ->get()
            ->map(fn (User $user) => $this->mapUser($user))
            ->values();

        $pdf = Pdf::loadView('pdf.users', [
            'users' => $users,
            'title' => 'Reporte de usuarios',
        ])->setPaper('letter', 'landscape');

        return $pdf->download('usuarios-churrasqueria-roberto.pdf');
    }

    public function search(Request $request): Response
    {
        $search = trim($request->get('search', ''));

        if ($search === '') {
            $latest = User::query()
                ->with($this->userRelations())
                ->latest()
                ->take(5)
                ->get();

            $samples = collect(['Mesero', 'Cliente', 'Administrador'])
                ->flatMap(function ($roleName) {
                    return User::role($roleName)
                        ->with($this->userRelations())
                        ->latest()
                        ->take(2)
                        ->get();
                });

            $users = $latest
                ->merge($samples)
                ->unique('id')
                ->take(20)
                ->map(fn (User $user) => $this->mapUser($user))
                ->values();
        } else {
            $users = User::query()
                ->with($this->userRelations())
                ->where(fn ($query) => $this->applyUserSearch($query, $search))
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn (User $user) => $this->mapUser($user));
        }

        $selectedUser = null;
        $stats = null;

        if ($request->filled('user')) {
            $selectedUserModel = User::query()
                ->with($this->userRelations())
                ->find($request->integer('user'));

            if ($selectedUserModel) {
                $selectedUser = $this->mapUser($selectedUserModel);
                $stats = $this->buildUserStats($selectedUserModel);
            }
        }

        return Inertia::render('Administration/SearchUser', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    private function indexByRole(
        Request $request,
        string $roleName,
        string $title,
        string $subtitle,
        string $createLabel,
        string $storeRoute
    ): Response {
        $search = trim($request->get('search', ''));
        $perPage = $request->get('per_page', '10');

        $allowedPerPage = ['10', '20', '50', '100', 'all'];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = '10';
        }

        $query = User::role($roleName)
            ->with($this->userRelations())
            ->when($search, fn ($query) => $query->where(fn ($subQuery) => $this->applyUserSearch($subQuery, $search)))
            ->latest();

        if ($perPage === 'all') {
            $collection = $query->get();

            $users = [
                'data' => $collection
                    ->map(fn (User $user) => $this->mapUser($user))
                    ->values(),
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 'all',
                    'total' => $collection->count(),
                    'from' => $collection->count() > 0 ? 1 : 0,
                    'to' => $collection->count(),
                ],
            ];
        } else {
            $paginated = $query
                ->paginate((int) $perPage)
                ->withQueryString();

            $users = [
                'data' => $paginated->getCollection()
                    ->map(fn (User $user) => $this->mapUser($user))
                    ->values(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],
            ];
        }

        return Inertia::render('Administration/UserList', [
            'title' => $title,
            'subtitle' => $subtitle,
            'roleName' => $roleName,
            'createLabel' => $createLabel,
            'storeRoute' => $storeRoute,
            'users' => $users,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
            ],
            'stats' => $this->buildListStats($roleName),
        ]);
    }

    private function storeUserWithRole(Request $request, string $roleName): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'ci' => ['required', 'integer', 'unique:profiles,ci'],
            'telephone' => ['nullable', 'integer'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg', 'max:4096'],
        ], $this->validationMessages());

        $themeName = match ($roleName) {
            'Administrador', 'Mesero' => 'Administrador',
            'Cliente' => 'Adultos',
            default => 'Adultos',
        };

        $theme = Theme::query()
            ->where('name', $themeName)
            ->first();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'themes_id' => $theme?->id ?? 1,
        ]);

        $user->assignRole($roleName);

        $photoUrl = null;

        if ($request->hasFile('photo')) {
            $photoUrl = $this->storeProfilePhoto(
                request: $request,
                roleName: $roleName,
                name: $validated['name'],
                lastName: $validated['last_name']
            );
        }

        Profile::create([
            'ci' => $validated['ci'],
            'name' => $validated['name'],
            'last_name' => $validated['last_name'],
            'telephone' => $validated['telephone'] ?? null,
            'url_photo' => $photoUrl,
            'users_id' => $user->id,
        ]);

        return back()->with('success', 'Usuario creado correctamente.');
    }

    private function userRelations(): array
    {
        return [
            'roles:id,name',
            'profile:id,ci,name,last_name,telephone,url_photo,users_id',
            'theme:id,name',
        ];
    }

    private function applyUserSearch($query, string $search): void
    {
        $query
            ->where('name', 'ILIKE', "%{$search}%")
            ->orWhere('email', 'ILIKE', "%{$search}%")
            ->orWhereHas('profile', function ($profileQuery) use ($search) {
                $profileQuery
                    ->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('last_name', 'ILIKE', "%{$search}%")
                    ->orWhereRaw('CAST(ci AS TEXT) ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('CAST(telephone AS TEXT) ILIKE ?', ["%{$search}%"]);
            });
    }

    private function usersQueryForExport(?string $roleName, array $ids, ?string $search)
    {
        return User::query()
            ->with($this->userRelations())
            ->when($roleName, fn ($query) => $query->role($roleName))
            ->when(count($ids) > 0, fn ($query) => $query->whereIn('id', $ids))
            ->when($search, fn ($query) => $query->where(fn ($subQuery) => $this->applyUserSearch($subQuery, $search)))
            ->latest();
    }

    private function parseIds(Request $request): array
    {
        $ids = $request->get('ids');

        if (! $ids) {
            return [];
        }

        if (is_array($ids)) {
            return array_map('intval', $ids);
        }

        return collect(explode(',', $ids))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function mapUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'theme' => $user->theme?->name,
            'roles' => $user->getRoleNames()->values(),
            'profile' => $user->profile ? [
                'ci' => $user->profile->ci,
                'name' => $user->profile->name,
                'last_name' => $user->profile->last_name,
                'telephone' => $user->profile->telephone,
                'url_photo' => $user->profile->url_photo,
            ] : null,
            'created_at' => optional($user->created_at)->format('d/m/Y H:i'),
        ];
    }

    private function buildListStats(string $roleName): array
    {
        return [
            'total' => (int) User::role($roleName)->count(),

            'with_profile' => (int) User::role($roleName)
                ->whereHas('profile')
                ->count(),

            'created_this_month' => (int) User::role($roleName)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];
    }

    private function buildUserStats(User $user): array
    {
        $role = $user->getRoleNames()->first();

        if ($role === 'Mesero') {
            $salesQuery = Sales_Note::query()
                ->where('users_admin_id', $user->id);

            return [
                'type' => 'employee',
                'title' => 'Estadísticas del empleado',
                'cards' => [
                    [
                        'label' => 'Ventas realizadas',
                        'value' => (string) $salesQuery->count(),
                    ],
                    [
                        'label' => 'Total vendido',
                        'value' => 'Bs. ' . number_format((float) $salesQuery->sum('total_price'), 2),
                    ],
                    [
                        'label' => 'Promedio por venta',
                        'value' => 'Bs. ' . number_format((float) $salesQuery->avg('total_price'), 2),
                    ],
                ],
                'recent' => Sales_Note::query()
                    ->where('users_admin_id', $user->id)
                    ->latest('date')
                    ->latest('hour')
                    ->take(5)
                    ->get(['id', 'date', 'hour', 'total_price', 'status', 'tables_id'])
                    ->map(fn ($sale) => [
                        'id' => $sale->id,
                        'date' => $sale->date,
                        'hour' => $sale->hour,
                        'total' => 'Bs. ' . number_format((float) $sale->total_price, 2),
                        'status' => $sale->status,
                        'description' => 'Mesa #' . $sale->tables_id,
                    ]),
            ];
        }

        if ($role === 'Cliente') {
            $salesQuery = Sales_Note::query()
                ->where('users_client_id', $user->id);

            $reservationQuery = Reservation::query()
                ->where('users_cliente_id', $user->id);

            return [
                'type' => 'client',
                'title' => 'Estadísticas del cliente',
                'cards' => [
                    [
                        'label' => 'Reservas realizadas',
                        'value' => (string) $reservationQuery->count(),
                    ],
                    [
                        'label' => 'Compras realizadas',
                        'value' => (string) $salesQuery->count(),
                    ],
                    [
                        'label' => 'Total consumido',
                        'value' => 'Bs. ' . number_format((float) $salesQuery->sum('total_price'), 2),
                    ],
                ],
                'recent' => $salesQuery
                    ->latest('date')
                    ->latest('hour')
                    ->take(5)
                    ->get(['id', 'date', 'hour', 'total_price', 'status', 'tables_id'])
                    ->map(fn ($sale) => [
                        'id' => $sale->id,
                        'date' => $sale->date,
                        'hour' => $sale->hour,
                        'total' => 'Bs. ' . number_format((float) $sale->total_price, 2),
                        'status' => $sale->status,
                        'description' => 'Compra registrada',
                    ]),
            ];
        }

        if (! in_array($role, ['Administrador', 'Master'], true)) {
            return [
                'type' => 'unassigned',
                'title' => 'Usuario sin rol asignado',
                'cards' => [
                    [
                        'label' => 'Rol',
                        'value' => 'Sin rol',
                    ],
                    [
                        'label' => 'Tema',
                        'value' => $user->theme?->name ?? 'Sin tema',
                    ],
                    [
                        'label' => 'Cuenta creada',
                        'value' => optional($user->created_at)->format('d/m/Y') ?? 'Sin fecha',
                    ],
                ],
                'recent' => [],
            ];
        }

        return [
            'type' => 'admin',
            'title' => 'Resumen del negocio',
            'cards' => [
                [
                    'label' => 'Ganancia total',
                    'value' => 'Bs. ' . number_format((float) Sales_Note::sum('total_price'), 2),
                ],
                [
                    'label' => 'Ventas totales',
                    'value' => (string) Sales_Note::count(),
                ],
                [
                    'label' => 'Reservas totales',
                    'value' => (string) Reservation::count(),
                ],
                [
                    'label' => 'Productos',
                    'value' => (string) Product::count(),
                ],
                [
                    'label' => 'Mesas',
                    'value' => (string) Table::count(),
                ],
                [
                    'label' => 'Valor de insumos',
                    'value' => 'Bs. ' . number_format((float) Insumo::query()->sum(DB::raw('amount * price')), 2),
                ],
            ],
            'recent' => Sales_Note::query()
                ->latest('date')
                ->latest('hour')
                ->take(5)
                ->get(['id', 'date', 'hour', 'total_price', 'status', 'tables_id'])
                ->map(fn ($sale) => [
                    'id' => $sale->id,
                    'date' => $sale->date,
                    'hour' => $sale->hour,
                    'total' => 'Bs. ' . number_format((float) $sale->total_price, 2),
                    'status' => $sale->status,
                    'description' => 'Venta del negocio',
                ]),
        ];
    }

    private function storeProfilePhoto(Request $request, string $roleName, string $name, string $lastName): string
    {
        $folder = 'administracion/' . Str::slug($roleName);

        $fileNameBase = Str::slug($name . $lastName, '');
        $fileName = ($fileNameBase ?: 'perfil') . '.jpg';

        $path = $request->file('photo')->storeAs($folder, $fileName, 'public');

        return Storage::url($path);
    }

    private function deleteOldPublicPhoto(?string $url): void
    {
        if (! $url || ! str_starts_with($url, '/storage/')) {
            return;
        }

        $path = str_replace('/storage/', '', $url);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function validationMessages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe superar los 255 caracteres.',

            'last_name.required' => 'El apellido es obligatorio.',
            'last_name.string' => 'El apellido debe ser texto.',
            'last_name.max' => 'El apellido no debe superar los 255 caracteres.',

            'ci.required' => 'El CI es obligatorio.',
            'ci.integer' => 'El CI debe ser numérico.',
            'ci.unique' => 'Este CI ya está registrado.',

            'telephone.integer' => 'El teléfono debe ser numérico.',

            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no debe superar los 255 caracteres.',
            'email.unique' => 'Este correo electrónico ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.string' => 'La contraseña debe ser texto.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',

            'photo.image' => 'La foto debe ser una imagen.',
            'photo.mimes' => 'La foto debe estar en formato JPG o JPEG.',
            'photo.max' => 'La foto no debe superar los 4MB.',
        ];
    }
}
