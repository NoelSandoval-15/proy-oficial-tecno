<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\Details_Reservation;   // ← Modelo correcto
use App\Models\Table;
use App\Models\User;
use Carbon\Carbon;

class ReservationSeeder extends Seeder
{
    private $totalReservations;

    public function __construct()
    {
        $this->totalReservations = rand(30, 60);
    }

    public function run(): void
    {
        $empleados = User::role(['Master', 'Administrador', 'Mesero'])->get();
        if ($empleados->isEmpty()) {
            $this->command->error('No hay empleados con rol Master, Administrador o Mesero. Ejecuta primero UsersSeeder.');
            return;
        }

        $clientes = User::role('Cliente')->get();
        if ($clientes->isEmpty()) {
            $this->command->error('No hay clientes con rol Cliente. Ejecuta primero UsersSeeder.');
            return;
        }

        $mesas = Table::all();
        if ($mesas->isEmpty()) {
            $this->command->error('No hay mesas registradas. Ejecuta primero TableSeeder.');
            return;
        }

        $descripciones = [
            'Cumpleaños', 'Cita romántica', 'Propuesta de matrimonio', 'Aniversario',
            'Reunión de negocios', 'Cena familiar', 'Despedida de soltero/a',
            'Celebración especial', 'Almuerzo ejecutivo', 'Evento corporativo',
        ];

        $estados = ['Pendiente', 'En Proceso', 'Cancelada', 'Completada'];

        $fechaInicio = Carbon::create(2026, 1, 1);
        $fechaFin    = Carbon::create(2026, 12, 31);
        $diasRango   = $fechaInicio->diffInDays($fechaFin);

        $reservasCreadas = 0;

        while ($reservasCreadas < $this->totalReservations) {
            $cliente = $clientes->random();
            $empleado = $empleados->random();

            $fecha = $fechaInicio->copy()->addDays(rand(0, $diasRango))->format('Y-m-d');
            $hora = rand(17, 23);
            $minuto = rand(0, 59);
            $segundo = rand(0, 59);
            if (rand(1, 100) <= 5) {
                $hora = 0;
                $minuto = 0;
                $segundo = 0;
            }
            $horaFormateada = sprintf('%02d:%02d:%02d', $hora, $minuto, $segundo);

            $reservaExistente = Reservation::where('users_cliente_id', $cliente->id)
                ->where('date', $fecha)
                ->where('hour', $horaFormateada)
                ->whereNotIn('state', ['Cancelada', 'Completada'])
                ->exists();

            if ($reservaExistente) {
                continue;
            }

            // CORRECCIÓN: usar el modelo Details_Reservation y la relación 'reservations'
            $mesasOcupadas = Details_Reservation::whereHas('reservations', function ($query) use ($fecha, $horaFormateada) {
                $query->where('date', $fecha)
                      ->where('hour', $horaFormateada)
                      ->whereNotIn('state', ['Cancelada', 'Completada']);
            })->pluck('tables_id')->toArray();

            $mesasDisponibles = $mesas->filter(fn($mesa) => !in_array($mesa->id, $mesasOcupadas));

            if ($mesasDisponibles->isEmpty()) {
                continue;
            }

            $numMesas = rand(1, min(3, $mesasDisponibles->count()));
            $mesasSeleccionadas = $mesasDisponibles->random($numMesas);

            $descripcion = $descripciones[array_rand($descripciones)];
            $estado = $estados[array_rand($estados)];

            $reserva = Reservation::create([
                'descriptions'      => $descripcion,
                'hour'              => $horaFormateada,
                'date'              => $fecha,
                'state'             => $estado,
                'users_id'          => $empleado->id,
                'users_cliente_id'  => $cliente->id,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            foreach ($mesasSeleccionadas as $mesa) {
                Details_Reservation::create([
                    'reservations_id' => $reserva->id,
                    'tables_id'       => $mesa->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            $reservasCreadas++;
        }

        $this->command->info("Se crearon {$reservasCreadas} reservas correctamente.");
    }
}