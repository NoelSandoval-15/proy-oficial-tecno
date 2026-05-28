<?php

namespace Database\Seeders;

use App\Models\Details_Insumos;
use App\Models\Insumo;
use App\Models\Insumos_Notes;
use Illuminate\Database\Seeder;

class DetailsInsumosSeeder extends Seeder
{
    public function run(): void
    {
        $notas = Insumos_Notes::all();
        $insumos = Insumo::all();

        if ($notas->isEmpty()) {
            $this->command->error('No hay notas de insumos. Ejecuta primero InsumosNotesSeeder.');
            return;
        }

        if ($insumos->isEmpty()) {
            $this->command->error('No hay insumos. Ejecuta primero InsumoSeeder.');
            return;
        }

        $totalDetalles = 0;

        foreach ($notas as $nota) {
            $numDetalles = rand(1, 5);
            $insumosUsados = [];

            for ($i = 0; $i < $numDetalles; $i++) {
                $insumosDisponibles = $insumos->reject(fn($i) => in_array($i->id, $insumosUsados));
                if ($insumosDisponibles->isEmpty()) {
                    break;
                }
                $insumo = $insumosDisponibles->random();
                $insumosUsados[] = $insumo->id;

                $maxRestar = min(30, $insumo->amount);
                if ($maxRestar <= 0) {
                    continue;
                }
                $cantidad = rand(1, $maxRestar);

                Details_Insumos::create([
                    'insumos_id'       => $insumo->id,
                    'insumos_notes_id' => $nota->id,
                    'amount'           => $cantidad,
                ]);

                $insumo->amount -= $cantidad;
                $insumo->save();

                $totalDetalles++;
            }
        }
    }
}
