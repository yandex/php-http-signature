<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Verifier;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Exception\KeyNotFoundException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\KeyStorageException;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Exception\UnknownSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\Exception\UnsupportedSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\Signature;

interface VerifierInterface
{
    /**
     * @param Signature $signature
     * @param HeadersAccessorInterface $headersAccessor
     *
     * @return bool
     *
     * @throws KeyNotMatchException
     * @throws KeyNotFoundException
     * @throws KeyCorruptedException
     * @throws KeyStorageException
     * @throws UnknownSignatureAlgorithmException
     * @throws UnsupportedSignatureAlgorithmException
     * @throws SignatureCorruptedException
     */
    public function verify(Signature $signature, HeadersAccessorInterface $headersAccessor): bool;
}
