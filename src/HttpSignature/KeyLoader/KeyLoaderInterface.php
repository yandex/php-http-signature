<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyLoader;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;

interface KeyLoaderInterface
{
    /**
     * @param $raw
     * @return bool
     */
    public function isSupports($raw): bool;

    /**
     * @param string $id
     * @param mixed $raw
     *
     * @return KeyInterface
     * @throws KeyNotMatchException
     * @throws KeyCorruptedException
     */
    public function load(string $id, $raw): KeyInterface;
}
