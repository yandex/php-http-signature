<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Key;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;

abstract class AbstractOpenSslPemKey implements KeyInterface
{
    private const RESOURCE_TYPE = 'OpenSSL key';

    private const KEYS_TYPE_MAPPING = [
        OPENSSL_KEYTYPE_RSA => 'rsa',
        OPENSSL_KEYTYPE_EC => 'ec',
        OPENSSL_KEYTYPE_DH => 'dh',
        OPENSSL_KEYTYPE_DSA => 'dsa',
    ];

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $key;

    /**
     * @var resource|null
     */
    private $openedKey;

    /**
     * @var string
     */
    private $type;

    /**
     * @param string $id
     * @param string $key
     * @throws KeyCorruptedException
     */
    public function __construct(string $id, string $key)
    {
        $this->id = $id;
        $this->key = $key;

        $resource = $this->open();

        try {

            $details = openssl_pkey_get_details($resource);
            if (!is_array($details) || !array_key_exists('type', $details)) {
                $this->close();

                throw new KeyCorruptedException('Unknown type of key given. Cannot determine key details.');
            }

            $type = $details['type'];
            if (!array_key_exists($type, self::KEYS_TYPE_MAPPING)) {
                $this->close();

                throw new \LogicException(
                    sprintf(
                        'Unknown type of PEM key given: %d. Allowed types: %s (look at php openssl constants OPENSSL_KEYTYPE_* for more information).',
                        $type,
                        implode(', ', self::KEYS_TYPE_MAPPING)
                    )
                );
            }

            $this->type = self::KEYS_TYPE_MAPPING[$type];
        } finally {
            $this->close();
        }
    }

    /**
     * @return resource
     * @throws KeyCorruptedException
     */
    public function open()
    {
        if ($this->isOpened()) {
            return $this->getOpenedKey();
        }

        $resource = $this->doOpen($this->key);
        if (!is_resource($resource) || get_resource_type($resource) !== self::RESOURCE_TYPE) {
            $errorMsg = openssl_error_string();

            throw new KeyCorruptedException(
                sprintf(
                    'Failed to open key [%s] via openssl: %s',
                    $this->getId(),
                    $errorMsg === false ? 'unknown error' : $errorMsg
                )
            );
        }

        $this->openedKey = $resource;

        return $this->getOpenedKey();
    }

    public function isOpened(): bool
    {
        return $this->openedKey !== null;
    }

    /**
     * @return resource
     */
    public function getOpenedKey()
    {
        if (!$this->isOpened()) {
            throw new \LogicException(sprintf('Called closed key (ID: %s)', $this->getId()));
        }

        return $this->openedKey;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $key
     *
     * @return resource|null
     */
    abstract protected function doOpen(string $key);

    public function close(): void
    {
        if (!$this->isOpened()) {
            return;
        }

        openssl_free_key($this->openedKey);
        $this->openedKey = null;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function __toString(): string
    {
        return $this->key;
    }
}
