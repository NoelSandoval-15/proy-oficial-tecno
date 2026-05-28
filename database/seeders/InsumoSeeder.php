<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Insumo;

class InsumoSeeder extends Seeder
{
    public function run(): void
    {
        $insumos = [
            // Carnes (kg)
            ['name' => 'Carne de res (falda) - 1 kg', 'amount' => 50, 'price' => 35.00],
            ['name' => 'Carne de res (lomo) - 1 kg', 'amount' => 30, 'price' => 55.00],
            ['name' => 'Pollo entero - 1 kg', 'amount' => 40, 'price' => 18.00],
            ['name' => 'Cerdo (paleta) - 1 kg', 'amount' => 25, 'price' => 28.00],
            ['name' => 'Cerdo (costillas) - 1 kg', 'amount' => 20, 'price' => 32.00],
            ['name' => 'Chorizo criollo - 1 kg', 'amount' => 30, 'price' => 25.00],
            ['name' => 'Chorizo criollo - 5 kg', 'amount' => 15, 'price' => 120.00],
            ['name' => 'Morcilla - 1 kg', 'amount' => 15, 'price' => 22.00],
            ['name' => 'Pescado (surubí) - 1 kg', 'amount' => 10, 'price' => 40.00],

            // Carbón y leña (kg)
            ['name' => 'Carbón vegetal - 5 kg', 'amount' => 100, 'price' => 8.00],
            ['name' => 'Carbón vegetal - 10 kg', 'amount' => 50, 'price' => 15.00],
            ['name' => 'Leña de quebracho - 5 kg', 'amount' => 80, 'price' => 6.00],
            ['name' => 'Leña de quebracho - 10 kg', 'amount' => 40, 'price' => 11.00],
            ['name' => 'Encendedor líquido - 1 L', 'amount' => 20, 'price' => 15.00],
            ['name' => 'Encendedor líquido - 5 L', 'amount' => 8, 'price' => 65.00],

            // Verduras (kg o unidad)
            ['name' => 'Papas - 1 kg', 'amount' => 60, 'price' => 4.50],
            ['name' => 'Papas - 5 kg', 'amount' => 30, 'price' => 20.00],
            ['name' => 'Cebolla - 1 kg', 'amount' => 40, 'price' => 5.00],
            ['name' => 'Tomate - 1 kg', 'amount' => 30, 'price' => 6.00],
            ['name' => 'Lechuga - unidad', 'amount' => 50, 'price' => 4.00],
            ['name' => 'Zanahoria - 1 kg', 'amount' => 25, 'price' => 3.50],
            ['name' => 'Morrón - 1 kg', 'amount' => 20, 'price' => 8.00],
            ['name' => 'Yuca - 1 kg', 'amount' => 40, 'price' => 4.00],

            // Granos y arroz
            ['name' => 'Arroz - 1 kg', 'amount' => 50, 'price' => 6.50],
            ['name' => 'Arroz - 5 kg', 'amount' => 20, 'price' => 30.00],
            ['name' => 'Frijol - 1 kg', 'amount' => 30, 'price' => 8.00],
            ['name' => 'Quinua - 1 kg', 'amount' => 15, 'price' => 15.00],

            // Condimentos y salsas (kg, L o unidad)
            ['name' => 'Sal gruesa - 1 kg', 'amount' => 20, 'price' => 2.50],
            ['name' => 'Sal gruesa - 5 kg', 'amount' => 10, 'price' => 11.00],
            ['name' => 'Pimienta - 100 g', 'amount' => 5, 'price' => 25.00],
            ['name' => 'Pimentón - 100 g', 'amount' => 5, 'price' => 20.00],
            ['name' => 'Azúcar - 1 kg', 'amount' => 15, 'price' => 5.00],
            ['name' => 'Azúcar - 2 kg', 'amount' => 10, 'price' => 9.00],
            ['name' => 'Azúcar - 5 kg', 'amount' => 5, 'price' => 22.00],
            ['name' => 'Aceite vegetal - 1 L', 'amount' => 30, 'price' => 10.00],
            ['name' => 'Aceite vegetal - 5 L', 'amount' => 10, 'price' => 45.00],
            ['name' => 'Vinagre - 1 L', 'amount' => 10, 'price' => 8.00],
            ['name' => 'Chimichurri (preparado) - 1 L', 'amount' => 8, 'price' => 12.00],
            ['name' => 'Salsa barbacoa - 1 L', 'amount' => 6, 'price' => 15.00],

            // Bebidas (insumos para preparar o vender)
            ['name' => 'Agua mineral - 1 L', 'amount' => 100, 'price' => 2.00],
            ['name' => 'Agua mineral - 5 L', 'amount' => 40, 'price' => 8.00],
            ['name' => 'Gaseosa (cola) - 1.5 L', 'amount' => 80, 'price' => 6.00],
            ['name' => 'Cerveza (lata) - 355 ml', 'amount' => 200, 'price' => 5.00],
            ['name' => 'Hielo - 1 kg', 'amount' => 50, 'price' => 3.00],

            // Postres y lácteos
            ['name' => 'Helado (balde) - 2 L', 'amount' => 15, 'price' => 25.00],
            ['name' => 'Gelatina en polvo - 100 g', 'amount' => 30, 'price' => 4.00],
            ['name' => 'Leche condensada - 400 g', 'amount' => 12, 'price' => 12.00],
            ['name' => 'Dulce de leche - 1 kg', 'amount' => 10, 'price' => 18.00],

            // Desechables (unidades)
            ['name' => 'Platos descartables (unidad)', 'amount' => 300, 'price' => 0.50],
            ['name' => 'Vasos descartables (unidad)', 'amount' => 500, 'price' => 0.30],
            ['name' => 'Servilletas (paquete x 100)', 'amount' => 200, 'price' => 0.20],
            ['name' => 'Cubiertos descartables (unidad)', 'amount' => 300, 'price' => 0.40],
        ];

        foreach ($insumos as $insumo) {
            Insumo::firstOrCreate(
                ['name' => $insumo['name']],
                [
                    'amount' => $insumo['amount'],
                    'price'  => $insumo['price'],
                ]
            );
        }
    }
}
