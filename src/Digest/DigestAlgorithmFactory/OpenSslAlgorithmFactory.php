<?php
declare(strict_types=1);

namespace Yandex\Eats\Digest\DigestAlgorithmFactory;

use Yandex\Eats\Digest\DigestAlgorithm\DigestAlgorithmInterface;
use Yandex\Eats\Digest\DigestAlgorithm\OpenSslBasedHashAlgorithm;
use Yandex\Eats\Digest\Exception\UnknownDigestAlgorithmException;
use Yandex\Eats\Digest\Exception\UnsupportedDigestAlgorithmException;

class OpenSslAlgorithmFactory implements DigestAlgorithmFactoryInterface
{
    public function __construct()
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException(sprintf('OpenSSL extension required for %s.', self::class));
        }
    }

    public function make(string $algorithm): DigestAlgorithmInterface
    {
        try {
            return new OpenSslBasedHashAlgorithm(strtolower($algorithm));
        } catch (UnsupportedDigestAlgorithmException $e) {
            throw new UnknownDigestAlgorithmException(
                sprintf('Hash algorithm %s is not supported by openssl-based algorithm implementation', $algorithm),
                0,
                $e
            );
        }
    }
}
