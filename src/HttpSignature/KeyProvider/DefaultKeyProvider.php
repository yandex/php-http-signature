<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyProvider;

use Yandex\Eats\HttpSignature\Exception\KeyNotFoundException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\KeyLoader\KeyLoaderInterface;
use Yandex\Eats\HttpSignature\KeyStorage\KeyStorageInterface;

class DefaultKeyProvider implements KeyProviderInterface
{
    /**
     * @var KeyStorageInterface
     */
    private $storage;

    /**
     * @var KeyLoaderInterface[]
     */
    private $loaders = [];

    public function __construct(KeyStorageInterface $storage, array $loaders)
    {
        $this->storage = $storage;

        foreach ($loaders as $loader) {
            if (!$loader instanceof KeyLoaderInterface) {
                $type = is_object($loader) ? get_class($loader) : gettype($loader);

                throw new \InvalidArgumentException(
                    sprintf('Instance of %s expected, %s given', KeyLoaderInterface::class, $type)
                );
            }

            $this->loaders[] = $loader;
        }
    }

    public function fetch(string $id): KeyInterface
    {
        $raw = $this->storage->fetch($id);
        if ($raw === null) {
            throw new KeyNotFoundException(sprintf('Key %s not found', $id));
        }

        $key = null;
        foreach ($this->loaders as $loader) {
            if ($loader->isSupports($raw)) {
                return $loader->load($id, $raw);
            }
        }

        if ($key === null) {
            throw new KeyNotMatchException(sprintf('No one registered loader cannot open key %s', $id));
        }

        return $key;
    }
}

