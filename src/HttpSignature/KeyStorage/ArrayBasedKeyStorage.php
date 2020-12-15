<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyStorage;

class ArrayBasedKeyStorage implements KeyStorageInterface
{
    /**
     * @var mixed[]
     */
    private $storage = [];

    public function __construct(array $storage)
    {
        $this->storage = $storage;
    }

    public function fetch(string $id)
    {
        return array_key_exists($id, $this->storage) ? $this->storage[$id] : null;
    }
}
