<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi;

use Interop\Http\EmitterInterface;
use Phpro\ApiProblem\Exception\ApiProblemException;
use Phpro\ApiProblem\Http\HttpApiProblem;
use Phpro\ApiProblem\Http\NotFoundProblem;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Waglpz\Webapp\Common\Exception\NotFound;

use function Waglpz\Webapp\getTraceDigest;
use function Waglpz\Webapp\jsonResponse;

final class App
{
    public function __construct(
        private readonly \Waglpz\Webapp\App $app,
        private readonly LoggerInterface $log,
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
            try {
                $content = $request->getBody()->getContents();
                if ($content !== '') {
                    \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
                }

                $request->getBody()->rewind();
                $this->app->run($request);
                exit;
            } catch (\JsonException $exception) {
                $this->log->error($exception->getMessage());
                $apiProblem = new HttpApiProblem(
                    400,
                    ['detail' => 'Request payload JSON: ' . $exception->getMessage()],
                );
                $response   = jsonResponse($apiProblem->toArray(), 400);
                //throw $exception;
            } catch (ApiProblemException $exception) {
                $response = jsonResponse($exception->getApiProblem()->toArray(), $exception->getCode());
                //throw $exception;
            } catch (NotFound $exception) {
                $apiProblem = new NotFoundProblem($exception->getMessage());
                $response   = jsonResponse($apiProblem->toArray(), 404);
            } catch (\Throwable $exception) {
                $this->log->error($exception->getMessage());
                foreach (getTraceDigest($exception) as $line) {
                    $this->log->error($line);
                }

                $apiProblem = new HttpApiProblem((int) $exception->getCode(), ['detail' => $exception->getMessage()]);
                $response   = jsonResponse($apiProblem->toArray(), 500);
                //throw $exception;
            } finally {
                if (isset($response)) {
                    $emitter = (new \ReflectionProperty($this->app, 'emitter'))->getValue($this->app);
                    \assert($emitter instanceof EmitterInterface);
                    $emitter->emit($response);
                }
            }
        }

        try {
            $this->app->run($request);
        } catch (\Throwable $exception) {
            $this->log->error($exception->getMessage());
            foreach (getTraceDigest($exception) as $line) {
                $this->log->error($line);
            }
        } finally {
            exit;
        }
    }

    private function isApiRequest(ServerRequestInterface $request): bool
    {
        return \str_starts_with($request->getRequestTarget(), '/api') &&
            \in_array($request->getMethod(), ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], true) &&
            (
                \str_starts_with($request->getHeaderLine('accept'), 'application/json')
                || \str_starts_with(
                    $request->getHeaderLine('content-type'),
                    'application/json',
                )
            );
    }
}
