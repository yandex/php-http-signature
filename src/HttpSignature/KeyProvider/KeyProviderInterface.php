<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyProvider;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Exception\KeyNotFoundException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\KeyStorageException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;

interface KeyProviderInterface
{
    /**
     * @param string $id
     *
     * @return KeyInterface
     * @throws KeyNotFoundException
     * @throws KeyNotMatchException
     * @throws KeyCorruptedException
     * @throws KeyStorageException
     */
    public function fetch(string $id): KeyInterface;
}
