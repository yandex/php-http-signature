<?php
declare(strict_types=1);

namespace Yandex\Eats\Digest\DigestAlgorithm;

use Yandex\Eats\Digest\Digest;
use Yandex\Eats\Digest\Exception\DigestFailedException;
use Yandex\Eats\Digest\Exception\UnsupportedDigestAlgorithmException;

class OpenSslBasedHashAlgorithm implements DigestAlgorithmInterface
{
    /**
     * @var string Openssl-mapped digest algorithm.
     */
    private $mappedHashAlgorithm = '';

    /**
     * @var string Digest algorithm as received via constructor.
     */
    private $hashAlgorithm = '';

    /**
     * @var string[]|null Cache of supported hashing algorithms.
     */
    private $supportedAlgorithms;

    /**
     * @param string $hashAlgorithm
     * @throws UnsupportedDigestAlgorithmException
     */
    public function __construct(string $hashAlgorithm)
    {
        if (!extension_loaded('openssl')) {
            throw new \LogicException(sprintf('OpenSSL extension required for %s.', self::class));
        }

        $mappedHashAlgorithm = $this->findRealHashAlgorithm($hashAlgorithm);
        if ($mappedHashAlgorithm === null) {
            throw new UnsupportedDigestAlgorithmException(
                sprintf(
                    'Unsupported openssl digest algorithm called: %s. Available algorithms: %s',
                    $hashAlgorithm,
                    implode(', ', $this->getSupportedAlgorithms())
                )
            );
        }

        $this->hashAlgorithm = $hashAlgorithm;
        $this->mappedHashAlgorithm = $mappedHashAlgorithm;
    }

    private function findRealHashAlgorithm(string $hashAlgorithmName): ?string
    {
        $algorithmNameAliases = [
            $hashAlgorithmName,
            str_replace('-', '', $hashAlgorithmName),
        ];

        foreach ($algorithmNameAliases as $algorithmNameAlias) {
            $lower = strtolower($algorithmNameAlias);

            if (array_key_exists($lower, $this->getSupportedAlgorithms())) {
                return $this->getSupportedAlgorithms()[$lower];
            }
        }

        return null;
    }

    public function getSupportedAlgorithms(): array
    {
        if ($this->supportedAlgorithms === null) {
            foreach (openssl_get_md_methods() as $method) {
                $this->supportedAlgorithms[strtolower($method)] = $method;
            }
        }

        return $this->supportedAlgorithms;
    }

    public function hash(string $data): Digest
    {
        $hash = @openssl_digest($data, $this->mappedHashAlgorithm);
        if ($hash === false) {
            throw new DigestFailedException(
                sprintf('Failed to create digest. OpenSSL error: %s', openssl_error_string())
            );
        }

        return new Digest($this->hashAlgorithm, $hash);
    }

    public function getMappedHashAlgorithm(): string
    {
        return $this->mappedHashAlgorithm;
    }

    public function getHashAlgorithm(): string
    {
        return $this->hashAlgorithm;
    }
}
