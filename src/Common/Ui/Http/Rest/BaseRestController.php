<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Common\Ui\Http\Rest;

use OpenApi\Attributes as OA;
use Waglpz\Webapp\BaseController;

#[OA\Info(version: '1.1.0', description: '', title: 'Blwdata REST API Documentation')]
#[OA\Server(url: '/api', description: 'Default server')]
#[OA\SecurityScheme(
    securityScheme: 'bearer',
    type: 'http',
    description: 'Bearer using JWT token authentication',
    bearerFormat: 'JWT',
    scheme: 'bearer',
)]
#[OA\Components(
    responses: [
        new OA\Response(
            response:    'BadRequest',
            description: 'When client sends invalid data.',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'detail', type: 'string', example: 'Ungültige Daten übermittelt.'),
                    new OA\Property(property: 'status', type: 'integer', example: 400),
                    new OA\Property(property: 'title', type: 'string', example: 'Bad Request'),
                    new OA\Property(property: 'type', type: 'string', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),
        new OA\Response(
            response:    'InternalServerError',
            description: 'When an unknown server error occurs.',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'detail',
                        type: 'string',
                        example: 'Unknown error on server Devteam helps.',
                    ),
                    new OA\Property(property: 'status', type: 'int', example: '500'),
                    new OA\Property(property: 'title', type: 'int', example: 'Internal Server Error'),
                    new OA\Property(property: 'type', type: 'int', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),
        new OA\Response(
            response:    'Unauthorized',
            description: 'JWT token is missing or invalid.',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'detail', type: 'string', example: 'User not authenticated.'),
                    new OA\Property(property: 'status', type: 'int', example: '401'),
                    new OA\Property(property: 'title', type: 'int', example: 'Unauthorized'),
                    new OA\Property(property: 'type', type: 'int', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),

        new OA\Response(
            response:    'Forbidden',
            description: 'User is authenticated but not allowed to perform the operation.',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'detail', type: 'string', example: 'Access forbidden insufficient role.'),
                    new OA\Property(property: 'status', type: 'int', example: '403'),
                    new OA\Property(property: 'title', type: 'int', example: 'Forbidden'),
                    new OA\Property(property: 'type', type: 'int', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),
        new OA\Response(
            response:    'NotFound',
            description: 'When the resource cannot be found at the moment.',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'detail', type: 'string', example: 'Resource not found.'),
                    new OA\Property(property: 'status', type: 'int', example: '404'),
                    new OA\Property(property: 'title', type: 'int', example: 'Not Found'),
                    new OA\Property(property: 'type', type: 'int', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),
        new OA\Response(
            response:    'Conflict',
            // phpcs:disable
            description: 'The response status code indicates a request conflict with the current state of the target resource.',
            // phpcs:enable
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'detail', type: 'string', example: 'Resource conflicting.'),
                    new OA\Property(property: 'status', type: 'int', example: '409'),
                    new OA\Property(property: 'title', type: 'int', example: 'Conflict'),
                    new OA\Property(property: 'type', type: 'int', example: 'about:blank'),
                ],
                type:       'object',
            ),
        ),
    ],
)]
#[OA\Get(
    path: '/error404',
    description: 'Get 404 Error from the unknown API endpoint',
    summary: 'Get 404 Error',
    tags: ['API'],
    responses: [
        new OA\Response(ref: '#/components/responses/BadRequest', response: 400),
        new OA\Response(ref: '#/components/responses/NotFound', response: 404),
    ],
)]
#[OA\Get(
    path: '/ping',
    description: 'Get information about the API',
    summary: 'Ping the API',
    tags: ['API'],
    responses: [
        new OA\Response(
            response:    200,
            description: 'API information',
            content:     new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property:    'time',
                        description: 'The server time as a timestamp',
                        type:        'integer',
                        example:     1524788100.000000,
                    ),
                    new OA\Property(
                        property:    'apiVersion',
                        description: 'The latest API version',
                        type:        'string',
                        example:     '1.0.3',
                    ),
                ],
                type:       'object',
            ),
        ),
        new OA\Response(ref: '#/components/responses/BadRequest', response: 400),
        new OA\Response(ref: '#/components/responses/NotFound', response: 404),
    ],
)]
#[OA\Schema(
    schema: 'listMeta',
    properties: [
        new OA\Property(
            property: 'itemsPerPage',
            type:     'integer',
        ),
        new OA\Property(
            property: 'totalPages',
            type:     'integer',
        ),
        new OA\Property(
            property: 'totalItems',
            type:     'integer',
        ),
        new OA\Property(
            property:   '_links',
            properties: [
                new OA\Property(
                    property: 'first',
                    type:     'string',
                    example:  '/resource?page=1&limit=10',
                ),
                new OA\Property(
                    property: 'previous',
                    type:     'string',
                    example:  '/resource?page=11&limit=10',
                ),
                new OA\Property(
                    property: 'self',
                    type:     'string',
                    example:  '/resource?page=12&limit=10',
                ),
                new OA\Property(
                    property: 'next',
                    type:     'string',
                    example:  '/resource?page=13&limit=10',
                ),
                new OA\Property(
                    property: 'last',
                    type:     'string',
                    example:  '/resource?page=15&limit=10',
                ),
            ],
            type:       'object',
        ),
    ],
    type: 'object',
)]
abstract class BaseRestController extends BaseController
{
}
