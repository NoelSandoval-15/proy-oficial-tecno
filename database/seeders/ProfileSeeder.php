<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Profile;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = User::all();
        $ci = 100001;

        foreach ($usuarios as $usuario) {
            if (Profile::where("users_id", $usuario->id)->exists()) {
                continue;
            }

            // Separar nombre y apellido del campo 'name' del usuario
            $partes = explode(" ", $usuario->name);
            $nombre = $partes[0];
            $apellido = $partes[1] ?? "Desconocido";

            // Teléfono: primer dígito 6 o 7, luego 7 dígitos aleatorios
            $primerDigito = rand(0, 1) == 0 ? "6" : "7";
            $telefono =
                $primerDigito . str_pad(rand(0, 9999999), 7, "0", STR_PAD_LEFT);

            Profile::create([
                "ci" => (string) $ci,
                "name" => $nombre,
                "last_name" => $apellido,
                "telephone" => $telefono,
                "url_photo" => null,
                "users_id" => $usuario->id,
            ]);

            $ci++;
        }
    }
}
