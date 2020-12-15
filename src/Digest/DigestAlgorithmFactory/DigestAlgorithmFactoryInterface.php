<?php
declare(strict_types=1);

namespace Yandex\Eats\Digest\DigestAlgorithmFactory;

use Yandex\Eats\Digest\DigestAlgorithm\DigestAlgorithmInterface;
use Yandex\Eats\Digest\Exception\UnknownDigestAlgorithmException;

interface DigestAlgorithmFactoryInterface
{
    /**
     * @param string $algorithm
     * @return DigestAlgorithmInterface
     * @throws UnknownDigestAlgorithmException
     */
    public function make(string $algorithm): DigestAlgorithmInterface;
}
