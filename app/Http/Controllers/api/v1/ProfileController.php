<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Auth\ProfileResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ProfileController extends Controller
{
    #[OA\Get(
        path: '/api/v1/profile',
        summary: 'Consultar el perfil del usuario autenticado',
        description: 'El usuario se obtiene del token. No recibe user_id y nunca devuelve la contraseña.',
        tags: ['Perfil'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil obtenido correctamente',
                content: new OA\JsonContent(
                    required: ['id', 'nombre', 'email', 'edad', 'imagen'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'nombre', type: 'string', example: 'Swagger Test'),
                        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'swagger@test.com'),
                        new OA\Property(property: 'edad', type: 'integer', example: 30),
                        new OA\Property(property: 'imagen', type: 'string', nullable: true, example: null),
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
    //Método para mostrar el perfil del usuario autenticado
    public function show(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        return response()->json(new ProfileResource($user), 200);
    }
}
