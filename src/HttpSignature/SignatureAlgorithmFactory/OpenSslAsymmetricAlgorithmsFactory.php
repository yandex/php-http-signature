<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureAlgorithmFactory;

use Yandex\Eats\HttpSignature\Exception\UnknownSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\Exception\UnsupportedSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\SignatureAlgorithmInterface;

class OpenSslAsymmetricAlgorithmsFactory implements SignatureAlgorithmFactoryInterface
{
    private const KEY_TYPE_RSA = 'rsa';
    private const ALGORITHM_MIN_PARTS = 2;

    /**
     * @var string[]
     */
    private $allowedKeyTypes = [];

    public function __construct(array $allowedKeyTypes = [self::KEY_TYPE_RSA])
    {
        $this->allowedKeyTypes = $allowedKeyTypes;
    }

    public function make(string $algorithm): SignatureAlgorithmInterface
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException(sprintf('OpenSSL extension required for %s.', static::class));
        }

        [$keyType, $signAlgorithm] = $this->parseAlgorithm($algorithm);

        if (!in_array($keyType, $this->allowedKeyTypes)) {
            throw new UnknownSignatureAlgorithmException(
                sprintf(
                    '%s supports only %s key types, %s given',
                    static::class,
                    implode(', ', $this->allowedKeyTypes),
                    $algorithm
                )
            );
        }

        try {
            return new OpenSslBasedAsymmetricAlgorithm($keyType, $signAlgorithm);
        } catch (UnsupportedSignatureAlgorithmException $e) {
            throw new UnknownSignatureAlgorithmException(
                sprintf('Used unsupported signature algorithm: %s', $signAlgorithm), 0, $e
            );
        }
    }

    /**
     * @param string $algorithm
     * @return array
     * @throws UnknownSignatureAlgorithmException
     */
    private function parseAlgorithm(string $algorithm): array
    {
        $algorithm = strtolower($algorithm);
        $parts = explode('-', $algorithm, 2);

        if (count($parts) < self::ALGORITHM_MIN_PARTS) {
            throw new UnknownSignatureAlgorithmException(
                sprintf('%s not support %s algorithm', static::class, $algorithm)
            );
        }

        return $parts;
    }
}
