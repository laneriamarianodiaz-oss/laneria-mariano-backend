<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseController
{
    /**
     * 📝 REGISTRO CON DNI OBLIGATORIO
     */
    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|same:password',
                'telefono' => 'required|string|max:20',
                'dni' => 'required|string|max:20', // ⭐ OBLIGATORIO
                'direccion' => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            try {
                // 1. Crear usuario verificado automáticamente
                $user = User::create([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'rol' => 'cliente',
                    'email_verified_at' => now(),
                ]);

                // 2. Crear cliente con DNI
                Cliente::create([
                    'user_id' => $user->id,
                    'nombre_cliente' => $validatedData['name'],
                    'dni' => $validatedData['dni'], // ⭐ GUARDAR DNI
                    'telefono' => $validatedData['telefono'],
                    'email' => $validatedData['email'],
                    'direccion' => $validatedData['direccion'] ?? null,
                    'fecha_registro' => now()
                ]);

                DB::commit();

                // 3. Crear token automáticamente
                $token = $user->createToken('auth_token')->plainTextToken;

                \Log::info('✅ Usuario registrado exitosamente', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'dni' => $validatedData['dni']
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Usuario registrado exitosamente',
                    'data' => [
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'rol' => $user->rol,
                        ],
                        'token' => $token,
                        'token_type' => 'Bearer'
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('❌ Error en transacción: ' . $e->getMessage());
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('❌ Error al registrar usuario: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔐 LOGIN SIMPLE
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->errorResponse('Credenciales incorrectas', 401);
        }

        // Eliminar tokens anteriores
        $user->tokens()->delete();

        // Crear nuevo token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], '✅ Login exitoso');
    }

    /**
     * 🚪 LOGOUT
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return $this->successResponse(null, '✅ Sesión cerrada exitosamente');
    }

    /**
     * 👤 OBTENER USUARIO AUTENTICADO
     */
    public function me(Request $request)
    {
        return $this->successResponse([
            'id' => $request->user()->id,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'rol' => $request->user()->rol,
        ]);
    }

    /**
     * 👤 OBTENER PERFIL COMPLETO
     */
    public function miPerfil(Request $request)
    {
        $user = $request->user();
        $cliente = $user->cliente;

        if (!$cliente) {
            return $this->errorResponse('Usuario no tiene perfil de cliente', 404);
        }

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
            'cliente' => [
                'cliente_id' => $cliente->cliente_id,
                'nombre_cliente' => $cliente->nombre_cliente,
                'dni' => $cliente->dni, // ⭐ INCLUIR DNI
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'direccion' => $cliente->direccion,
                'fecha_registro' => $cliente->fecha_registro,
            ]
        ]);
    }

    /**
     * ✏️ ACTUALIZAR PERFIL
     */
    public function actualizarPerfil(Request $request)
    {
        try {
            $user = $request->user();
            $cliente = $user->cliente;

            if (!$cliente) {
                return $this->errorResponse('Usuario no tiene perfil de cliente', 404);
            }

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'telefono' => 'sometimes|string|max:20',
                'dni' => 'sometimes|string|max:20',
                'direccion' => 'nullable|string|max:255',
            ]);

            DB::beginTransaction();

            try {
                // Actualizar usuario
                if (isset($validatedData['name'])) {
                    $user->name = $validatedData['name'];
                    $user->save();
                }

                // Actualizar cliente
                $datosCliente = [];
                if (isset($validatedData['name'])) {
                    $datosCliente['nombre_cliente'] = $validatedData['name'];
                }
                if (isset($validatedData['telefono'])) {
                    $datosCliente['telefono'] = $validatedData['telefono'];
                }
                if (isset($validatedData['dni'])) {
                    $datosCliente['dni'] = $validatedData['dni'];
                }
                if (isset($validatedData['direccion'])) {
                    $datosCliente['direccion'] = $validatedData['direccion'];
                }

                if (!empty($datosCliente)) {
                    $cliente->update($datosCliente);
                }

                DB::commit();

                return $this->successResponse([
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'rol' => $user->rol,
                    ],
                    'cliente' => [
                        'cliente_id' => $cliente->cliente_id,
                        'nombre_cliente' => $cliente->nombre_cliente,
                        'dni' => $cliente->dni,
                        'telefono' => $cliente->telefono,
                        'email' => $cliente->email,
                        'direccion' => $cliente->direccion,
                    ]
                ], 'Perfil actualizado exitosamente');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return $this->errorResponse('Error al actualizar perfil: ' . $e->getMessage(), 500);
        }
    }
}