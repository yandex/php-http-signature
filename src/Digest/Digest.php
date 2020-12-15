<?php
declare(strict_types=1);

namespace Yandex\Eats\Digest;

use Yandex\Eats\Digest\Exception\UnknownDigestFormatException;

/**
 * Naive and very simple Digest implementation for a minimum support in this package.
 * Please, use better implementation if you want to have a full RFC power.
 *
 * {@see https://tools.ietf.org/html/rfc3230} for more information about Digest header.
 */
class Digest
{
    private const HEADER_FORMATTED_PARTS = 2;

    /**
     * @var string
     */
    private $algorithm;

    /**
     * @var string
     */
    private $hash;

    /**
     * @param string $headerFormatted
     * @return Digest
     * @throws UnknownDigestFormatException
     */
    public static function fromHeader(string $headerFormatted)
    {
        $parts = explode('=', $headerFormatted, self::HEADER_FORMATTED_PARTS);
        if (count($parts) < self::HEADER_FORMATTED_PARTS) {
            throw new UnknownDigestFormatException('Unknown digest header format.');
        }

        [$algorithm, $hash] = $parts;

        return new static($algorithm, $hash);
    }

    public function __construct(string $algorithm, string $hash)
    {
        $this->algorithm = $algorithm;
        $this->hash = $hash;
    }

    public function __toString(): string
    {
        return sprintf('%s=%s', $this->getAlgorithm(), $this->getHash());
    }

    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
