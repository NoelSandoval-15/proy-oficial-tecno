<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly ?string $roleName = null,
        private readonly array $ids = [],
        private readonly ?string $search = null
    ) {
    }

    public function collection(): Collection
    {
        return User::query()
            ->with([
                'roles:id,name',
                'profile:id,ci,name,last_name,telephone,url_photo,users_id',
                'theme:id,name',
            ])
            ->when($this->roleName, fn ($query) => $query->role($this->roleName))
            ->when(count($this->ids) > 0, fn ($query) => $query->whereIn('id', $this->ids))
            ->when($this->search, function ($query) {
                $search = $this->search;

                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('email', 'ILIKE', "%{$search}%")
                        ->orWhereHas('profile', function ($profileQuery) use ($search) {
                            $profileQuery
                                ->whereRaw('CAST(ci AS TEXT) ILIKE ?', ["%{$search}%"])
                                ->orWhereRaw('CAST(telephone AS TEXT) ILIKE ?', ["%{$search}%"])
                                ->orWhere('last_name', 'ILIKE', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->get()
            ->map(function (User $user) {
                return [
                    'id' => $user->id,
                    'nombre' => $user->name,
                    'apellido' => $user->profile?->last_name ?? '',
                    'correo' => $user->email,
                    'ci' => $user->profile?->ci ?? '',
                    'telefono' => $user->profile?->telephone ?? '',
                    'rol' => $user->getRoleNames()->first() ?? 'Sin rol',
                    'tema' => $user->theme?->name ?? 'Sin tema',
                    'creado' => optional($user->created_at)->format('d/m/Y H:i'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Apellido',
            'Correo',
            'CI',
            'Teléfono',
            'Rol',
            'Tema',
            'Creado',
        ];
    }
}
