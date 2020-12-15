<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Key;

use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;

class OpenSslPemPrivateKey extends AbstractOpenSslPemKey
{
    /**
     * @var string
     */
    private $passphrase = '';

    /**
     * @param string $id
     * @param string $key
     * @param string $passphrase
     * @throws KeyCorruptedException
     */
    public function __construct(string $id, string $key, $passphrase = '')
    {
        $this->passphrase = $passphrase;

        parent::__construct($id, $key);
    }

    public function getPassphrase(): string
    {
        return $this->passphrase;
    }

    protected function doOpen(string $key)
    {
        $resource = openssl_pkey_get_private($key, $this->passphrase);

        return $resource !== false ? $resource : null;
    }
}
