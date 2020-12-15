<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\HeadersAccessor;

use Psr\Http\Message\RequestInterface;

class PsrHttpRequestAccessor implements HeadersAccessorInterface
{
    /**
     * @var RequestInterface
     */
    private $psrRequest;

    public function __construct(RequestInterface $psrRequest)
    {
        $this->psrRequest = $psrRequest;
    }

    public function fetch(string $header): string
    {
        if ($header === self::REQUEST_TARGET_HEADER) {
            return $this->buildRequestTarget();
        }

        return preg_replace('/\s+/', ' ', $this->psrRequest->getHeaderLine($header));
    }

    private function buildRequestTarget(): string
    {
        $requestHeader = sprintf(
            "%s %s",
            strtolower($this->psrRequest->getMethod()),
            $this->psrRequest->getUri()
        );

        return $requestHeader;
    }
}
