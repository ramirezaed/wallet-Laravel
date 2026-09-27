<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Resources\Auth\AccountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class AccountController extends Controller
{
    
    #[OA\Get(
        path: '/api/v1/account',
        summary: 'Consultar la cuenta del usuario autenticado',
        description: 'La cuenta se obtiene del token. No recibe user_id ni account_id.',
        tags: ['Cuenta'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta obtenida correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/Account')
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
            ),
        ]
    )]
    // Metodo para mostrar la cuenta del usuario autenticado
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $account = $user->cuenta;

        if (! $account) {
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 404);
        }

        return response()->json(
            new AccountResource($account),
            200
        );
    }

    #[OA\Post(
        path: '/api/v1/deposits',
        summary: 'Depositar dinero en la cuenta del usuario autenticado',
        description: 'Suma el monto al saldo y registra un movimiento de tipo deposito. Devuelve la cuenta con el saldo actualizado.',
        tags: ['Cuenta'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', format: 'float', minimum: 0.01, example: 50.00),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deposito realizado correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/Account')
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Cuenta no encontrada',
                content: new OA\JsonContent(ref: '#/components/schemas/AccountNotFoundError')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validacion (amount faltante, no numerico o menor a 0.01)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function store(StoreAccountRequest $accountRequest): JsonResponse
    {
        $user = Auth::guard('api')->user();

        $account = $user->cuenta;

        if (! $account) {
            return response()->json([
                'message' => 'Cuenta no encontrada',
            ], 404);
        }

        $depositAmount = $accountRequest->toDTO()->amount;

        DB::transaction(function () use ($account, $depositAmount): void {
        // Generar cambio en el saldo de la cuenta
            $account->saldo += $depositAmount;
            $account->save();
        // Generar registro de movimiento en movimientos
            $account->movimientos()->create([
                'tipo' => 'deposito',
                'monto' => $depositAmount,
            ]);
        });

        return response()->json(
            new AccountResource($account),
            200
        );
    }
  
}
