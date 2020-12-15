<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\KeyLoader;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;
use Yandex\Eats\HttpSignature\KeyLoader\OpenSslPemKeyLoader;

class OpenSslPemKeyLoaderTest extends TestCase
{
    /**
     * @param $data
     * @param string $id
     * @param KeyInterface $expectedKey
     *
     * @dataProvider validKeysProvider
     */
    public function testSuccessfullyLoad($data, string $id, KeyInterface $expectedKey)
    {
        $loader = new OpenSslPemKeyLoader();

        $this->assertEquals($expectedKey, $loader->load($id, $data));
    }

    /**
     * @param $data
     *
     * @dataProvider invalidKeysProvider
     */
    public function testFailureLoad($data)
    {
        $loader = new OpenSslPemKeyLoader();

        $this->expectException(KeyNotMatchException::class);
        $loader->load('id', $data);
    }

    /**
     * @param $data
     * @param bool $expected
     *
     * @dataProvider supportCheckKeysDataProvider
     */
    public function testIsSupports($data, bool $expected)
    {
        $loader = new OpenSslPemKeyLoader();

        $this->assertEquals($expected, $loader->isSupports($data));
    }

    public function validKeysProvider()
    {
        $uniqId = uniqid();

        return [
            'private' => [
                $this->getFormattedKey('private_key'),
                $uniqId,
                new OpenSslPemPrivateKey($uniqId, $this->getKey('private_key_content')),
            ],
            'private encrypted' => [
                $this->getFormattedKey('private_encrypted_key'),
                $uniqId,
                new OpenSslPemPrivateKey($uniqId, $this->getKey('private_encrypted_key_content'), 'test123'),
            ],
            'public' => [
                $this->getFormattedKey('public_key'),
                $uniqId,
                new OpenSslPemPublicKey($uniqId, $this->getKey('public_key_content')),
            ],
        ];
    }

    public function invalidKeysProvider()
    {
        return [
            'empty string' => [''],
            'invalid string' => ['not key string'],
            'int' => [100],
            'float' => [99.5],
            'object' => [new \stdClass()],
            'array' => [['not empty array']],
            'callback' => [
                function () {
                    return false;
                },
            ],
        ];
    }

    public function supportCheckKeysDataProvider()
    {
        return [
            'private' => [$this->getFormattedKey('private_key'), true],
            'private encrypted' => [$this->getFormattedKey('private_encrypted_key'), true],
            'public' => [$this->getFormattedKey('public_key'), true],
            'empty string' => ['', false],
            'invalid string' => ['this is not key string', false],
            'int' => [100, false],
            'float' => [99.5, false],
            'object' => [new \stdClass(), false],
            'array' => [['not empty array'], false],
            'callback' => [
                function () {
                    return false;
                },
                false,
            ],
        ];
    }

    private function getKey(string $name)
    {
        return file_get_contents(__DIR__.'/../../fixtures/keys_formatted/'.$name);
    }

    private function getFormattedKey(string $name)
    {
        return require __DIR__.'/../../fixtures/keys_formatted/'.$name.'.php';
    }
}
