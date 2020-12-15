<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\KeyLoader;

use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;

class OpenSslPemKeyLoader implements KeyLoaderInterface
{
    public function isSupports($raw): bool
    {
        return is_array($raw)
            && array_key_exists('type', $raw)
            && array_key_exists('is_public', $raw)
            && array_key_exists('content', $raw)
            && $raw['type'] === 'pem'
            && is_bool($raw['is_public'])
            && (!array_key_exists('passphrase', $raw) || is_string($raw['passphrase']))
            && is_string($raw['content']);
    }

    public function load(string $id, $raw): KeyInterface
    {
        if ($this->isSupports($raw)) {
            if ($raw['is_public'] === true) {
                return new OpenSslPemPublicKey($id, $raw['content']);
            } else {
                return new OpenSslPemPrivateKey($id, $raw['content'], $raw['passphrase'] ?? '');
            }
        }

        throw new KeyNotMatchException('Not supported PEM key format');
    }
}
