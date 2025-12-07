<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Cliente;
use App\Mail\VerificationCodeMail;
use App\Mail\PasswordResetMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends BaseController
{
    /**
     * 📝 REGISTRO DE USUARIO (CON VERIFICACIÓN DE EMAIL)
     */
    public function register(Request $request)
    {
        try {
            // Validar datos
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'password_confirmation' => 'required|same:password',
                'telefono' => 'required|string|max:15',
            ]);

            // Verificar si el email ya existe
            $existingUser = User::where('email', $request->email)->first();
            if ($existingUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'El email ya está registrado'
                ], 409);
            }

            // Generar código de verificación
            $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            // Crear usuario
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'rol' => 'cliente',
                'verification_code' => $verificationCode,
                'verification_code_expires_at' => now()->addMinutes(15),
                'email_verified' => false
            ]);

            // Crear cliente asociado
            Cliente::create([
                'user_id' => $user->id,
                'nombre_cliente' => $validatedData['name'],
                'telefono' => $validatedData['telefono'],
                'email' => $validatedData['email'],
                'fecha_registro' => now()
            ]);

            // Enviar email con código
            try {
                \Log::error('🔵 DEBUG: Iniciando envío de email');
                \Log::error('🔵 Email destino: ' . $user->email);
                \Log::error('🔵 Código: ' . $verificationCode);
                
                // LLAMADA DIRECTA A BREVO API
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'api-key' => env('BREVO_API_KEY'),
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                ])->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => 'Lanería Mariano Díaz',
                        'email' => 'laneriamarianodiaz@gmail.com',
                    ],
                    'to' => [
                        [
                            'email' => $user->email,
                            'name' => $user->name,
                        ]
                    ],
                    'subject' => '🔐 Código de Verificación - Lanería Mariano Díaz',
                    'htmlContent' => "
                        <h1>Hola {$user->name}</h1>
                        <p>Tu código de verificación es:</p>
                        <h2 style='color: #4A90E2; font-size: 32px; letter-spacing: 5px;'>{$verificationCode}</h2>
                        <p>Este código expira en 15 minutos.</p>
                    ",
                ]);
                
                \Log::error('🔵 Response status: ' . $response->status());
                \Log::error('🔵 Response body: ' . $response->body());
                
                if ($response->successful()) {
                    \Log::error('✅ Email enviado exitosamente vía Brevo API');
                } else {
                    \Log::error('❌ Error en Brevo: ' . $response->body());
                }
                
            } catch (\Exception $e) {
                \Log::error('❌ Exception al enviar email: ' . $e->getMessage());
                \Log::error('❌ Stack: ' . $e->getTraceAsString());
            }

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente. Código: ' . $verificationCode,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'rol' => $user->rol,
                        'email_verified' => false
                    ],
                    'codigo_verificacion' => $verificationCode  // ← SOLO PARA DESARROLLO
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error en registro: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ VERIFICAR CÓDIGO DE EMAIL
     */
    public function verifyEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Usuario no encontrado', 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('El email ya está verificado', 400);
        }

        if ($user->verification_code !== $request->code) {
            return $this->errorResponse('Código de verificación incorrecto', 400);
        }

        if (now()->greaterThan($user->verification_code_expires_at)) {
            return $this->errorResponse('El código de verificación ha expirado', 400);
        }

        // Marcar como verificado
        $user->markEmailAsVerified();

        // Crear token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
                'email_verified' => true,
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ], '✅ Email verificado exitosamente');
    }

    /**
     * 🔄 REENVIAR CÓDIGO DE VERIFICACIÓN
     */
    public function resendVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Usuario no encontrado', 404);
        }

        if ($user->hasVerifiedEmail()) {
            return $this->errorResponse('El email ya está verificado', 400);
        }

        // Generar nuevo código
        $verificationCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user->verification_code = $verificationCode;
        $user->verification_code_expires_at = now()->addMinutes(15);
        $user->save();

        // Enviar email directamente con Brevo API
        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'api-key' => env('BREVO_API_KEY'),
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ])->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => [
                    'name' => 'Lanería Mariano Díaz',
                    'email' => 'laneriamarianodiaz@gmail.com',
                ],
                'to' => [
                    [
                        'email' => $user->email,
                        'name' => $user->name,
                    ]
                ],
                'subject' => '🔐 Código de Verificación - Lanería Mariano Díaz',
                'htmlContent' => "
                    <h1>Hola {$user->name}</h1>
                    <p>Tu código de verificación es:</p>
                    <h2 style='color: #4A90E2; font-size: 32px; letter-spacing: 5px;'>{$verificationCode}</h2>
                    <p>Este código expira en 15 minutos.</p>
                ",
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al reenviar email: ' . $e->getMessage());
        }

        return $this->successResponse(null, '📧 Código de verificación reenviado');
    }

    /**
     * 🔐 LOGIN (VERIFICACIÓN DE EMAIL DESACTIVADA TEMPORALMENTE)
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

        // ✅ VERIFICACIÓN DE EMAIL DESACTIVADA TEMPORALMENTE
        /*if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Debes verificar tu email antes de iniciar sesión',
                'requires_verification' => true,
                'email' => $user->email
            ], 403);
        }*/

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
     * 📧 SOLICITAR RECUPERACIÓN DE CONTRASEÑA
     */
    public function forgotPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email'
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'No existe un usuario con ese email'
                ], 200);
            }

            // Generar token único
            $token = Str::random(64);

            // Guardar token y fecha de expiración
            $user->password_reset_token = Hash::make($token);
            $user->password_reset_expires_at = now()->addHour();
            $user->save();

            // URL del frontend
            $resetUrl = env('FRONTEND_URL', 'https://laneria-mariano-frontend.vercel.app') . '/autenticacion/restablecer-contrasena?token=' . $token . '&email=' . urlencode($user->email);

            // Enviar email directamente con Brevo API
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'api-key' => env('BREVO_API_KEY'),
                    'Content-Type' => 'application/json',
                    'accept' => 'application/json',
                ])->post('https://api.brevo.com/v3/smtp/email', [
                    'sender' => [
                        'name' => 'Lanería Mariano Díaz',
                        'email' => 'laneriamarianodiaz@gmail.com',
                    ],
                    'to' => [
                        [
                            'email' => $user->email,
                            'name' => $user->name,
                        ]
                    ],
                    'subject' => '🔑 Recuperación de Contraseña - Lanería Mariano Díaz',
                    'htmlContent' => "
                        <h1>Hola {$user->name}</h1>
                        <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                        <p>Haz clic en el siguiente enlace para crear una nueva contraseña:</p>
                        <a href='{$resetUrl}' style='background-color: #4A90E2; color: white; padding: 10px 20px; text-decoration: none; display: inline-block; border-radius: 5px;'>Restablecer Contraseña</a>
                        <p>Este enlace expira en 1 hora.</p>
                        <p>Si no solicitaste este cambio, puedes ignorar este correo.</p>
                    ",
                ]);
                
                if (!$response->successful()) {
                    throw new \Exception('Error en Brevo: ' . $response->body());
                }
            } catch (\Exception $e) {
                \Log::error('Error al enviar email de recuperación: ' . $e->getMessage());
                
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar el correo. Intenta de nuevo.'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Se ha enviado un enlace de recuperación a tu correo'
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en forgot password: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔑 RESTABLECER CONTRASEÑA
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->errorResponse('Usuario no encontrado', 404);
        }

        // Verificar token
        if (!$user->password_reset_token || !Hash::check($request->token, $user->password_reset_token)) {
            return $this->errorResponse('Token de recuperación inválido', 400);
        }

        // Verificar expiración
        if (now()->greaterThan($user->password_reset_expires_at)) {
            return $this->errorResponse('El token de recuperación ha expirado', 400);
        }

        // Actualizar contraseña
        $user->password = Hash::make($request->password);
        $user->password_reset_token = null;
        $user->password_reset_expires_at = null;
        $user->save();

        return $this->successResponse(null, '✅ Contraseña restablecida exitosamente');
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
            'email_verified' => $request->user()->hasVerifiedEmail(),
        ]);
    }

    /**
     * 👤 OBTENER PERFIL COMPLETO (INCLUYE DATOS DE CLIENTE)
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
                'email_verified' => $user->hasVerifiedEmail(),
            ],
            'cliente' => [
                'cliente_id' => $cliente->cliente_id,
                'nombre_cliente' => $cliente->nombre_cliente,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'direccion' => $cliente->direccion,
                'fecha_registro' => $cliente->fecha_registro,
            ]
        ]);
    }
}