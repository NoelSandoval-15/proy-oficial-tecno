<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuarios existentes (evitar duplicados)
        $mabo = User::firstOrCreate(
            ['email' => 'calvimontesvediam108@gmail.com'],
            [
                'name'      => 'miguel angel',
                'password'  => Hash::make('123456789'),
                'themes_id' => 1,
            ]
        );
        $mabo->assignRole('Master');

        $adminExistente = User::firstOrCreate(
            ['email' => 'grupo07sc@tecnoweb.org.bo'],
            [
                'name'      => 'Grupo07SC',
                'password'  => Hash::make('123456789'),
                'themes_id' => 2,
            ]
        );
        $adminExistente->assignRole('Administrador');

        // 2. Nuevos usuarios: 5 Administradores, 15 Meseros, 30 Clientes
        $nombres = ['Juan', 'María', 'Carlos', 'Ana', 'Luis', 'Laura', 'José', 'Carmen', 'Pedro', 'Sofía',
                    'Diego', 'Valentina', 'Andrés', 'Camila', 'Javier', 'Lucía', 'Fernando', 'Paula', 'Ricardo', 'Elena'];
        $apellidos = ['Pérez', 'López', 'García', 'Martínez', 'Rodríguez', 'Fernández', 'González', 'Sánchez',
                      'Ramírez', 'Torres', 'Flores', 'Rivera', 'Mendoza', 'Cruz', 'Gómez', 'Díaz', 'Vargas', 'Rojas'];

        $indice = 1;
        $rolesMap = [
            'Administrador' => 5,
            'Mesero'        => 15,
            'Cliente'       => 30,
        ];

        foreach ($rolesMap as $rol => $cantidad) {
            for ($i = 0; $i < $cantidad; $i++) {
                $nombre  = $nombres[$indice % count($nombres)];
                $apellido = $apellidos[$indice % count($apellidos)];
                $nombreCompleto = $nombre . ' ' . $apellido;
                $email = strtolower($nombre . '+' . $apellido . '@tecnoweb.org.bo');

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'      => $nombreCompleto,
                        'password'  => Hash::make('123456789'),
                        'themes_id' => 2,
                    ]
                );
                $user->assignRole($rol);
                $indice++;
            }
        }
    }
}
