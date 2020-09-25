<?php

declare(strict_types=1);

\Locale::setDefault('de_DE.utf8');

return [
    'apiVersion'          => '0.1.0',
    'logErrorsDir'        => '/tmp',
    'router'              => include 'router.restapi.php',
    'view'                => [
        'templates'  => \dirname(__DIR__) . '/templates/',
        'attributes' => [/*add hier if needed*/],
        'layout'     => null,
    ],
    'swagger_scheme_file' => __DIR__ . '/../swagger.json',
];
