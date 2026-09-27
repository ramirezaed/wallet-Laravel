<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Wallet API',
    description: 'Documentación de la API de Wallet. Las rutas privadas requieren un JWT: obtenelo en POST /api/v1/auth/login y cargalo con el botón Authorize.'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT'
)]
#[OA\Schema(
    schema: 'Account',
    required: ['cbu', 'saldo', 'tipo', 'moneda'],
    properties: [
        new OA\Property(property: 'cbu', type: 'string', example: '0000000000000000000001'),
        new OA\Property(property: 'saldo', type: 'string', example: '1550.50'),
        new OA\Property(property: 'tipo', type: 'string', example: 'ahorro'),
        new OA\Property(property: 'moneda', type: 'string', example: 'ARS'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ApiError',
    description: 'Formato comun de error de la API (401, 403, 404 y 422). El campo status repite el codigo HTTP de la respuesta.',
    required: ['message', 'status', 'error'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'No autenticado'),
        new OA\Property(property: 'status', type: 'integer', example: 401),
        new OA\Property(property: 'error', type: 'object'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AccountNotFoundError',
    description: 'Respuesta 404 propia de los endpoints de cuenta, deposito y transferencia. No incluye status ni error.',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Cuenta no encontrada'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'BusinessRuleError',
    description: 'Error de regla de negocio que el endpoint responde directamente (por ejemplo, saldo insuficiente). No incluye status ni error.',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Saldo insuficiente para realizar la transferencia'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Movement',
    required: ['id', 'tipo', 'monto', 'cbu_contraparte', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'tipo', type: 'string', enum: ['deposito', 'transferencia_salida', 'transferencia_entrada'], example: 'deposito'),
        new OA\Property(property: 'monto', type: 'string', example: '150.00'),
        new OA\Property(property: 'cbu_contraparte', description: 'CBU de la otra cuenta en transferencias. Es null en los depositos.', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-09-22T03:35:00.000000Z'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationLinks',
    description: 'Enlaces de navegacion del paginador de Laravel.',
    properties: [
        new OA\Property(property: 'first', type: 'string', example: 'http://localhost:8000/api/v1/movements?page=1'),
        new OA\Property(property: 'last', type: 'string', example: 'http://localhost:8000/api/v1/movements?page=3'),
        new OA\Property(property: 'prev', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'next', type: 'string', nullable: true, example: 'http://localhost:8000/api/v1/movements?page=2'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    description: 'Metadatos del paginador de Laravel.',
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'last_page', type: 'integer', example: 3),
        new OA\Property(property: 'links', type: 'array', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'path', type: 'string', example: 'http://localhost:8000/api/v1/movements'),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'to', type: 'integer', nullable: true, example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 42),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Favorite',
    required: ['id', 'cbu', 'titular'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'cbu', type: 'string', example: '0000000000000000000002'),
        new OA\Property(property: 'titular', description: 'Nombre del dueño de la cuenta guardada', type: 'string', nullable: true, example: 'Beto'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'AdminUser',
    required: ['id', 'nombre', 'email', 'edad', 'imagen', 'rol', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'nombre', type: 'string', example: 'Ana'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'ana@test.com'),
        new OA\Property(property: 'edad', type: 'integer', example: 25),
        new OA\Property(property: 'imagen', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'rol', type: 'string', enum: ['usuario', 'administrador'], example: 'usuario'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-09-22T03:35:00.000000Z'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'AdminAccount',
    required: ['id', 'usuario_id', 'cbu', 'saldo', 'tipo', 'moneda', 'nombre_usuario', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
        new OA\Property(property: 'cbu', type: 'string', example: '0000000000000000000002'),
        new OA\Property(property: 'saldo', description: 'Siempre con dos decimales', type: 'string', example: '1550.50'),
        new OA\Property(property: 'tipo', type: 'string', enum: ['ahorro', 'corriente'], example: 'ahorro'),
        new OA\Property(property: 'moneda', type: 'string', enum: ['ARS', 'USD'], example: 'ARS'),
        new OA\Property(property: 'nombre_usuario', description: 'Nombre del titular', type: 'string', nullable: true, example: 'Ana'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-09-22T03:35:00.000000Z'),
    ],
    type: 'object'
)]

#[OA\Schema(
    schema: 'AdminMovement',
    required: ['id', 'tipo', 'monto', 'cbu_contraparte', 'fecha', 'cuenta'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'tipo', type: 'string', enum: ['deposito', 'transferencia_salida', 'transferencia_entrada'], example: 'deposito'),
        new OA\Property(property: 'monto', description: 'Siempre con dos decimales', type: 'string', example: '150.00'),
        new OA\Property(property: 'cbu_contraparte', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'fecha', description: 'Fecha de creacion del movimiento', type: 'string', format: 'date-time', example: '2026-09-22T03:35:00.000000Z'),
        new OA\Property(
            property: 'cuenta',
            description: 'Cuenta a la que pertenece el movimiento',
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 3),
                new OA\Property(property: 'cbu', type: 'string', example: '0000000000000000000002'),
                new OA\Property(property: 'tipo', type: 'string', example: 'ahorro'),
                new OA\Property(property: 'moneda', type: 'string', example: 'ARS'),
                new OA\Property(property: 'usuario_id', type: 'integer', example: 2),
            ],
            type: 'object'
        ),
    ],
    type: 'object'
)]
abstract class Controller
{
    //
}
