<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\HeadersAccessor;

use Yandex\Eats\HttpSignature\HeadersEnum;

interface HeadersAccessorInterface
{
    /**
     * Special header name for request-target string
     */
    public const REQUEST_TARGET_HEADER = HeadersEnum::PSEUDO_HEADER_REQUEST_TARGET;

    /**
     * Glue for multiple header values
     */
    public const ARRAY_HEADER_GLUE = ', ';

    /**
     * @param string $header
     *
     * @return string A string of values as provided for the given header concatenated together using a comma.
     *                If the header does not appear in the message, this method MUST return an empty string.
     */
    public function fetch(string $header): string;
}
