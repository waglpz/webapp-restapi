<?php

declare(strict_types=1);

use Waglpz\Webapp\RestApi\UI\Http\Web\SwaggerUI;

use function Waglpz\Config\config;

return [
    SwaggerUI::class              => [
        'shared'          => true,
        'constructParams' => [config('swagger_scheme_file')],
    ],
];
