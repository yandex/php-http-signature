<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureAlgorithmFactory;

use Yandex\Eats\HttpSignature\Exception\UnknownSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\SignatureAlgorithmInterface;

interface SignatureAlgorithmFactoryInterface
{
    /**
     * @param string $algorithm The algorithm of signature include key type. Example: rsa-sha512.
     *
     * @return SignatureAlgorithmInterface
     *
     * @throws UnknownSignatureAlgorithmException
     */
    public function make(string $algorithm): SignatureAlgorithmInterface;
}
