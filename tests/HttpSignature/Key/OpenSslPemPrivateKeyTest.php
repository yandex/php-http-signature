<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\Key;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyCorruptedException;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;

class OpenSslPemPrivateKeyTest extends TestCase
{
    private const FIXTURE_PRIVATE_ENC = 'private_encrypted.pem';

    private const KEY_ID = 'TestPrivate';

    /**
     * @param string $key
     * @param string $passphrase
     *
     * @dataProvider unopenableKeysDataProvider
     */
    public function testConstructKeyFailure(string $key, string $passphrase)
    {
        $this->expectException(KeyCorruptedException::class);
        new OpenSslPemPrivateKey(self::KEY_ID, $key, $passphrase);
    }

    public function unopenableKeysDataProvider()
    {
        return [
            'empty private pem' => ['', ''],
            'invalid private pem' => ['some not key', ''],
            'valid private pem with empty passphrase' => [
                $this->loadFixture(self::FIXTURE_PRIVATE_ENC),
                '',
            ],
            'valid private pem with invalid passphrase' => [
                $this->loadFixture(self::FIXTURE_PRIVATE_ENC),
                'password',
            ],
        ];
    }

    private function loadFixture(string $keyName)
    {
        return file_get_contents(__DIR__.'/../../fixtures/keys/'.$keyName);
    }
}
