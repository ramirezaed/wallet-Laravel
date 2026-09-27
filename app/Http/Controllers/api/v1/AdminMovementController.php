<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Movements\AdminMovementResource;
use App\Models\Movimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Http\Requests\Admin\StoreAdminMovementRequest;
use App\Http\Requests\Admin\UpdateAdminMovementRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminMovementController extends Controller
{
    #[OA\Get(
        path: '/api/v1/admin/movements',
        summary: 'Listar movimientos',
        description: 'Listado paginado de movimientos de todas las cuentas, ordenado por fecha de creacion (por defecto descendente). Se puede filtrar por cuenta o por usuario. Requiere rol administrador.',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cuenta_id', description: 'Filtra por el id interno de una cuenta', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 3)),
            new OA\Parameter(name: 'usuario_id', description: 'Filtra por los movimientos de la cuenta de un usuario', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 2)),
            new OA\Parameter(name: 'page', description: 'Numero de pagina', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Elementos por pagina (por defecto 15)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 15)),
            new OA\Parameter(name: 'orden', description: 'Orden por fecha de creacion (por defecto desc)', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'desc')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de movimientos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminMovement')),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'cuenta_id o usuario_id inexistentes, per_page fuera de 1 a 100 u orden distinto de asc/desc', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate([
            'cuenta_id' => ['nullable', 'integer', 'exists:cuentas,id'],
            'usuario_id' => ['nullable', 'integer', 'exists:users,id'],
            'orden' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        //Equivale a obtener cada movimiento junto con:
        //Movimiento -> Cuenta -> Usuario
        $query = Movimiento::query()->with('cuenta.usuario');

        //Filtra por cuenta_id si se proporciona en la solicitud
        if ($request->filled('cuenta_id')) {
            $query->where('cuenta_id', $request->integer('cuenta_id'));
        }

        //devuelve los movimientos de todas las cuentas pertenecientes al usuario
        if ($request->filled('usuario_id')) {
            $query->whereHas('cuenta', function ($query) use ($request) {
                $query->where('usuario_id', $request->integer('usuario_id'));
            });
        }

        $orden = $request->input('orden', 'desc');
        $perPage = $request->integer('per_page', 15);

        $movimientos = $query
            ->orderBy('created_at', $orden)
            ->paginate($perPage);

        return AdminMovementResource::collection($movimientos);
    }

    #[OA\Post(
        path: '/api/v1/admin/movements',
        summary: 'Crear un movimiento en el historial',
        description: 'Registra un movimiento en el historial de una cuenta. NO modifica el saldo: no equivale a un deposito ni a una transferencia real.',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['cuenta_id', 'tipo', 'monto'],
                properties: [
                    new OA\Property(property: 'cuenta_id', description: 'Id interno de la cuenta', type: 'integer', example: 3),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['deposito', 'transferencia_salida', 'transferencia_entrada'], example: 'deposito'),
                    new OA\Property(property: 'monto', description: 'Debe ser mayor a 0', type: 'number', format: 'float', example: 150.00),
                    new OA\Property(property: 'cbu_contraparte', description: 'Opcional. Exactamente 22 caracteres', type: 'string', nullable: true, example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Movimiento creado', content: new OA\JsonContent(ref: '#/components/schemas/AdminMovement')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Cuenta inexistente, tipo no permitido, monto menor o igual a 0 o cbu_contraparte que no tiene 22 caracteres', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function store(StoreAdminMovementRequest $request): JsonResponse
    {
        $movimiento = Movimiento::create($request->validated());

        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/movements/{movimiento}',
        summary: 'Consultar un movimiento',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'movimiento', description: 'Id del movimiento', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 10)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Movimiento encontrado', content: new OA\JsonContent(ref: '#/components/schemas/AdminMovement')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El movimiento no existe', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function show(Movimiento $movimiento): JsonResponse
    {
        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            200
        );
    }

    #[OA\Put(
        path: '/api/v1/admin/movements/{movimiento}',
        operationId: 'adminMovementsUpdatePut',
        summary: 'Actualizar un movimiento del historial',
        description: 'Todos los campos son opcionales. Modifica solo el historial: el saldo de la cuenta NO se recalcula.',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'movimiento', description: 'Id del movimiento', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 10)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cuenta_id', description: 'Id interno de la cuenta', type: 'integer', example: 3),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['deposito', 'transferencia_salida', 'transferencia_entrada'], example: 'deposito'),
                    new OA\Property(property: 'monto', description: 'Debe ser mayor a 0', type: 'number', format: 'float', example: 200.00),
                    new OA\Property(property: 'cbu_contraparte', description: 'Exactamente 22 caracteres', type: 'string', nullable: true, example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Movimiento actualizado', content: new OA\JsonContent(ref: '#/components/schemas/AdminMovement')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El movimiento no existe', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Error de validacion', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    #[OA\Patch(
        path: '/api/v1/admin/movements/{movimiento}',
        operationId: 'adminMovementsUpdatePatch',
        summary: 'Actualizar un movimiento del historial (equivalente a PUT)',
        description: 'Mismo comportamiento que PUT: el saldo de la cuenta NO se recalcula.',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'movimiento', description: 'Id del movimiento', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 10)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'cuenta_id', description: 'Id interno de la cuenta', type: 'integer', example: 3),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['deposito', 'transferencia_salida', 'transferencia_entrada'], example: 'deposito'),
                    new OA\Property(property: 'monto', description: 'Debe ser mayor a 0', type: 'number', format: 'float', example: 200.00),
                    new OA\Property(property: 'cbu_contraparte', description: 'Exactamente 22 caracteres', type: 'string', nullable: true, example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Movimiento actualizado', content: new OA\JsonContent(ref: '#/components/schemas/AdminMovement')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El movimiento no existe', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Error de validacion', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function update(UpdateAdminMovementRequest $request, Movimiento $movimiento): JsonResponse
    {
        $movimiento->update($request->validated());

        $movimiento->load('cuenta.usuario');

        return response()->json(
            new AdminMovementResource($movimiento),
            200
        );
    }

    #[OA\Delete(
        path: '/api/v1/admin/movements/{movimiento}',
        summary: 'Eliminar un movimiento del historial',
        description: 'Borrado DEFINITIVO: a diferencia de usuarios y cuentas, no es una baja logica y no se puede deshacer. El saldo de la cuenta NO se recalcula.',
        tags: ['Administración: movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'movimiento', description: 'Id del movimiento', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 10)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimiento eliminado',
                content: new OA\JsonContent(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Movimiento eliminado correctamente'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'El movimiento no existe', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function destroy(Movimiento $movimiento): JsonResponse
    {
        $movimiento->delete();

        return response()->json([
            'message' => 'Movimiento eliminado correctamente',
        ], 200);
    }

}
