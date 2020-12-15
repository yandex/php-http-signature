<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\HeadersAccessor;

class HeadersListAccessor implements HeadersAccessorInterface
{
    /**
     * @var array
     */
    private $headersList = [];

    /**
     * @var string
     */
    private $requestTarget;

    public function __construct(array $headersList, string $method, string $uri)
    {
        foreach ($headersList as $headerName => $headerValue) {
            $this->headersList[strtolower($headerName)] = $headerValue;
        }

        $method = trim(strtolower($method));
        $uri = trim($uri);

        $this->requestTarget = sprintf('%s %s', $method, $uri);
    }

    public function fetch(string $header, string $default = ''): string
    {
        if ($header === self::REQUEST_TARGET_HEADER) {
            return $this->requestTarget;
        }

        $header = strtolower($header);

        $rawValue = $this->headersList[$header] ?? $default;

        if (is_array($rawValue)) {
            $rawValue = array_map(
                function ($value) {
                    return trim((string)$value, " \t");
                },
                $rawValue
            );
            $headerValue = implode(', ', $rawValue);
        } else {
            $headerValue = trim((string)$rawValue);
        }

        $headerValue = preg_replace('/\s+/', ' ', $headerValue);

        return $headerValue;
    }
}
