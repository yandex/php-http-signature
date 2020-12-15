<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyStorage;

use Yandex\Eats\HttpSignature\Exception\KeyStorageException;

interface KeyStorageInterface
{
    /**
     * @param string $id
     *
     * @return mixed Any data of raw key. It can be BLOB, array, object, anything you want to process in the related Loader.
     * @throws KeyStorageException
     */
    public function fetch(string $id);
}
