<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ListAdminAccountRequest;
use App\Http\Requests\Admin\StoreAdminAccountRequest;
use App\Http\Requests\Admin\UpdateAdminAccountRequest;
use App\Http\Resources\Admin\AdminAccountResource;
use App\Models\Cuenta;
use App\Services\Account\createAccountService;
use App\Services\Admin\ListAdminAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class AdminAccountController extends Controller
{
    public function __construct(
        private createAccountService $createAccountService,
        private ListAdminAccountService $listAdminAccountService,
        // private deleteFavoriteService $delete_favorite_service,
    ) {}

    
    #[OA\Get(
        path: '/api/v1/admin/accounts',
        summary: 'Listar cuentas o buscar una por CBU',
        description: 'Sin el parametro cbu: listado paginado de cuentas activas de usuarios activos, ordenado por nombre del titular (por defecto ascendente). Con el parametro cbu: devuelve solo esa cuenta, sin paginar, e ignora page, per_page y orden. Requiere rol administrador.',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cbu', description: 'Si se envia, busca una cuenta por su numero de CBU en lugar de listar', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: '0000000000000000000002')),
            new OA\Parameter(name: 'page', description: 'Numero de pagina', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)),
            new OA\Parameter(name: 'per_page', description: 'Elementos por pagina (por defecto 15)', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, example: 15)),
            new OA\Parameter(name: 'orden', description: 'Orden por nombre del titular (por defecto asc)', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], example: 'asc')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado (sin cbu) o una sola cuenta (con cbu)',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(
                            properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminAccount')),
                                new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                                new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                            ],
                            type: 'object'
                        ),
                        new OA\Schema(ref: '#/components/schemas/AdminAccount'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'No existe una cuenta con el CBU indicado', content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')),
            new OA\Response(response: 422, description: 'per_page fuera de 1 a 100 u orden distinto de asc/desc', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    public function index(ListAdminAccountRequest $request): JsonResponse|AnonymousResourceCollection
    {
        if ($request->filled('cbu')) {
            $cuenta = Cuenta::with('usuario')
                ->where('cbu', $request->string('cbu'))
                ->first();

            if ($cuenta === null) {
                return response()->json([
                    'message' => 'No se encontró una cuenta con el CBU indicado.',
                ], 404);
            }

            return response()->json(new AdminAccountResource($cuenta), 200);
        }

        $listAccount = $this->listAdminAccountService->list($request->toDTO());

        return AdminAccountResource::collection($listAccount);
    }

    #[OA\Post(
        path: '/api/v1/admin/accounts',
        summary: 'Crear una cuenta para un usuario sin cuenta',
        description: 'Crea la cuenta con CBU unico y saldo 0.00. Si el usuario tenia una cuenta dada de baja, la reactiva (con su saldo anterior) en lugar de crear otra. No genera depositos ni movimientos.',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['usuario_id'],
                properties: [
                    new OA\Property(property: 'usuario_id', description: 'Usuario activo que no tenga una cuenta activa', type: 'integer', example: 2),
                    new OA\Property(property: 'tipo', type: 'string', enum: ['ahorro', 'corriente'], nullable: true, example: 'ahorro'),
                    new OA\Property(property: 'moneda', type: 'string', enum: ['ARS', 'USD'], nullable: true, example: 'ARS'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cuenta creada', content: new OA\JsonContent(ref: '#/components/schemas/AdminAccount')),
            new OA\Response(response: 200, description: 'Cuenta dada de baja reactivada', content: new OA\JsonContent(ref: '#/components/schemas/AdminAccount')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'Usuario inexistente o dado de baja, usuario que ya tiene cuenta activa, o tipo/moneda invalidos', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    // Almacena una nueva cuenta desde la administración de usuario sin cuenta
    public function store(StoreAdminAccountRequest $request): JsonResponse
    {
        $deletedAccount = Cuenta::withTrashed()
            ->where('usuario_id', $request->integer('usuario_id'))
            ->first();

        if ($deletedAccount?->trashed()) {
            $deletedAccount->restore();
            $deletedAccount->update($request->validated());
            $deletedAccount->load('usuario');

            return response()->json(new AdminAccountResource($deletedAccount), 200);
        }

        $account = $this->createAccountService->create($request->toDTO());

        return response()->json(
            new AdminAccountResource($account->load('usuario')),
            201
        );
    }

    #[OA\Get(
        path: '/api/v1/admin/accounts/{cuenta}',
        summary: 'Consultar una cuenta',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cuenta', description: 'Id interno de la cuenta', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 3)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cuenta encontrada', content: new OA\JsonContent(ref: '#/components/schemas/AdminAccount')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'La cuenta no existe o fue dada de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    // Muestra información de una cuenta
    public function show(Cuenta $cuenta): JsonResponse
    {
        $cuenta->load('usuario');

        return response()->json(new AdminAccountResource($cuenta), 200);
    }

    #[OA\Put(
        path: '/api/v1/admin/accounts/{cuenta}',
        operationId: 'adminAccountsUpdatePut',
        summary: 'Actualizar tipo o moneda de una cuenta',
        description: 'Solo permite modificar tipo y moneda. No se puede cambiar el titular ni el saldo.',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cuenta', description: 'Id interno de la cuenta', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 3)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo', type: 'string', enum: ['ahorro', 'corriente'], example: 'corriente'),
                    new OA\Property(property: 'moneda', type: 'string', enum: ['ARS', 'USD'], example: 'USD'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cuenta actualizada', content: new OA\JsonContent(ref: '#/components/schemas/AdminAccount')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'La cuenta no existe o fue dada de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'tipo o moneda con valor no permitido', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    #[OA\Patch(
        path: '/api/v1/admin/accounts/{cuenta}',
        operationId: 'adminAccountsUpdatePatch',
        summary: 'Actualizar tipo o moneda de una cuenta (equivalente a PUT)',
        description: 'Mismo comportamiento que PUT: solo permite modificar tipo y moneda.',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cuenta', description: 'Id interno de la cuenta', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 3)),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'tipo', type: 'string', enum: ['ahorro', 'corriente'], example: 'corriente'),
                    new OA\Property(property: 'moneda', type: 'string', enum: ['ARS', 'USD'], example: 'USD'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cuenta actualizada', content: new OA\JsonContent(ref: '#/components/schemas/AdminAccount')),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'La cuenta no existe o fue dada de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'tipo o moneda con valor no permitido', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
        ]
    )]
    // Actualizar cuenta desde la administración
    public function update(UpdateAdminAccountRequest $request, Cuenta $cuenta): JsonResponse
    {
        $cuenta->update($request->validated());
        $cuenta->load('usuario');

        return response()->json(new AdminAccountResource($cuenta), 200);
    }

    #[OA\Delete(
        path: '/api/v1/admin/accounts/{cuenta}',
        summary: 'Dar de baja una cuenta',
        description: 'Baja logica. Solo se permite si el saldo es 0. Los movimientos de la cuenta se conservan.',
        tags: ['Administración: cuentas'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'cuenta', description: 'Id interno de la cuenta', in: 'path', required: true, schema: new OA\Schema(type: 'integer', minimum: 1, example: 3)),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Cuenta dada de baja (sin contenido)'),
            new OA\Response(response: 401, description: 'Usuario no autenticado', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 403, description: 'Se requiere rol administrador', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 404, description: 'La cuenta no existe o ya fue dada de baja', content: new OA\JsonContent(ref: '#/components/schemas/ApiError')),
            new OA\Response(response: 422, description: 'La cuenta tiene saldo distinto de 0, o el usuario asociado no existe', content: new OA\JsonContent(ref: '#/components/schemas/BusinessRuleError')),
        ]
    )]
    public function destroy(Cuenta $cuenta): JsonResponse
    {
        if (! $cuenta->usuario()->withTrashed()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar la cuenta porque el usuario asociado no existe.',
            ], 422);
        }

        if ((float) $cuenta->saldo !== 0.0) {
            return response()->json([
                'message' => 'No se puede eliminar la cuenta porque su saldo debe ser 0.',
            ], 422);
        }

        $cuenta->delete();

        return response()->json([
            'message' => 'Cuenta eliminada correctamente',
        ], 204);
    }
}
