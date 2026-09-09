<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi;

use Aidphp\Http\Response;
use Interop\Http\EmitterInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblem\Http\NotFoundProblem;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Waglpz\Webapp\Common\Exception\NotFound;

use function Waglpz\Webapp\getTraceDigest;
use function Waglpz\Webapp\jsonResponse;

final readonly class App
{
    public function __construct(
        private \Waglpz\Webapp\App $app,
        private LoggerInterface $log,
    ) {
    }

    /**
     * @throws \ReflectionException
     * @throws \JsonException
     * @throws \Throwable
     */
    public function run(ServerRequestInterface $request): never
    {
        if ($this->isApiRequest($request)) {
            $this->handleApiRequest($request);
        }

        $this->handleWebRequest($request);
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        $path = $request->getRequestTarget();

        if (\str_ends_with($path, '.pdf') && $request->getMethod() === 'GET') {
            return true;
        }

        return \str_starts_with($path, '/api') &&
            \in_array($request->getMethod(), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], true) &&
            $this->hasJsonContentType($request);
    }

    private function hasJsonContentType(ServerRequestInterface $request): bool
    {
        return \str_starts_with($request->getHeaderLine('accept'), 'application/json')
            || \str_starts_with($request->getHeaderLine('content-type'), 'application/json');
    }

    /**
     * @throws \Throwable
     * @throws \ReflectionException
     */
    private function handleApiRequest(ServerRequestInterface $request): never
    {
        try {
            $this->validateJsonPayload($request);
            $this->app->run($request);
            exit;
        } catch (\JsonException $exception) {
            $this->logException($exception);
            $response = $this->createErrorResponse(
                400,
                $exception,
                'Invalid request payload JSON: ' . $exception->getMessage(),
            );
            $this->emitResponse($response);
        } catch (ApiProblemException $exception) {
            $this->logException($exception);
            $response = $this->createErrorResponse($exception->getCode(), $exception);
            $this->emitResponse($response);
        } catch (NotFound $exception) {
            $this->logException($exception);
            $notFoundException = new ApiProblemException(new NotFoundProblem($exception->getMessage()));
            $response          = $this->createErrorResponse(404, $notFoundException);
            $this->emitResponse($response);
        } catch (\Throwable $exception) {
            $this->logException($exception);
            $response = $this->createErrorResponse(500, $exception);
            $this->emitResponse($response);
        }
    }

    /** @throws \JsonException */
    private function validateJsonPayload(ServerRequestInterface $request): void
    {
        $content = $request->getBody()->getContents();
        if ($content !== '') {
            \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        }

        $request->getBody()->rewind();
    }

    private function logException(\Throwable $exception): void
    {
        $this->log->error($exception->getMessage());
        foreach (getTraceDigest($exception) as $line) {
            $this->log->error($line);
        }
    }

    private function createErrorResponse(
        int|null $statusCode = null,
        \Throwable|null $exception = null,
        string|null $detail = null,
    ): ResponseInterface {
        $currentDetail = $detail ?? $exception?->getMessage() ?? 'Unbekannter Server Error.';
        try {
            if ($exception instanceof ApiProblemException) {
                $finalStatusCode = $exception->getCode();
                $apiProblem      = $exception->getApiProblem();
            } else {
                $finalStatusCode = $statusCode ?? 500;
                $apiProblem      = new HttpApiProblem($finalStatusCode, ['detail' => $currentDetail]);
            }

            return jsonResponse($apiProblem->toArray(), $finalStatusCode);
        } catch (\Throwable $responseException) {
            return $this->createLowLevelErrorResponse($responseException);
        }
    }

    public function createLowLevelErrorResponse(\Throwable $responseException): Response
    {
        $this->logException($responseException);
        $response = (new Response(500))->withHeader('content-type', 'application/json');
        $payload  = '{"status": 500, "detail": "Unbekannter Server Error."}';

        try {
            $response->getBody()->write($payload);
        } catch (\Throwable $writeException) {
            $this->logException($writeException);
            // Fallback
        }

        return $response;
    }

    /**
     * @throws \ReflectionException
     * @throws \Throwable
     */
    private function emitResponse(ResponseInterface $response): never
    {
        try {
            $emitter = (new \ReflectionProperty($this->app, 'emitter'))->getValue($this->app);
            \assert($emitter instanceof EmitterInterface);
            $emitter->emit($response);
        } catch (\Throwable $exception) {
            $this->logException($exception);

            throw $exception;
        }

        exit;
    }

    /** @throws \Throwable */
    private function handleWebRequest(ServerRequestInterface $request): never
    {
        try {
            $this->app->run($request);
        } catch (\Throwable $exception) {
            $this->logException($exception);

            throw $exception;
        }

        exit;
    }
}
