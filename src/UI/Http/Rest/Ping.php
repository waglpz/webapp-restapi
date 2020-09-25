<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\UI\Http\Rest;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\BaseController;

use function Waglpz\Config\config;
use function Waglpz\Webapp\jsonResponse;

final class Ping extends BaseController
{
    /** @throws \JsonException */
    public function __invoke(RequestInterface $request): ResponseInterface
    {
        $data = [
            'time'       => \microtime(true),
            'apiVersion' => config('apiVersion'),
        ];

        return jsonResponse($data);
    }
}
