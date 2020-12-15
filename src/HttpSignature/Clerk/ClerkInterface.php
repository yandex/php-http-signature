<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Clerk;

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Signature;

interface ClerkInterface
{
    /**
     * @param KeyInterface $key
     * @param HeadersAccessorInterface $headersAccessor
     * @param array $headers
     *
     * @return Signature
     *
     * @throws
     */
    public function sign(KeyInterface $key, HeadersAccessorInterface $headersAccessor, array $headers): Signature;
}
