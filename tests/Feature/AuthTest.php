<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    /**
     * Test 1: Registro de usuario exitoso
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'rol' => 'cliente'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'user',
                         'token',
                     ]
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test 2: Login exitoso
     */
    public function test_user_can_login(): void
    {
        // Crear usuario
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);

        // Intentar login
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'user',
                         'token',
                     ]
                 ]);
    }

    /**
     * Test 3: Login falla con credenciales incorrectas
     */
    public function test_login_fails_with_wrong_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
    }

    /**
     * Test 4: Obtener usuario autenticado
     */
    public function test_can_get_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@test.com',
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'name' => 'John Doe',
                         'email' => 'john@test.com',
                     ]
                 ]);
    }

    /**
     * Test 5: Logout exitoso
     */
    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Sesión cerrada exitosamente'
                 ]);
    }
}