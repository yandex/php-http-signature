<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\Key;

use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Key\AbstractOpenSslPemKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;

class OpenSslPemKeysTest extends TestCase
{
    private const FIXTURE_PRIVATE = 'private.pem';
    private const FIXTURE_PRIVATE_ENC = 'private_encrypted.pem';
    private const FIXTURE_PUBLIC = 'public.pem';

    private const PASSPHRASE = 'test123';

    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL is not supported.');
        }
    }

    /**
     * @param OpenSslPemPrivateKey|OpenSslPemPublicKey|MockObject $key
     *
     * @dataProvider closedKeysDataProvider
     * @dataProvider openedKeysDataProvider
     */
    public function testOpenKeySuccessful($key)
    {
        $resource = $key->open();

        $this->assertIsResource($resource);
        $this->assertSame('OpenSSL key', get_resource_type($resource));
        $this->assertTrue($key->isOpened());
        $this->assertSame($resource, $key->getOpenedKey());
    }

    /**
     * @param OpenSslPemPrivateKey|OpenSslPemPublicKey|MockObject $key
     *
     * @dataProvider openedKeysDataProvider
     * @dataProvider closedKeysDataProvider
     */
    public function testCloseSuccessful($key)
    {
        $key->close();
        $this->assertFalse($key->isOpened());
    }

    /**
     * @param OpenSslPemPrivateKey|OpenSslPemPublicKey|MockObject $key
     *
     * @dataProvider closedKeysDataProvider
     */
    public function testFailureGetOpenedKey($key)
    {
        $this->expectException(LogicException::class);
        $key->getOpenedKey();
    }

    /**
     * @param OpenSslPemPrivateKey|OpenSslPemPublicKey|MockObject $key
     * @param string $expected
     *
     * @dataProvider keysToStringDataProvider
     */
    public function testToString($key, string $expected)
    {
        $this->assertSame($expected, (string)$key);
    }

    public function closedKeysDataProvider()
    {
        return [
            'closed public pem' => [
                $this->mockPublic($this->loadFixture(self::FIXTURE_PUBLIC)),
            ],
            'closed private pem' => [
                $this->mockPrivate($this->loadFixture(self::FIXTURE_PRIVATE), ''),
            ],
            'closed encrypted private pem' => [
                $this->mockPrivate($this->loadFixture(self::FIXTURE_PRIVATE_ENC), self::PASSPHRASE),
            ],
        ];
    }

    public function openedKeysDataProvider()
    {
        return [
            'already opened public pem' => [
                $this->mockPublic($this->loadFixture(self::FIXTURE_PUBLIC), null, true),
            ],
            'already opened private pem' => [
                $this->mockPrivate($this->loadFixture(self::FIXTURE_PRIVATE), '', null, true),
            ],
        ];
    }

    public function keysToStringDataProvider()
    {
        return [
            'public pem' => [
                $this->mockPublic($this->loadFixture('public.pem')),
                $this->loadFixture('public.pem'),
            ],
            'private pem' => [
                $this->mockPrivate($this->loadFixture('private.pem'), ''),
                $this->loadFixture('private.pem'),
            ],
        ];
    }

    /**
     * @param string $key
     * @param array|null $methods
     * @param bool $open
     *
     * @return OpenSslPemPublicKey|MockObject
     */
    private function mockPublic(string $key, ?array $methods = null, bool $open = false)
    {
        return $this->mockKey(
            OpenSslPemPublicKey::class,
            ['public_pem', $key],
            $methods,
            $open
        );
    }

    private function mockKey(string $class, array $args, ?array $methods = null, bool $open = false)
    {
        /** @var AbstractOpenSslPemKey|MockObject $mock */
        $mock = $this->getMockBuilder($class)
            ->setConstructorArgs($args)
            ->setMethods($methods)
            ->getMock();

        if ($open) {
            $mock->open();
        }

        return $mock;
    }

    private function loadFixture(string $keyName)
    {
        return file_get_contents(__DIR__.'/../../fixtures/keys/'.$keyName);
    }

    /**
     * @param string $key
     * @param string $passphrase
     * @param array|null $methods
     * @param bool $open
     *
     * @return OpenSslPemPrivateKey|MockObject
     */
    private function mockPrivate(
        string $key,
        string $passphrase,
        ?array $methods = null,
        bool $open = false
    ) {
        return $this->mockKey(
            OpenSslPemPrivateKey::class,
            ['private_pem', $key, $passphrase],
            $methods,
            $open
        );
    }
}
