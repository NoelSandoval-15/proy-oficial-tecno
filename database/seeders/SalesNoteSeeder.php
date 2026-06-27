<?php

namespace Database\Seeders;

use App\Models\Reservation;
use App\Models\Sales_Note;
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalesNoteSeeder extends Seeder
{
    public function run(): void
    {
        $admins = User::role(['Administrador', 'Mesero'])->get();
        $allClients = User::role('Cliente')->get();

        if ($admins->isEmpty() || $allClients->isEmpty()) {
            $this->command->error('Faltan admins/meseros o clientes. Ejecuta primero UsersSeeder.');
            return;
        }

        $mesas = Table::all();
        $reservas = Reservation::all();

        if ($reservas->count() < 12) {
            $this->command->warn('Hay menos de 12 reservas. Se usarán todas las disponibles.');
        }

        $fechaInicio = Carbon::create(2026, 1, 1);
        $fechaFin = Carbon::create(2026, 12, 31);
        $diasRango = $fechaInicio->diffInDays($fechaFin);

        $totalNotas = rand(30, 60);
        $notasCreadas = 0;

        $reservasUsadasIds = [];
        $mesasOcupadas = [];
        $clientesConReservaHoy = [];

        while ($notasCreadas < $totalNotas) {
            $fecha = $fechaInicio->copy()->addDays(rand(0, $diasRango))->format('Y-m-d');
            $hora = sprintf('%02d:%02d:%02d', rand(12, 23), rand(0, 59), rand(0, 59));

            $admin = $admins->random();

            if ($notasCreadas < 12) {
                $reservasDisponibles = $reservas->reject(fn($r) => in_array($r->id, $reservasUsadasIds));
                if ($reservasDisponibles->isEmpty()) break;

                $reserva = $reservasDisponibles->random();
                $reservasUsadasIds[] = $reserva->id;

                $clienteId = $reserva->users_id;
                $mesaDeReserva = $reserva->details_reservations->first();
                $mesaId = $mesaDeReserva ? $mesaDeReserva->tables_id : ($mesas->isNotEmpty() ? $mesas->random()->id : null);

                $existe = Sales_Note::where('date', $fecha)
                    ->where('users_client_id', $clienteId)
                    ->exists();
                if ($existe) continue;

                Sales_Note::create([
                    'hour' => $hora,
                    'date' => $fecha,
                    'total_price' => rand(20, 800) + round(rand(0, 99) / 100, 2),
                    'status' => rand(0, 1) ? 'pagado' : 'impagado', // <-- NUEVO
                    'users_admin_id' => $admin->id,
                    'users_client_id' => $clienteId,
                    'tables_id' => $mesaId,
                    'reservations_id' => $reserva->id,
                ]);

                if ($reserva->state !== 'Completada') {
                    $reserva->state = 'Completada';
                    $reserva->save();
                }

                $clientesConReservaHoy[] = ['cliente_id' => $clienteId, 'fecha' => $fecha];
                $notasCreadas++;
                continue;
            }

            $rango = $totalNotas - $notasCreadas;
            $esParaLlevar = ($rango <= 13 && ($notasCreadas >= 12 && $notasCreadas < 12 + 13)) || ($notasCreadas >= 12 && $notasCreadas < 25);
            $esLocalSinReserva = !$esParaLlevar;

            if ($esParaLlevar) {
                $clientesDisponibles = $allClients->reject(function($c) use ($fecha, $clientesConReservaHoy) {
                    return collect($clientesConReservaHoy)->contains(fn($item) => $item['cliente_id'] == $c->id && $item['fecha'] == $fecha);
                });
                if ($clientesDisponibles->isEmpty()) continue;
                $cliente = $clientesDisponibles->random();

                $existe = Sales_Note::where('date', $fecha)
                    ->where('users_client_id', $cliente->id)
                    ->exists();
                if ($existe) continue;

                Sales_Note::create([
                    'hour' => $hora,
                    'date' => $fecha,
                    'total_price' => rand(20, 800) + round(rand(0, 99) / 100, 2),
                    'status' => rand(0, 1) ? 'pagado' : 'impagado', // <-- NUEVO
                    'users_admin_id' => $admin->id,
                    'users_client_id' => $cliente->id,
                    'tables_id' => null,
                    'reservations_id' => null,
                ]);

                $notasCreadas++;
                continue;
            }

            if ($esLocalSinReserva) {
                $clientesDisponibles = $allClients->reject(function($c) use ($fecha, $clientesConReservaHoy) {
                    return collect($clientesConReservaHoy)->contains(fn($item) => $item['cliente_id'] == $c->id && $item['fecha'] == $fecha);
                });
                if ($clientesDisponibles->isEmpty()) continue;
                $cliente = $clientesDisponibles->random();

                $existe = Sales_Note::where('date', $fecha)
                    ->where('users_client_id', $cliente->id)
                    ->exists();
                if ($existe) continue;

                $mesasDisponibles = $mesas->filter(function($mesa) use ($fecha, $hora, $mesasOcupadas) {
                    $clave = $mesa->id . '_' . $fecha;
                    if (!isset($mesasOcupadas[$clave])) return true;
                    foreach ($mesasOcupadas[$clave] as $horaOcupada) {
                        $inicioOcup = Carbon::parse($horaOcupada);
                        $finOcup = $inicioOcup->copy()->addHours(2);
                        $inicioNueva = Carbon::parse($hora);
                        $finNueva = $inicioNueva->copy()->addHours(2);
                        if ($inicioNueva->lt($finOcup) && $finNueva->gt($inicioOcup)) {
                            return false;
                        }
                    }
                    return true;
                });

                if ($mesasDisponibles->isEmpty()) continue;

                $mesa = $mesasDisponibles->random();
                $clave = $mesa->id . '_' . $fecha;
                $mesasOcupadas[$clave][] = $hora;

                Sales_Note::create([
                    'hour' => $hora,
                    'date' => $fecha,
                    'total_price' => rand(20, 800) + round(rand(0, 99) / 100, 2),
                    'status' => rand(0, 1) ? 'pagado' : 'impagado',
                    'users_admin_id' => $admin->id,
                    'users_client_id' => $cliente->id,
                    'tables_id' => $mesa->id,
                    'reservations_id' => null,
                ]);

                $notasCreadas++;
                continue;
            }
        }
    }
}