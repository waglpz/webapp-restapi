<?php

declare(strict_types=1);

use Waglpz\Webapp\RestApi\App;

return [
    '$DefaultWebApp'                 => [
        'shared' => true,
        'instanceOf' =>  App::class,
    ],
];
