<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_cliente' => fake()->name(),
            'contacto_cliente' => fake()->name(),
            'telefono' => fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'preferencias_cliente' => fake()->sentence(),
            'historial_compras' => null,
            'fecha_registro' => now(),
        ];
    }
}