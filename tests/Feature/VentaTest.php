<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Inventario;
use App\Models\Venta;

class VentaTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminToken;
    protected $cliente;
    protected $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['rol' => 'administrador']);
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;

        $this->cliente = Cliente::factory()->create();
        
        $this->producto = Producto::factory()->create([
            'precio_producto' => 50.00,
            'stock_disponible' => 20,
        ]);

        Inventario::create([
            'producto_id' => $this->producto->producto_id,
            'stock_actual' => 20,
            'stock_minimo' => 5,
        ]);
    }

    /**
     * Test crear venta exitosa
     */
    public function test_can_create_sale()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/ventas', [
            'cliente_id' => $this->cliente->cliente_id,
            'metodo_pago' => 'Efectivo',
            'observaciones' => 'Venta de prueba',
            'detalles' => [
                [
                    'producto_id' => $this->producto->producto_id,
                    'cantidad' => 2,
                ]
            ]
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Venta registrada exitosamente'
                 ]);

        $this->assertDatabaseHas('ventas', [
            'cliente_id' => $this->cliente->cliente_id,
            'estado_venta' => 'Completada',
            'total_venta' => 100.00, // 2 * 50.00
        ]);

        // Verificar que el stock se actualizó
        $this->producto->refresh();
        $this->assertEquals(18, $this->producto->stock_disponible);
    }

    /**
     * Test venta falla por stock insuficiente
     */
    public function test_sale_fails_with_insufficient_stock()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/ventas', [
            'cliente_id' => $this->cliente->cliente_id,
            'metodo_pago' => 'Efectivo',
            'detalles' => [
                [
                    'producto_id' => $this->producto->producto_id,
                    'cantidad' => 100, // Más del stock disponible
                ]
            ]
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                     'success' => false,
                 ]);
    }

    /**
     * Test listar ventas
     */
    public function test_admin_can_list_sales()
    {
        Venta::factory()->count(5)->create([
            'cliente_id' => $this->cliente->cliente_id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/v1/ventas');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'venta_id',
                                 'cliente_id',
                                 'fecha_venta',
                                 'total_venta',
                                 'estado_venta',
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * Test cambiar estado de venta
     */
    public function test_admin_can_change_sale_status()
    {
        $venta = Venta::factory()->create([
            'estado_venta' => 'Pendiente',
            'cliente_id' => $this->cliente->cliente_id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson("/api/v1/ventas/{$venta->venta_id}/estado", [
            'estado' => 'Completada'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Estado actualizado exitosamente'
                 ]);

        $this->assertDatabaseHas('ventas', [
            'venta_id' => $venta->venta_id,
            'estado_venta' => 'Completada',
        ]);
    }

    /**
     * Test obtener estadísticas de ventas
     */
    public function test_admin_can_get_sales_statistics()
    {
        // Crear algunas ventas
        Venta::factory()->count(3)->create([
            'cliente_id' => $this->cliente->cliente_id,
            'estado_venta' => 'Completada',
            'total_venta' => 100.00,
            'fecha_venta' => now(),
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->getJson('/api/v1/ventas/estadisticas/general');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'periodo',
                         'resumen' => [
                             'total_ventas',
                             'cantidad_ventas',
                             'ticket_promedio',
                         ]
                     ]
                 ]);
    }
}