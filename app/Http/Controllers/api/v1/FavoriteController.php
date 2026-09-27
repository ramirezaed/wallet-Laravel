<?php

namespace App\Http\Controllers\api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Favorites\deleteFavoriteFormRequest;
use App\Http\Requests\Favorites\listFavoritesFormRequest;
use App\Http\Requests\Favorites\storeFavoriteFormRequest;
use App\Http\Resources\Favorites\FavoriteResource;
use App\Services\Favorites\deleteFavoriteService;
use App\Services\Favorites\listFavoritesService;
use App\Services\Favorites\storeFavoriteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class FavoriteController extends Controller
{
    public function __construct(
        private storeFavoriteService $store_favorite_service,
        private listFavoritesService $list_favorites_service,
        private deleteFavoriteService $delete_favorite_service,
    ) {}

    #[OA\Post(
        path: '/api/v1/cbu/{cbu}/users/{idUser}',
        summary: 'Guardar un CBU de terceros',
        description: 'Agrega un CBU a la lista de destinatarios frecuentes. {cbu} es el numero de CBU, no el id interno de la cuenta. {idUser} debe ser el id del usuario autenticado.',
        tags: ['CBU de terceros'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'cbu',
                description: 'Numero de CBU de 22 digitos',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9]{22}$', example: '0000000000000000000002')
            ),
            new OA\Parameter(
                name: 'idUser',
                description: 'Id del usuario autenticado',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'CBU guardado correctamente',
                content: new OA\JsonContent(ref: '#/components/schemas/Favorite')
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'El idUser no corresponde al usuario autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'No existe una cuenta con ese CBU',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'CBU con formato invalido, CBU propio o CBU ya guardado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function store(storeFavoriteFormRequest $request): JsonResponse
    {
        $favorite = $this->store_favorite_service->store($request->toDTO());

        // Cargamos las relaciones para que el Resource pueda mostrar el titular.
        $favorite->load('cuentaFavorita.usuario');

        return response()->json(new FavoriteResource($favorite), 201);
    }

    #[OA\Get(
        path: '/api/v1/cbu/users/{idUser}',
        summary: 'Listar los CBU de terceros guardados',
        description: 'Devuelve todos los CBU guardados por el usuario autenticado, con el titular de cada cuenta. No esta paginado.',
        tags: ['CBU de terceros'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'idUser',
                description: 'Id del usuario autenticado',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de CBU guardados',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Favorite')),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'El idUser no corresponde al usuario autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function index(listFavoritesFormRequest $request): AnonymousResourceCollection
    {
        $favorites = $this->list_favorites_service->list($request->toDTO());

        return FavoriteResource::collection($favorites);
    }

    #[OA\Delete(
        path: '/api/v1/cbu/{cbu}/users/{idUser}',
        summary: 'Quitar un CBU de terceros de la lista',
        description: 'Elimina solo la relacion guardada. La cuenta y el usuario tercero no se modifican.',
        tags: ['CBU de terceros'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'cbu',
                description: 'Numero de CBU de 22 digitos',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^[0-9]{22}$', example: '0000000000000000000002')
            ),
            new OA\Parameter(
                name: 'idUser',
                description: 'Id del usuario autenticado',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', minimum: 1, example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CBU quitado de la lista',
                content: new OA\JsonContent(
                    required: ['message'],
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'CBU removido de tu lista de favoritos.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Usuario no autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 403,
                description: 'El idUser no corresponde al usuario autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 404,
                description: 'Ese CBU no esta en la lista del usuario',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
            new OA\Response(
                response: 422,
                description: 'CBU con formato invalido',
                content: new OA\JsonContent(ref: '#/components/schemas/ApiError')
            ),
        ]
    )]
    public function destroy(deleteFavoriteFormRequest $request): JsonResponse
    {
        $this->delete_favorite_service->delete($request->toDTO());

        return response()->json([
            'message' => 'CBU removido de tu lista de favoritos.',
        ], 200);
    }
}
