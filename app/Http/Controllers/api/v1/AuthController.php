<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\loginUserFormRequest;
use App\Http\Requests\Auth\registerUserFormRequest;
use App\Http\Requests\Auth\updateUserFormRequest;
use App\Http\Resources\Auth\registerUserResource;
use App\Http\Resources\Auth\updateUserResource;
use App\Models\User;
use App\Services\Auth\deleteUserService;
use App\Services\Auth\loginUserService;
use App\Services\Auth\registerUserService;
use App\Services\Auth\updateUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    public function __construct(
        private registerUserService $register_user_service,
        private loginUserService $login_user_service,
        private updateUserService $update_user_service,
        private deleteUserService $delete_user_service,
    ) {}
    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'Registrar un usuario',
        description: 'Crea el usuario con rol "usuario" y su cuenta asociada con saldo 0.00. El rol no puede elegirse desde este endpoint.',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['nombre', 'email', 'password', 'password_confirmation', 'edad'],
                properties: [
                    new OA\Property(property: 'nombre', type: 'string', example: 'Swagger Test'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'swagger@test.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'edad', type: 'integer', minimum: 18, example: 30),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado correctamente',
                content: new OA\JsonContent(
                    required: ['id', 'nombre', 'email', 'rol'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nombre', type: 'string', example: 'Swagger Test'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'swagger@test.com'),
                        new OA\Property(property: 'rol', type: 'string', example: 'usuario'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validacion (campos faltantes, email duplicado, contraseñas distintas o menor de 18)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function register(registerUserFormRequest $request): JsonResponse
    {
        $user = $this->register_user_service->create($request->toDTO());

        return response()->json(new registerUserResource($user), 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Iniciar sesion',
        description: 'Devuelve un JWT. Copia el valor de access_token y cargalo en el boton Authorize (sin escribir "Bearer").',
        tags: ['Autenticación'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'swagger@test.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login correcto',
                content: new OA\JsonContent(
                    required: ['access_token', 'token_type', 'expires_in'],
                    properties: [
                        new OA\Property(property: 'access_token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciOi...'),
                        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                        new OA\Property(property: 'expires_in', type: 'integer', example: 3600),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales incorrectas',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validacion',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    //nuevo login para el RateLimit de intentos de sesión
    public function login(loginUserFormRequest $request): JsonResponse
    {
        $email = Str::lower(trim((string) $request->input('email')));
        $key = 'login:' . $email . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Demasiados intentos de inicio de sesión. Intente nuevamente más tarde.',
                'status' => 429,
                'retry_after' => $seconds,
                'error' => (object) [],
            ], 429)->header('Retry-After', (string) $seconds);
        }

        $token = $this->login_user_service->login($request->toDTO());

        if ($token === null) {
            RateLimiter::hit($key, 60);

            return response()->json([
                'message' => 'Credenciales invalidas',
                'status' => 401,
                'error' => (object) [],
            ], 401);
        }

        RateLimiter::clear($key);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
        ], 200);
    }

    #[OA\Put(
        path: '/api/v1/profile',
        summary: 'Actualizar el perfil del usuario autenticado',
        description: 'Se envia como multipart/form-data porque acepta una imagen. Todos los campos son opcionales: solo se modifican los que se envian. El rol no puede modificarse.',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Nuevo Nombre'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'nuevo@test.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'nueva1234'),
                        new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'nueva1234'),
                        new OA\Property(property: 'edad', type: 'integer', minimum: 18, maximum: 120, example: 31),
                        new OA\Property(property: 'imagen', description: 'JPG, JPEG, PNG o WebP de hasta 2 MB', type: 'string', format: 'binary'),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil actualizado correctamente',
                content: new OA\JsonContent(
                    required: ['id', 'nombre', 'email', 'edad', 'imagen', 'rol'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nombre', type: 'string', example: 'Nuevo Nombre'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nuevo@test.com'),
                        new OA\Property(property: 'edad', type: 'integer', example: 31),
                        new OA\Property(property: 'imagen', type: 'string', nullable: true, example: null),
                        new OA\Property(property: 'rol', type: 'string', example: 'usuario'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validacion (email en uso, edad fuera de rango, contraseñas distintas o imagen invalida)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    // Método para actualizar el perfil del usuario autenticado
    public function update(updateUserFormRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $user = $this->update_user_service->update($user, $request->toDTO());

        return response()->json(new updateUserResource($user), 200);
    }

    #[OA\Delete(
        path: '/api/v1/profile',
        summary: 'Dar de baja el perfil del usuario autenticado',
        description: 'Baja logica: la cuenta y los movimientos se conservan. El token usado queda invalidado. Registrarse de nuevo con el mismo email reactiva la cuenta.',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Perfil dado de baja correctamente (sin contenido)'
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    // Método para dar de baja al usuario autenticado
    public function delete(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $this->delete_user_service->delete($user);
        Auth::guard('api')->logout();

        return response()->json(null, 204);
    }
}
