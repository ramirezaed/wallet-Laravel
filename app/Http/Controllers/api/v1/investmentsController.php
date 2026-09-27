<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Investments\fixedTerRequest;
use App\Http\Resources\investments\fixedTermResource;
use App\Services\Investments\fixedTermServices;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class investmentsController extends Controller
{
    public  function __construct(private fixedTermServices $fixed_term_services) {}

    #[OA\Post(
        path: '/api/v1/investments/fixed-term/simulate',
        summary: 'Simular un plazo fijo',
        description: 'Calcula el rendimiento estimado con interes simple. Formula: interes = monto x 0.30 x (plazo / 365). La TNA es fija (30%) y no puede elegirse. Es solo una simulacion: no descuenta saldo ni crea movimientos.',
        tags: ['Plazo fijo'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['monto', 'plazo'],
                properties: [
                    new OA\Property(property: 'monto', description: 'Monto a invertir. Debe ser un numero entero.', type: 'integer', minimum: 1, example: 10000),
                    new OA\Property(property: 'plazo', description: 'Plazo en dias', type: 'integer', minimum: 30, maximum: 365, example: 30),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Simulacion calculada',
                content: new OA\JsonContent(
                    required: ['Fecha Inicio', 'Fecha Fin', 'Monto Invertido', 'Interes ganado', 'Total'],
                    properties: [
                        new OA\Property(property: 'Fecha Inicio', description: 'Formato Y-m-d H:i:s', type: 'string', example: '2026-09-24 10:00:00'),
                        new OA\Property(property: 'Fecha Fin', description: 'Formato Y-m-d H:i:s', type: 'string', example: '2026-10-24 10:00:00'),
                        new OA\Property(property: 'Monto Invertido', type: 'integer', example: 10000),
                        new OA\Property(property: 'Interes ganado', description: 'Redondeado a 2 decimales', type: 'number', format: 'float', example: 246.58),
                        new OA\Property(property: 'Total', description: 'Monto invertido + interes, redondeado a 2 decimales', type: 'number', format: 'float', example: 10246.58),
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
                description: 'Error de validacion (monto no entero o menor a 1, plazo fuera de 30 a 365 dias)',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function store(fixedTerRequest $request): JsonResponse
    {
        $fixedTerm = $this->fixed_term_services->create($request->toDTO());
        return response()->json(new fixedTermResource($fixedTerm), 200);
    }
}
