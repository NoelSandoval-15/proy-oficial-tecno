<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Insumos_Notes;
use App\Models\User;
use Carbon\Carbon;

class InsumosNotesSeeder extends Seeder
{

    private $cantidadNotas = 60;

    public function run(): void
    {
        $admins = User::role(['Master', 'Administrador'])->get();

        if ($admins->isEmpty()) {
            $this->command->error('No hay usuarios con roles Master o Administrador. Ejecuta primero UsersSeeder.');
            return;
        }

        $fechaInicio = Carbon::create(2026, 1, 1);
        $fechaFin    = Carbon::create(2026, 12, 31);
        $diasRango   = $fechaInicio->diffInDays($fechaFin);

        $totalNotas = $this->cantidadNotas;
        $notasCreadas = 0;
        $maxIntentos = 10000;
        $intentos = 0;

        while ($notasCreadas < $totalNotas && $intentos < $maxIntentos) {
            $intentos++;

            $fecha = $fechaInicio->copy()->addDays(rand(0, $diasRango))->format('Y-m-d');
            $hora = sprintf('%02d:%02d:%02d', rand(8, 17), rand(0, 59), rand(0, 59));
            $admin = $admins->random();

            $existe = Insumos_Notes::where('date', $fecha)
                ->where('hour', $hora)
                ->where('users_admin_id', $admin->id)
                ->exists();

            if (!$existe) {
                Insumos_Notes::create([
                    'hour'           => $hora,
                    'date'           => $fecha,
                    'users_admin_id' => $admin->id,
                ]);
                $notasCreadas++;
                $intentos = 0;
            }
        }
    }
}
