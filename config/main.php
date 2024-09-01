<?php

declare(strict_types=1);

use Waglpz\Webapp\ExceptionHandler;

\Locale::setDefault('de_DE.utf8');

return [
    'apiVersion'          => '0.1.0',
    'logErrorsDir'        => $_SERVER['LOG_DIR'],
//    'db'                  => include 'db.php',
    'router'              => include 'router.php',
//    'logger'              => include 'logger.php',
    'view'                => [
        'templates'  => \dirname(__DIR__) . '/templates/',
        'attributes' => [/*add hier if needed*/],
        'layout'     => null,
    ],
    'exception_handler'   => ExceptionHandler::class,
    'swagger_scheme_file' => __DIR__ . '/../swagger.json',
];
