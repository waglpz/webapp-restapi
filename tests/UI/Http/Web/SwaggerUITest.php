<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Tests\UI\Http\Web;

use PHPUnit\Framework\MockObject\Exception;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\RestApi\UI\Http\Web\SwaggerUI;
use Waglpz\Webapp\Tests\UI\WebTestCase;

final class SwaggerUITest extends WebTestCase
{
    /**
     * @throws \JsonException
     * @throws Exception
     *
     * @test
     */
    public function einErrorWirdProduziertWennSchemaFileInvalid(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Swagger scheme load failed to open stream: No such file.');

        $view    = $this->createMock(PhpRenderer::class);
        $request = $this->createMock(ServerRequestInterface::class);
        $request->expects(self::once())->method('getRequestTarget')->willReturn('/doc');
        $swaggerSchemeFile = 'WRONG';

        $controller = new SwaggerUI($view, $swaggerSchemeFile);
        $controller($request);
    }

    /** @test */
    public function dokumentation(): void
    {
        $uri      = '/api/doc';
        $response = $this->webGetResponse($uri);
        self::assertSame(200, $response->getStatusCode());
        $html = (string) $response->getBody();
        self::assertStringContainsString(
            '<title>Waglpz REST API Documentation</title>',
            $html,
        );
    }

    /**
     * @throws \JsonException
     *
     * @test
     */
    public function schema(): void
    {
        $uri      = '/api/doc.json';
        $response = $this->webGetResponse($uri);
        self::assertSame(200, $response->getStatusCode());
        $json           = (string) $response->getBody();
        $jsonSchemaFile = \file_get_contents(__DIR__ . '/../../../../swagger.json');
        self::assertIsString($jsonSchemaFile);
        self::assertEquals(
            \json_decode($json, true, 512, \JSON_THROW_ON_ERROR),
            \json_decode($jsonSchemaFile, true, 512, \JSON_THROW_ON_ERROR),
        );
    }
}
