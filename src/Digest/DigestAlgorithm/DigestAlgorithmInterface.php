<?php
declare(strict_types=1);

namespace Yandex\Eats\Digest\DigestAlgorithm;

use Yandex\Eats\Digest\Digest;
use Yandex\Eats\Digest\Exception\DigestFailedException;

interface DigestAlgorithmInterface
{
    /**
     * @param string $data
     * @return Digest
     * @throws DigestFailedException
     */
    public function hash(string $data): Digest;
}
