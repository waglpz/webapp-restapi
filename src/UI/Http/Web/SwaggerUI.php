<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\UI\Http\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;
use Waglpz\Webapp\WebController;

use function Waglpz\Webapp\jsonResponse;

final class SwaggerUI extends WebController
{
    public function __construct(PhpRenderer $view, private readonly string $swaggerSchemeFile)
    {
        parent::__construct($view);
    }

    /** @throws \JsonException */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $target = $request->getRequestTarget();
        if (\str_ends_with($target, '.json')) {
            return jsonResponse($this->getSchema());
        }

        $this->disableLayout();
        $spec = $this->getSchema();
        $spec = \json_encode($spec, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_HEX_TAG);

        $model = [
            'webSiteTitle' => 'Waglpz REST API Documentation',
            'spec'         => $spec,
        ];

        return $this->render($model, 200, 'swagger-ui');
    }

    /**
     * @return array<mixed>
     *
     * @throws \JsonException
     */
    private function getSchema(): array
    {
        if (! \file_exists($this->swaggerSchemeFile)) {
            throw new \Error(
                'Swagger scheme load failed to open stream: No such file.',
            );
        }

        $swaggerScheme = \file_get_contents($this->swaggerSchemeFile);
        \assert($swaggerScheme !== false);

        /** @phpstan-ignore-next-line */
        return \json_decode($swaggerScheme, true, 512, \JSON_THROW_ON_ERROR);
    }
}
