<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase_Notes;
use App\Models\User;
use App\Models\Supplier;
use Carbon\Carbon;

class PurchaseNotesSeeder extends Seeder
{
    private $cantidadNotas = 100;

    public function run(): void
    {
        $admins = User::role(['Master', 'Administrador'])->get();
        $suppliers = Supplier::all();

        if ($admins->isEmpty()) {
            $this->command->error('No hay usuarios con roles Master o Administrador. Ejecuta primero UsersSeeder.');
            return;
        }

        $fechaInicio = Carbon::create(2026, 1, 1);
        $fechaFin    = Carbon::create(2026, 12, 31);
        $diasRango   = $fechaInicio->diffInDays($fechaFin);

        $totalNotas = $this->cantidadNotas;
        $notasCreadas = 0;
        $maxIntentos = 20000;
        $intentos = 0;

        while ($notasCreadas < $totalNotas && $intentos < $maxIntentos) {
            $intentos++;

            $fecha = $fechaInicio->copy()->addDays(rand(0, $diasRango))->format('Y-m-d');
            $hora = sprintf('%02d:%02d:%02d', rand(8, 17), rand(0, 59), rand(0, 59));
            $admin = $admins->random();
            $supplier = $suppliers->isNotEmpty() ? $suppliers->random()->id : null;

            $existe = Purchase_Notes::where('date', $fecha)
                ->where('hour', $hora)
                ->where('users_admin_id', $admin->id)
                ->where('suppliers_id', $supplier)
                ->exists();

            if (!$existe) {
                Purchase_Notes::create([
                    'hour'           => $hora,
                    'date'           => $fecha,
                    'total_price'    => rand(100, 5000) + round(rand(0, 99) / 100, 2),
                    'users_admin_id' => $admin->id,
                    'suppliers_id'   => $supplier,
                ]);
                $notasCreadas++;
                $intentos = 0;
            }
        }
    }
}
