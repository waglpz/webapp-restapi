<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Tests\UI;

use Aidphp\Http\ServerRequest;
use Aidphp\Http\Stream;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Waglpz\Webapp\Tests\UI\WebTestCase;

abstract class RestTestCase extends WebTestCase
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function restGetResponse(string $uri): ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('GET', $uri, ['content-type' => 'application/json']);

        $response = ($app->handleRequest($request))();
        $response->getBody()->rewind();

        return $response;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function restPostResponse(string $uri, string|null $body = null): ResponseInterface
    {
        $app     = $this->createApp();
        $request = new ServerRequest('POST', $uri, ['content-type' => 'application/json']);

        if ($body !== null) {
            $stream = new Stream(\fopen('php://temp', 'wb+'));
            $stream->write($body);
            $request = $request->withBody($stream);
            $request->getBody()->rewind();
        }

        return ($app->handleRequest($request))();
    }
}
