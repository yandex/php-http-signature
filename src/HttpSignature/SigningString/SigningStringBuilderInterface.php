<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SigningString;

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;

interface SigningStringBuilderInterface
{
    public function build(HeadersAccessorInterface $headersAccessor, iterable $headersList): string;
}
