<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\Inventario;

class ProductoTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $adminToken;
    protected $proveedor;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuario administrador
        $this->admin = User::factory()->create([
            'rol' => 'administrador'
        ]);
        $this->adminToken = $this->admin->createToken('test-token')->plainTextToken;

        // Crear proveedor
        $this->proveedor = Proveedor::create([
            'nombre' => 'Proveedor Test',
            'telefono' => '987654321',
        ]);
    }

    /**
     * Test listar productos (público)
     */
    public function test_can_list_products_without_authentication()
    {
        Producto::factory()->count(3)->create([
            'estado_producto' => 'Activo'
        ]);

        $response = $this->getJson('/api/v1/productos');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'data' => [
                             '*' => [
                                 'producto_id',
                                 'nombre_producto',
                                 'precio_producto',
                                 'stock_disponible'
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * Test crear producto
     */
    public function test_admin_can_create_product()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/productos', [
            'nombre_producto' => 'Lana Test',
            'tipo_de_producto' => 'Perlita Domino',
            'color_producto' => 'Rojo',
            'precio_producto' => 15.50,
            'stock_disponible' => 50,
            'stock_minimo' => 10,
            'proveedor_id' => $this->proveedor->proveedor_id,
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Producto creado exitosamente'
                 ]);

        $this->assertDatabaseHas('productos', [
            'nombre_producto' => 'Lana Test',
            'precio_producto' => 15.50,
        ]);

        $this->assertDatabaseHas('inventarios', [
            'stock_actual' => 50,
            'stock_minimo' => 10,
        ]);
    }

    /**
     * Test crear producto sin autenticación
     */
    public function test_cannot_create_product_without_authentication()
    {
        $response = $this->postJson('/api/v1/productos', [
            'nombre_producto' => 'Lana Test',
            'tipo_de_producto' => 'Perlita Domino',
            'precio_producto' => 15.50,
            'stock_disponible' => 50,
            'stock_minimo' => 10,
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test actualizar producto
     */
    public function test_admin_can_update_product()
    {
        $producto = Producto::factory()->create([
            'precio_producto' => 20.00
        ]);

        Inventario::create([
            'producto_id' => $producto->producto_id,
            'stock_actual' => $producto->stock_disponible,
            'stock_minimo' => 5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->putJson("/api/v1/productos/{$producto->producto_id}", [
            'precio_producto' => 25.00,
            'stock_disponible' => 60,
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Producto actualizado exitosamente'
                 ]);

        $this->assertDatabaseHas('productos', [
            'producto_id' => $producto->producto_id,
            'precio_producto' => 25.00,
            'stock_disponible' => 60,
        ]);
    }

    /**
     * Test eliminar producto con stock
     */
    public function test_cannot_delete_product_with_stock()
    {
        $producto = Producto::factory()->create([
            'stock_disponible' => 10
        ]);

        Inventario::create([
            'producto_id' => $producto->producto_id,
            'stock_actual' => 10,
            'stock_minimo' => 5,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->deleteJson("/api/v1/productos/{$producto->producto_id}");

        $response->assertStatus(400)
                 ->assertJson([
                     'success' => false,
                     'message' => 'No se puede eliminar un producto con stock disponible'
                 ]);

        $this->assertDatabaseHas('productos', [
            'producto_id' => $producto->producto_id,
        ]);
    }

    /**
     * Test ver detalle de producto
     */
    public function test_can_view_product_detail()
    {
        $producto = Producto::factory()->create([
            'nombre_producto' => 'Producto Detalle',
        ]);

        Inventario::create([
            'producto_id' => $producto->producto_id,
            'stock_actual' => $producto->stock_disponible,
            'stock_minimo' => 5,
        ]);

        $response = $this->getJson("/api/v1/productos/{$producto->producto_id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'nombre_producto' => 'Producto Detalle',
                     ]
                 ]);
    }

    /**
     * Test validación al crear producto
     */
    public function test_product_creation_validates_required_fields()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->adminToken,
        ])->postJson('/api/v1/productos', [
            'nombre_producto' => '',
            'precio_producto' => -10,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'nombre_producto',
                     'tipo_de_producto',
                     'precio_producto',
                     'stock_disponible',
                     'stock_minimo',
                 ]);
    }
}