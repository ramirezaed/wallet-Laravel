<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Movements\getTransactionsResource;
use App\Services\Movements\GetTransactionsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use App\Models\Cuenta;
use App\Models\Movimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class MovementsController extends Controller
{
    //
    public function __construct(private GetTransactionsService $get_transactios_service) {}

    #[OA\Get(
        path: '/api/v1/movements',
        summary: 'Listar los movimientos de la cuenta autenticada',
        description: 'Devuelve solo los movimientos de la cuenta del usuario del token. Paginado de a 15 elementos, ordenado por fecha de creacion descendente (lo mas reciente primero). La cantidad por pagina y el orden no son configurables.',
        tags: ['Movimientos'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'page',
                description: 'Numero de pagina',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Listado paginado de movimientos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Movement')),
                        new OA\Property(property: 'links', ref: '#/components/schemas/PaginationLinks'),
                        new OA\Property(property: 'meta', ref: '#/components/schemas/PaginationMeta'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $movements = $this->get_transactios_service->GetTransactios();
        return getTransactionsResource::collection($movements);
    }

    #[OA\Post(
        path: '/api/v1/transfers',
        summary: 'Transferir dinero a otro CBU',
        description: 'El origen es siempre la cuenta del usuario autenticado. Descuenta y acredita el mismo monto dentro de una transaccion y registra un movimiento en cada cuenta.',
        tags: ['Movimientos'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['destination_cbu', 'amount'],
                properties: [
                    new OA\Property(property: 'destination_cbu', type: 'string', example: '0000000000000000000002'),
                    new OA\Property(property: 'amount', description: 'Debe ser mayor a 0', type: 'number', format: 'float', example: 100.00),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Transferencia realizada',
                content: new OA\JsonContent(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Transferencia realizada con éxito'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'La cuenta de origen o de destino no existe',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validacion (ApiError) o regla de negocio: saldo insuficiente o transferencia a la propia cuenta (BusinessRuleError)',
                content: new OA\JsonContent(
                    oneOf: [
                        new OA\Schema(ref: '#/components/schemas/ApiError'),
                        new OA\Schema(ref: '#/components/schemas/BusinessRuleError'),
                    ]
                )
            ),
        ]
    )]
    public function transfer(Request $request): JsonResponse
    {
        $request->validate([
            'destination_cbu' => ['required', 'string'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);

        $user_origen = Auth::guard('api')->user();
        $account_origen = $user_origen?->cuenta;
        $account_destino = Cuenta::where('cbu', $request->destination_cbu)->first();

        if (! $account_origen || ! $account_destino) {
            return response()->json([
                'message' => 'la cuenta de origen o destino no existe',
            ], 404);
        }

        if ($account_origen->cbu === $request->destination_cbu) {
            return response()->json([
                'message' => 'No se puede transferir a la misma cuenta',
            ], 422);
        }

        if ($account_origen->saldo < $request->amount) {
            return response()->json([
                'message' => 'Saldo insuficiente para realizar la transferencia',
            ], 422);
        }

        DB::transaction(function () use ($account_origen, $account_destino, $request): void {
            // Restar el monto de la cuenta de origen
            $account_origen->saldo -= $request->amount;
            $account_origen->save();

            // Agregar el monto a la cuenta de destino
            $account_destino->saldo += $request->amount;
            $account_destino->save();

            // Registrar el movimiento en la cuenta de origen
            $account_origen->movimientos()->create([
                'tipo' => 'transferencia_salida',
                'monto' => $request->amount,
                'cbu_contraparte' => $account_destino->cbu,
            ]);

            // Registrar el movimiento en la cuenta de destino
            $account_destino->movimientos()->create([
                'tipo' => 'transferencia_entrada',
                'monto' => $request->amount,
                'cbu_contraparte' => $account_origen->cbu,
            ]);
        });

        return response()->json([
            'message' => 'Transferencia realizada con éxito',
        ], 200);
    }
}
