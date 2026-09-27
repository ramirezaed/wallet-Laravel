<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Http\Resources\Admin\AdminUserResource;
use App\Models\User;
use App\Services\Admin\CreateAdminUserService;
use App\Services\Admin\DeleteAdminUserService;
use App\Services\Admin\UpdateAdminUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    public function __construct(
        private CreateAdminUserService $createAdminUserService,
        private UpdateAdminUserService $updateAdminUserService,
        private DeleteAdminUserService $deleteAdminUserService,
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/users',
        summary: 'Listar usuarios',
        description: 'Listado paginado de usuarios activos, ordenado por fecha de creacion. Los usuarios dados de baja no aparecen. Requiere rol administrador.',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', description: 'Numero de pagina', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Elementos por pagina (por defecto 15)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 15)),
            new OA\Parameter(name: 'orden', description: 'Orden por fecha de creacion (por defecto desc)', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de usuarios',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminUser')),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'per_page fuera de 1 a 100 u orden distinto de asc/desc', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'orden' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $orden = $request->input('orden', 'desc');
        $perPage = $request->integer('per_page', 15);

        $usuarios = User::query()
            ->orderBy('created_at', $orden)
            ->paginate($perPage);

        return AdminUserResource::collection($usuarios);
    }

    #[OA\Post(
        path: '/api/v1/admin/users',
        summary: 'Crear un usuario',
        description: 'Crea el usuario con rol "usuario" y su cuenta asociada con saldo 0.00. El rol no puede elegirse. Se envia como multipart/form-data porque acepta una imagen.',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['nombre', 'email', 'password', 'edad'],
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Carla'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'carla@test.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret123'),
                        new OA\Property(property: 'edad', type: 'integer', minimum: 18, maximum: 120, example: 28),
                        new OA\Property(property: 'imagen', description: 'Opcional. JPG, JPEG, PNG o WebP de hasta 2 MB', type: 'string', format: 'binary'),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Usuario creado', content: new OA\JsonContent(ref: '#/components/schemas/AdminUser')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Error de validacion (email en uso, edad fuera de rango, contraseña corta o imagen invalida)', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function store(StoreAdminUserRequest $request): JsonResponse
    {
        $usuario = $this->createAdminUserService->create($request->toDTO());

        return response()->json(
            new AdminUserResource($usuario),
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/users/{user}',
        summary: 'Consultar un usuario',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', description: 'Id del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 2)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario encontrado', content: new OA\JsonContent(ref: '#/components/schemas/AdminUser')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El usuario no existe o fue dado de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function show(User $user): JsonResponse
    {
        return response()->json(
            new AdminUserResource($user),
            200
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/users/{user}',
        operationId: 'adminUsersUpdatePut',
        summary: 'Actualizar un usuario',
        description: 'Todos los campos son opcionales: solo se modifican los que se envian. El rol no puede modificarse. Se envia como multipart/form-data porque acepta una imagen.',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', description: 'Id del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 2)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Carla Actualizada'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'carla.nueva@test.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'nueva1234'),
                        new OA\Property(property: 'edad', type: 'integer', minimum: 18, maximum: 120, example: 29),
                        new OA\Property(property: 'imagen', description: 'JPG, JPEG, PNG o WebP de hasta 2 MB', type: 'string', format: 'binary'),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado', content: new OA\JsonContent(ref: '#/components/schemas/AdminUser')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El usuario no existe o fue dado de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Error de validacion (email en uso por otro usuario, edad fuera de rango, contraseña corta o imagen invalida)', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    #[OA\Patch(
        path: '/api/v1/admin/users/{user}',
        operationId: 'adminUsersUpdatePatch',
        summary: 'Actualizar un usuario (equivalente a PUT)',
        description: 'Mismo comportamiento que PUT: todos los campos son opcionales y el rol no puede modificarse.',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', description: 'Id del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 2)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'nombre', type: 'string', maxLength: 255, example: 'Carla Actualizada'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', maxLength: 255, example: 'carla.nueva@test.com'),
                        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'nueva1234'),
                        new OA\Property(property: 'edad', type: 'integer', minimum: 18, maximum: 120, example: 29),
                        new OA\Property(property: 'imagen', description: 'JPG, JPEG, PNG o WebP de hasta 2 MB', type: 'string', format: 'binary'),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado', content: new OA\JsonContent(ref: '#/components/schemas/AdminUser')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El usuario no existe o fue dado de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Error de validacion', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function update(UpdateAdminUserRequest $request, User $user): JsonResponse
    {
        $usuario = $this->updateAdminUserService->update($user, $request->toDTO());

        return response()->json(
            new AdminUserResource($usuario),
            200
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/users/{user}',
        summary: 'Dar de baja un usuario',
        description: 'Baja logica: el usuario deja de aparecer en el listado, pero su cuenta y sus movimientos se conservan. Dar de baja un usuario ya dado de baja devuelve 404.',
        tags: ['Administración: usuarios'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'user', description: 'Id del usuario', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 2)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario dado de baja',
                content: new OA\JsonContent(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Usuario eliminado correctamente'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El usuario no existe o ya fue dado de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function destroy(User $user): JsonResponse
    {
        $this->deleteAdminUserService->delete($user);

        return response()->json([
            'message' => 'Usuario eliminado correctamente',
        ], 200);
    }
}
