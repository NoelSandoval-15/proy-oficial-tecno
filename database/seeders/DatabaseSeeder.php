<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            ThemeSeeder::class,
            UsersSeeder::class,
            ProfileSeeder::class,
            CategorieSeeder::class,
            SubCategorieSeeder::class,
            ProductSeeder::class,
            InsumoSeeder::class,
            TableSeeder::class,
            ReservationSeeder::class,
            SalesNoteSeeder::class,
            SalesNoteSeeder::class,
            SupplierSeeder::class,
            PurchaseNotesSeeder::class,
            InsumosNotesSeeder::class,
            
            SalesDetailSeeder::class,
            // DetailsReservationSeeder::class,
            DetailsPurchasesSeeder::class,
            DetailsInsumosSeeder::class,

        ]);
    }
}
