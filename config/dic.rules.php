<?php

declare(strict_types=1);

use Aidphp\Http\Emitter;
use Aidphp\Http\ServerRequestFactory;
use Dice\Dice;
use FastRoute\Dispatcher;
use Interop\Http\EmitterInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\RestApi\App;
use Waglpz\Webapp\RestApi\UI\Http\Web\SwaggerUI;

use function FastRoute\simpleDispatcher;
use function Waglpz\Config\config;

return [
    '*'                           => [
        'substitutions' => [
            Dispatcher::class       => [
                Dice::INSTANCE => static function (): Dispatcher {
                    $routerCollector = config('router');
                    \assert(\is_callable($routerCollector));

                    return simpleDispatcher($routerCollector);
                },
            ],
            PhpRenderer::class      => '$DefaultViewRenderer',
            EmitterInterface::class => Emitter::class,
        ],
    ],
    '$DefaultWebApp'                 => [
        'shared' => true,
        'instanceOf' =>  App::class,
    ],
    ServerRequestInterface::class => [
        'shared'     => true,
        'instanceOf' => ServerRequestFactory::class,
        'call'       => [['createServerRequestFromGlobals', [], Dice::CHAIN_CALL]],
    ],
    '$DefaultViewRenderer'        => [
        'shared'          => true,
        'instanceOf'      => PhpRenderer::class,
        'constructParams' => [
            /** @phpstan-ignore-next-line */
            config('view')['templates'],
            /** @phpstan-ignore-next-line */
            config('view')['attributes'],
            /** @phpstan-ignore-next-line */
            config('view')['layout'],
        ],
    ],
    // Controllers with specific params
    SwaggerUI::class              => [
        'shared'          => true,
        'constructParams' => [config('swagger_scheme_file')],
    ],
];
