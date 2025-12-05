<?php

namespace Database\Factories;

use App\Models\Proveedor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre_producto' => fake()->words(3, true),
            'tipo_de_producto' => fake()->randomElement(['Perlita Domino', 'Silvia Clásica', 'Chompa', 'Chalina', 'Gorro']),
            'color_producto' => fake()->safeColorName(),
            'talla_producto' => fake()->randomElement(['S', 'M', 'L', 'XL', null]),
            'precio_producto' => fake()->randomFloat(2, 10, 150),
            'stock_disponible' => fake()->numberBetween(10, 100),
            'descripcion' => fake()->sentence(),
            'imagen_url' => null,
            'proveedor_id' => null,
            'estado_producto' => 'Activo',
            'fecha_creacion' => now(),
        ];
    }
}