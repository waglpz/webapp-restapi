<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Common;

use Psr\Http\Message\ServerRequestInterface;
use Waglpz\Webapp\Common\Assert\Assert;

final class PaginationHateOS
{
    public const int MAX_ITEMS_ALLOWED_PER_PAGE = 100;
    private int $limit;
    private int $page;
    private int $totalItems;
    private string $baseUrl;
    private int $totalPages;
    private int $offset;

    public function initPagination(
        ServerRequestInterface $request,
        int $totalCount,
        int $maxItemsPerPage = 10,
    ): self {
        $requestTarget    = $request->getRequestTarget();
        $position         = \strpos($requestTarget, '?');
        $this->baseUrl    = $position !== false ? \substr($requestTarget, 0, $position) : $requestTarget;
        $this->totalItems = $totalCount;

        $requestQueryParams = $request->getQueryParams();

        $currentPage = $requestQueryParams['page'] ?? 1;
        try {
            Assert::integerish($currentPage);
            $this->page = \min(\max((int) $currentPage, 1), \PHP_INT_MAX);
        } catch (\InvalidArgumentException) {
            $this->page = 1;
        }

        $currentLimit = $requestQueryParams['limit'] ?? $maxItemsPerPage;
        try {
            Assert::integerish($currentLimit);
        } catch (\InvalidArgumentException) {
            $currentLimit = $maxItemsPerPage;
        }

        $this->limit      = \min((\max((int) $currentLimit, 1)), self::MAX_ITEMS_ALLOWED_PER_PAGE);
        $this->offset     = $this->limit * ($this->page - 1);
        $this->totalPages = \max((int) \ceil($this->totalItems / $this->limit), 1);

        return $this;
    }

    /**
     * @param array<mixed> $requestQueryParams
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function paginationResponseModel(array $requestQueryParams, array $data): array
    {
        $first            = $previous = $self = $next = $last = $requestQueryParams;
        $first['page']    = 1;
        $previous['page'] = \max($this->page - 1, 1);
        $self['page']     = $this->page;
        $next['page']     = \min($this->page + 1, $this->totalPages);
        $last['page']     = $this->totalPages;

        return [
            '_links'       => [
                'first'    => $this->baseUrl . '?' . \http_build_query($first),
                'previous' => $this->baseUrl . '?' . \http_build_query($previous),
                'self'     => $this->baseUrl . '?' . \http_build_query($self),
                'next'     => $this->baseUrl . '?' . \http_build_query($next),
                'last'     => $this->baseUrl . '?' . \http_build_query($last),
            ],
            'totalItems'   => $this->totalItems,
            'totalPages'   => $this->totalPages,
            'itemsPerPage' => $this->limit,
            '_embedded'    => $data,
        ];
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function totalItems(): int
    {
        return $this->totalItems;
    }

    public function itemsPerPage(): int
    {
        return $this->limit;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function page(): int
    {
        return $this->page;
    }
}
