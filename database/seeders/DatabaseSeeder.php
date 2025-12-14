<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Proveedor;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\Cliente;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario administrador
        User::create([
            'name' => 'Melchora Bernaola',
            'email' => 'admin@laneriamariano.com',
            'password' => Hash::make('admin123'),
            'rol' => 'administrador',
        ]);

        // Crear usuario vendedor
        User::create([
            'name' => 'Vendedor Test',
            'email' => 'vendedor@laneriamariano.com',
            'password' => Hash::make('vendedor123'),
            'rol' => 'vendedor',
        ]);

        // Crear proveedores
        $proveedor1 = Proveedor::create([
            'nombre_proveedor' => 'Textiles Andinos SAC',
            'contacto_proveedor' => 'Juan Pérez',
            'telefono' => '987654321',
            'email' => 'contacto@textilesandinos.com',
            'direccion' => 'Av. Principal 123, Andahuaylas',
        ]);

        $proveedor2 = Proveedor::create([
            'nombre_proveedor' => 'Lanas del Sur EIRL',
            'contacto_proveedor' => 'María González',
            'telefono' => '912345678',
            'email' => 'ventas@lanasdelsur.com',
            'direccion' => 'Jr. Comercio 456, Cusco',
        ]);

        // Crear productos con inventario
        $productos = [
            [
                'nombre_producto' => 'Lana Perlita Domino Roja',
                'tipo_de_producto' => 'Perlita Domino',
                'color_producto' => 'Rojo',
                'talla_producto' => null,
                'precio_producto' => 15.50,
                'stock_disponible' => 50,
                'descripcion' => 'Lana suave de alta calidad, ideal para tejidos finos',
                'proveedor_id' => $proveedor1->proveedor_id,
                'stock_minimo' => 10,
            ],
            [
                'nombre_producto' => 'Lana Silvia Clásica Azul',
                'tipo_de_producto' => 'Silvia Clásica',
                'color_producto' => 'Azul',
                'talla_producto' => null,
                'precio_producto' => 18.00,
                'stock_disponible' => 35,
                'descripcion' => 'Lana resistente perfecta para prendas de uso diario',
                'proveedor_id' => $proveedor1->proveedor_id,
                'stock_minimo' => 8,
            ],
            [
                'nombre_producto' => 'Chompa de Alpaca Talla M',
                'tipo_de_producto' => 'Chompa',
                'color_producto' => 'Beige',
                'talla_producto' => 'M',
                'precio_producto' => 120.00,
                'stock_disponible' => 15,
                'descripcion' => 'Chompa artesanal de alpaca 100% natural',
                'proveedor_id' => $proveedor2->proveedor_id,
                'stock_minimo' => 5,
            ],
            [
                'nombre_producto' => 'Chalina Multicolor',
                'tipo_de_producto' => 'Chalina',
                'color_producto' => 'Multicolor',
                'talla_producto' => 'Única',
                'precio_producto' => 45.00,
                'stock_disponible' => 8,
                'descripcion' => 'Chalina tejida a mano con diseños tradicionales',
                'proveedor_id' => $proveedor2->proveedor_id,
                'stock_minimo' => 3,
            ],
            [
                'nombre_producto' => 'Gorro de Lana Verde',
                'tipo_de_producto' => 'Gorro',
                'color_producto' => 'Verde',
                'talla_producto' => 'Única',
                'precio_producto' => 25.00,
                'stock_disponible' => 20,
                'descripcion' => 'Gorro tejido con lana de oveja',
                'proveedor_id' => $proveedor1->proveedor_id,
                'stock_minimo' => 5,
            ],
        ];

        foreach ($productos as $productoData) {
            $stockMinimo = $productoData['stock_minimo'];
            unset($productoData['stock_minimo']);

            $producto = Producto::create($productoData);

            // Crear inventario para cada producto
            Inventario::create([
                'producto_id' => $producto->producto_id,
                'stock_actual' => $producto->stock_disponible,
                'stock_minimo' => $stockMinimo,
            ]);
        }

        // Crear clientes de prueba
        Cliente::create([
            'nombre_cliente' => 'Carlos Mendoza',
            'telefono' => '999888777',
            'email' => 'carlos.mendoza@email.com',
            'direccion' => 'Av. Los Andes 789, Andahuaylas',
            'preferencias_cliente' => 'Prefiere colores cálidos',
        ]);

        Cliente::create([
            'nombre_cliente' => 'Ana Torres',
            'telefono' => '988777666',
            'email' => 'ana.torres@email.com',
            'direccion' => 'Jr. San Martín 321, Andahuaylas',
            'preferencias_cliente' => 'Le gustan las chompas y chalinas',
        ]);

        Cliente::create([
            'nombre_cliente' => 'Luis Fernández',
            'telefono' => '977666555',
            'email' => null,
            'direccion' => 'Psje. Las Flores 555, Pacucha',
        ]);
    }
}