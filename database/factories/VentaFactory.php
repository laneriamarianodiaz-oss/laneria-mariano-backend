<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

class VentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cliente_id' => Cliente::factory(),
            'fecha_venta' => now(),
            'estado_venta' => 'Completada',
            'total_venta' => fake()->randomFloat(2, 20, 500),
            'metodo_pago' => fake()->randomElement(['Efectivo', 'Transferencia', 'Yape', 'Plin']),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}