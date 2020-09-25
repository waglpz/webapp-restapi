<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Tests\UI\Http\Rest;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Waglpz\Webapp\RestApi\Tests\UI\RestTestCase;

final class PingTest extends RestTestCase
{
    /**
     * @throws \JsonException
     *
     * @test
     */
    public function pingDerGesundheit(): void
    {
        $uri = '/api/ping';
        try {
            $response = $this->restGetResponse($uri);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface) {
        }

        self::assertTrue(isset($response));
        self::assertSame(200, $response->getStatusCode());
        $json = (string) $response->getBody();
        $data = \json_decode($json, true, 512, \JSON_THROW_ON_ERROR);
        \assert(\is_array($data));
        self::assertArrayHasKey('time', $data);
        self::assertArrayHasKey('apiVersion', $data);
    }
}
