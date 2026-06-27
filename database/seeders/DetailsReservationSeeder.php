<?php

namespace Database\Seeders;

use App\Models\DetailsReservation;
use App\Models\Reservation;
use App\Models\Table;
use Illuminate\Database\Seeder;

class DetailsReservationSeeder extends Seeder
{

    private $maxDetailsToCreate = 0;

    public function run(): void
    {
        if ($this->maxDetailsToCreate <= 0) {
            $this->command->info('DetailsReservationSeeder está deshabilitado (maxDetailsToCreate = 0).');
            return;
        }

        $reservasSinDetalles = Reservation::doesntHave('details_reservations')->get();
        if ($reservasSinDetalles->isEmpty()) {
            $this->command->info('No hay reservas sin detalles. No se crearon nuevos detalles.');
            return;
        }

        $mesas = Table::all();
        if ($mesas->isEmpty()) {
            $this->command->error('No hay mesas registradas. No se pueden asignar detalles.');
            return;
        }

        $detallesCreados = 0;

        foreach ($reservasSinDetalles as $reserva) {
            if ($detallesCreados >= $this->maxDetailsToCreate) break;

            $mesasOcupadas = DetailsReservation::whereHas('reservation', function ($query) use ($reserva) {
                $query->where('date', $reserva->date)
                      ->where('hour', $reserva->hour)
                      ->whereNotIn('state', ['Cancelada', 'Completada'])
                      ->where('id', '!=', $reserva->id);
            })->pluck('tables_id')->toArray();

            $mesasDisponibles = $mesas->filter(fn($mesa) => !in_array($mesa->id, $mesasOcupadas));

            if ($mesasDisponibles->isEmpty()) {
                $this->command->warn("Reserva ID {$reserva->id} no tiene mesas disponibles en esa fecha/hora.");
                continue;
            }

            $numMesas = rand(1, min(3, $mesasDisponibles->count()));
            $mesasSeleccionadas = $mesasDisponibles->random($numMesas);

            foreach ($mesasSeleccionadas as $mesa) {
                DetailsReservation::create([
                    'reservations_id' => $reserva->id,
                    'tables_id'       => $mesa->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                $detallesCreados++;
                if ($detallesCreados >= $this->maxDetailsToCreate) break;
            }
        }

        $this->command->info("Se crearon {$detallesCreados} detalles para reservas que carecían de ellos.");
    }
}