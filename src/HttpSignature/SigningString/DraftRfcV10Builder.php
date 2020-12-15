<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SigningString;

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;

class DraftRfcV10Builder implements SigningStringBuilderInterface
{
    private const LINE_SEPARATOR = "\n";

    private const HEADER_DEFAULT = 'date';

    public function build(HeadersAccessorInterface $headersAccessor, iterable $headersList): string
    {
        $stringParts = [];
        foreach ($headersList as $headerName) {
            if (!is_string($headerName)) {
                throw new \InvalidArgumentException(sprintf('Header must be a string, %s given', gettype($headerName)));
            }

            if (trim($headerName) === '') {
                continue;
            }

            $headerValue = $headersAccessor->fetch($headerName);
            $stringParts[] = $this->buildHeader($headerName, $headerValue);
        }

        if (count($stringParts) < 1) {
            $stringParts[] = $this->buildHeader(
                self::HEADER_DEFAULT,
                $headersAccessor->fetch(self::HEADER_DEFAULT)
            );
        }

        return implode(self::LINE_SEPARATOR, $stringParts);
    }

    private function buildHeader(string $name, string $value)
    {
        return sprintf('%s: %s', strtolower($name), $value);
    }
}
