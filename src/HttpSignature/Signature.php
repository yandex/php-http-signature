<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature;

class Signature
{
    /**
     * @var string
     */
    private $keyId;

    /**
     * @var string
     */
    private $binarySignature;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string|null
     */
    private $algorithm;

    public function __construct(string $keyId, string $binarySignature, ?string $algorithm, array $headers = [])
    {
        foreach ($headers as $header) {
            if (!is_string($header)) {
                throw new \InvalidArgumentException('Header name MUST be a string');
            }

            $header = trim($header);
            $header = strtolower($header);

            $this->headers[] = $header;
        }

        $this->keyId = $keyId;
        $this->binarySignature = $binarySignature;
        $this->algorithm = $algorithm !== null ? strtolower($algorithm) : null;
    }

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    public function getAlgorithm(): ?string
    {
        return $this->algorithm;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getSignature(): string
    {
        return base64_encode($this->getBinarySignature());
    }

    public function getBinarySignature(): string
    {
        return $this->binarySignature;
    }

    public function hasHeaders(): bool
    {
        return count($this->headers) > 0;
    }

    public function hasAlgorithm(): bool
    {
        return $this->algorithm !== null;
    }
}
