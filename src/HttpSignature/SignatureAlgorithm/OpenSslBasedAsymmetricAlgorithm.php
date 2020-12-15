<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureAlgorithm;

use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Exception\SignatureFailedException;
use Yandex\Eats\HttpSignature\Exception\UnsupportedSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;

class OpenSslBasedAsymmetricAlgorithm implements SignatureAlgorithmInterface
{
    private const SIGN_ALGORITHMS = [
        'sha1' => OPENSSL_ALGO_SHA1,
        'md5' => OPENSSL_ALGO_MD5,
        'md4' => OPENSSL_ALGO_MD4,
        'sha224' => OPENSSL_ALGO_SHA224,
        'sha256' => OPENSSL_ALGO_SHA256,
        'sha384' => OPENSSL_ALGO_SHA384,
        'sha512' => OPENSSL_ALGO_SHA512,
        'rmd160' => OPENSSL_ALGO_RMD160,
    ];

    private $keyType = '';

    /**
     * @var int Openssl-mapped signature algorithm.
     */
    private $mappedSignAlgorithm = '';

    /**
     * @var string Signature algorithm as received via constructor.
     */
    private $signAlgorithm = '';

    /**
     * @var string[]|null Cache of supported hashing algorithms.
     */
    private $supportedAlgorithms;

    /**
     * @param string $keyType
     * @param string $signAlgorithm
     * @throws UnsupportedSignatureAlgorithmException
     */
    public function __construct(string $keyType, string $signAlgorithm)
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException(sprintf('OpenSSL extension required for %s.', self::class));
        }

        $mappedHashAlgorithm = $this->mapSignAlgorithm($signAlgorithm);
        if ($mappedHashAlgorithm === null) {
            throw new UnsupportedSignatureAlgorithmException(
                sprintf(
                    'Unsupported openssl signature algorithm called: %s. Available algorithms: %s',
                    $signAlgorithm,
                    implode(', ', array_keys($this->getSupportedAlgorithms()))
                )
            );
        }

        $this->signAlgorithm = $signAlgorithm;
        $this->mappedSignAlgorithm = $mappedHashAlgorithm;
        $this->keyType = $keyType;
    }

    private function mapSignAlgorithm(string $algorithmName): ?int
    {
        $aliases = [
            $algorithmName,
            str_replace('-', '', $algorithmName),
        ];

        foreach ($aliases as $alias) {
            $lower = strtolower($alias);

            if (array_key_exists($lower, $this->getSupportedAlgorithms())) {
                return $this->getSupportedAlgorithms()[$lower];
            }
        }

        return null;
    }

    public function getSupportedAlgorithms(): array
    {
        if ($this->supportedAlgorithms === null) {
            foreach (self::SIGN_ALGORITHMS as $alias => $method) {
                $this->supportedAlgorithms[strtolower($alias)] = $method;
            }
        }

        return $this->supportedAlgorithms;
    }

    public function sign(KeyInterface $key, string $data): string
    {
        if (!$key instanceof OpenSslPemPrivateKey) {
            throw new KeyNotMatchException(sprintf('PEM private key expected, %s given', get_class($key)));
        }

        if ($key->getType() !== $this->keyType) {
            throw new KeyNotMatchException('%s key expected, %s given', $this->keyType, $key->getType());
        }

        $keyResource = $key->open();

        try {
            $result = openssl_sign($data, $signature, $keyResource, $this->mappedSignAlgorithm);
        } finally {
            $key->close();
        }

        if ($result === true) {
            return $signature;
        } else {
            throw new SignatureFailedException(sprintf('Failed to create signature: %s', openssl_error_string()));
        }
    }

    public function verify(KeyInterface $key, string $data, string $signature): bool
    {
        if (!$key instanceof OpenSslPemPublicKey) {
            throw new KeyNotMatchException('PEM public key expected');
        }

        if ($key->getType() !== $this->keyType) {
            throw new KeyNotMatchException('%s key expected, %s given', $this->keyType, $key->getType());
        }

        $keyResource = $key->open();

        try {
            $result = openssl_verify($data, $signature, $keyResource, $this->mappedSignAlgorithm);
        } finally {
            $key->close();
        }

        if ($result === 1) {
            return true;
        } elseif ($result === 0) {
            return false;
        } else {
            throw new SignatureCorruptedException('Failed to verify signature: '.openssl_error_string());
        }
    }

    public function getAlgorithmName(): string
    {
        return sprintf('%s-%s', $this->getKeyType(), $this->getSignAlgorithm());
    }

    public function getKeyType(): string
    {
        return $this->keyType;
    }

    public function getSignAlgorithm(): string
    {
        return $this->signAlgorithm;
    }

    public function getMappedSignAlgorithm(): int
    {
        return $this->mappedSignAlgorithm;
    }
}
