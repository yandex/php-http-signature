<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Key;

class OpenSslPemPublicKey extends AbstractOpenSslPemKey
{
    protected function doOpen(string $key)
    {
        $resource = openssl_pkey_get_public($key);

        return $resource !== false ? $resource : false;
    }
}
