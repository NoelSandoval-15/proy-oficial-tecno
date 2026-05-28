<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'Carnes Santa Cruz',
                'description' => 'Proveedor de carnes de res, cerdo y pollo de alta calidad',
                'telephone' => 34567890,
                'url_photo' => 'img/suppliers/carnes_santa_cruz.jpg',
            ],
            [
                'name' => 'Frigorífico BFC',
                'description' => 'Carnes premium y embutidos para restaurantes',
                'telephone' => 71234567,
                'url_photo' => null,
            ],
            [
                'name' => 'Embutidos La Criolla',
                'description' => 'Chorizos, morcillas y salchichas artesanales',
                'telephone' => 76543210,
                'url_photo' => 'img/suppliers/la_criolla.jpg',
            ],
            [
                'name' => 'Pescados del Oriente',
                'description' => 'Pescado de río (surubí, pacú) y mariscos',
                'telephone' => 67890123,
                'url_photo' => null,
            ],
            [
                'name' => 'Verduras San José',
                'description' => 'Frutas y verduras frescas para el sector gastronómico',
                'telephone' => 78901234,
                'url_photo' => null,
            ],
            [
                'name' => 'Distribuidora de Granos La Paz',
                'description' => 'Arroz, frijol, quinua y otros granos',
                'telephone' => 23456789,
                'url_photo' => null,
            ],
            [
                'name' => 'Yuca y Papas del Valle',
                'description' => 'Especialistas en tubérculos y productos de la canasta básica',
                'telephone' => 69012345,
                'url_photo' => null,
            ],
            [
                'name' => 'Carbones Los Quemas',
                'description' => 'Carbón vegetal y leña para parrillas',
                'telephone' => 70123456,
                'url_photo' => 'img/suppliers/carbones_los_quemas.jpg',
            ],
            [
                'name' => 'Energía Natural',
                'description' => 'Carbón ecológico y encendedores líquidos',
                'telephone' => 61234567,
                'url_photo' => null,
            ],
            [
                'name' => 'Cervecería Boliviana Nacional',
                'description' => 'Distribuidor de cervezas Paceña, Huari, Potosina',
                'telephone' => 22222222,
                'url_photo' => null,
            ],
            [
                'name' => 'Embotelladoras Unidas',
                'description' => 'Gaseosas Coca-Cola, Sprite, Fanta, Manaos',
                'telephone' => 22334455,
                'url_photo' => null,
            ],
            [
                'name' => 'Vinos y Licores Copacabana',
                'description' => 'Vinos, singanis y licores importados',
                'telephone' => 77665544,
                'url_photo' => null,
            ],
            [
                'name' => 'Distribuidora Sal Real',
                'description' => 'Sal, azúcar, especias y condimentos',
                'telephone' => 78889999,
                'url_photo' => null,
            ],
            [
                'name' => 'Salsas y Aderezos Doña Clara',
                'description' => 'Chimichurri, salsa barbacoa y aderezos caseros',
                'telephone' => 70001122,
                'url_photo' => null,
            ],
            [
                'name' => 'Lácteos Pando',
                'description' => 'Leche condensada, dulce de leche y productos lácteos',
                'telephone' => 73344556,
                'url_photo' => null,
            ],
            [
                'name' => 'Helados Artesanales La Merced',
                'description' => 'Baldes de helado y postres congelados',
                'telephone' => 66778899,
                'url_photo' => null,
            ],
            [
                'name' => 'Envases y Desechables del Sur',
                'description' => 'Platos, vasos, cubiertos y servilletas desechables',
                'telephone' => 34455667,
                'url_photo' => null,
            ],
            [
                'name' => 'EcoPack Bolivia',
                'description' => 'Desechables ecológicos y biodegradables',
                'telephone' => 75544332,
                'url_photo' => null,
            ],
            [
                'name' => 'Insumos y Limpieza Total',
                'description' => 'Productos de limpieza y químicos para cocina',
                'telephone' => 78899001,
                'url_photo' => null,
            ],
            [
                'name' => 'Hielo Glaciar',
                'description' => 'Hielo en bolsa de 1 kg y 5 kg',
                'telephone' => 71234589,
                'url_photo' => null,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::firstOrCreate(
                ['name' => $supplier['name']],
                [
                    'description' => $supplier['description'],
                    'telephone'   => $supplier['telephone'],
                    'url_photo'   => $supplier['url_photo'],
                ]
            );
        }
    }
}
