<?php

declare(strict_types=1);

namespace Waglpz\Webapp\RestApi\Common\Exception;

use Waglpz\Webapp\Common\Exception\NotFound;

class ResourceNotFound extends \Exception implements NotFound
{
    /** @codingStandardsIgnoreStart */
    /** @var mixed */
    protected $message = 'Resource not found';
    /** @var mixed */
    protected $code = 404;
    /** @codingStandardsIgnoreEnd */
}
