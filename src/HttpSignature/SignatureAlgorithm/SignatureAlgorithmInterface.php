<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureAlgorithm;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Exception\SignatureFailedException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;

interface SignatureAlgorithmInterface
{
    /**
     * @param KeyInterface $key
     * @param string $data
     *
     * @return string
     * @throws SignatureFailedException
     * @throws KeyNotMatchException
     * @throws KeyCorruptedException
     */
    public function sign(KeyInterface $key, string $data): string;

    /**
     * @param KeyInterface $key
     * @param string $data
     * @param string $signature
     *
     * @return bool
     * @throws KeyNotMatchException
     * @throws KeyCorruptedException
     * @throws SignatureCorruptedException
     */
    public function verify(KeyInterface $key, string $data, string $signature): bool;


    /**
     * @return string
     */
    public function getAlgorithmName(): string;
}
