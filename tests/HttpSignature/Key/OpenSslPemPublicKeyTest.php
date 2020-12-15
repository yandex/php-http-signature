<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\Key;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;

class OpenSslPemPublicKeyTest extends TestCase
{
    private const KEY_ID = 'Test';

    /**
     * @param string $key
     *
     * @dataProvider unopenableKeysDataProvider
     */
    public function testConstructKeyFailure(string $key)
    {
        $this->expectException(KeyCorruptedException::class);
        new OpenSslPemPublicKey(self::KEY_ID, $key);
    }

    public function unopenableKeysDataProvider()
    {
        return [
            'empty public pem' => [''],
            'invalid public pem' => ['some not key'],
        ];
    }
}
