<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\SignatureAlgorithm;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\UnsupportedSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;

class OpenSslBasedAsymmetricAlgorithmTest extends TestCase
{
    private const SIGNATURE_ALGORITHMS_MAPPING = [
        'md5' => OPENSSL_ALGO_MD5,
        'MD5' => OPENSSL_ALGO_MD5,
        'md-5' => OPENSSL_ALGO_MD5,
        'MD-5' => OPENSSL_ALGO_MD5,

        'md4' => OPENSSL_ALGO_MD4,
        'MD4' => OPENSSL_ALGO_MD4,
        'md-4' => OPENSSL_ALGO_MD4,
        'MD-4' => OPENSSL_ALGO_MD4,

        'sha1' => OPENSSL_ALGO_SHA1,
        'SHA1' => OPENSSL_ALGO_SHA1,
        'sha-1' => OPENSSL_ALGO_SHA1,
        'SHA-1' => OPENSSL_ALGO_SHA1,

        'sha224' => OPENSSL_ALGO_SHA224,
        'SHA224' => OPENSSL_ALGO_SHA224,
        'sha-224' => OPENSSL_ALGO_SHA224,
        'SHA-224' => OPENSSL_ALGO_SHA224,

        'sha256' => OPENSSL_ALGO_SHA256,
        'SHA256' => OPENSSL_ALGO_SHA256,
        'sha-256' => OPENSSL_ALGO_SHA256,
        'SHA-256' => OPENSSL_ALGO_SHA256,

        'sha384' => OPENSSL_ALGO_SHA384,
        'SHA384' => OPENSSL_ALGO_SHA384,
        'sha-384' => OPENSSL_ALGO_SHA384,
        'SHA-384' => OPENSSL_ALGO_SHA384,

        'sha512' => OPENSSL_ALGO_SHA512,
        'SHA512' => OPENSSL_ALGO_SHA512,
        'sha-512' => OPENSSL_ALGO_SHA512,
        'SHA-512' => OPENSSL_ALGO_SHA512,

        'rmd160' => OPENSSL_ALGO_RMD160,
        'RMD160' => OPENSSL_ALGO_RMD160,
        'rmd-160' => OPENSSL_ALGO_RMD160,
        'RMD-160' => OPENSSL_ALGO_RMD160,
    ];

    private const SIGNATURE_ALGORITHMS = [
        'md5',
        'md4',
        'sha1',
        'sha224',
        'sha256',
        'sha384',
        'sha512',
        'rmd160',
    ];

    private const KEY_TYPE = 'rsa';

    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('openssl is not supported');
        }
    }

    /**
     * @param string $hashAlgorithm
     * @param int $expectedMappedAlgorithm
     * @param string $exptectedAlgorithmName
     *
     * @dataProvider successConstructDataProvider
     */
    public function testConstruct(
        string $hashAlgorithm,
        int $expectedMappedAlgorithm,
        string $exptectedAlgorithmName
    ) {
        $algorithm = new OpenSslBasedAsymmetricAlgorithm(self::KEY_TYPE, $hashAlgorithm);

        $this->assertSame($hashAlgorithm, $algorithm->getSignAlgorithm());
        $this->assertSame($expectedMappedAlgorithm, $algorithm->getMappedSignAlgorithm());
        $this->assertSame($exptectedAlgorithmName, $algorithm->getAlgorithmName());
    }

    public function testConstructFailure()
    {
        $this->expectException(UnsupportedSignatureAlgorithmException::class);
        new OpenSslBasedAsymmetricAlgorithm(self::KEY_TYPE, 'abrakadabra');
    }

    /**
     * @param resource $keyResource
     * @param OpenSslPemPrivateKey|MockObject $key
     * @param string $hashAlgorithm
     * @param string $data
     *
     * @dataProvider signDataProvider
     */
    public function testSuccessfulSign($keyResource, $key, $hashAlgorithm, $data)
    {
        $key->expects($this->atLeastOnce())
            ->method('open')
            ->willReturn($keyResource);

        $key->expects($this->atLeastOnce())
            ->method('close');

        $algorithm = new OpenSslBasedAsymmetricAlgorithm($key->getType(), $hashAlgorithm);

        $actual = $algorithm->sign($key, $data);
        openssl_sign($data, $expected, $keyResource, $hashAlgorithm);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param resource $keyResource
     * @param OpenSslPemPrivateKey|MockObject $key
     * @param string $hashAlgorithm
     * @param string $data
     * @param $signature
     * @param bool $expected
     *
     * @dataProvider verifyDataProvider
     */
    public function testSuccessfulVerify($keyResource, $key, $hashAlgorithm, $data, $signature, bool $expected)
    {
        $key->expects($this->atLeastOnce())
            ->method('open')
            ->willReturn($keyResource);

        $key->expects($this->atLeastOnce())
            ->method('close');

        $algorithm = new OpenSslBasedAsymmetricAlgorithm($key->getType(), $hashAlgorithm);

        $actual = $algorithm->verify($key, $data, $signature);

        $this->assertSame($expected, $actual);
    }

    /**
     * @param $key
     *
     * @dataProvider invalidToSignKeyDataProvider
     */
    public function testSignWithInvalidKey($key)
    {
        /** @var OpenSslBasedAsymmetricAlgorithm|MockObject $algorithm */
        $algorithm = $this->getMockBuilder(OpenSslBasedAsymmetricAlgorithm::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->expectException(KeyNotMatchException::class);
        $algorithm->sign($key, 'data');
    }

    /**
     * @param $key
     *
     * @dataProvider invalidToVerifyKeyDataProvider
     */
    public function testVerifyWithInvalidKey($key)
    {
        /** @var OpenSslBasedAsymmetricAlgorithm|MockObject $algorithm */
        $algorithm = $this->getMockBuilder(OpenSslBasedAsymmetricAlgorithm::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->expectException(KeyNotMatchException::class);
        $algorithm->verify($key, 'data', 'sign');
    }

    public function invalidToSignKeyDataProvider()
    {
        return [
            'not pem key' => [$this->getMockBuilder(KeyInterface::class)->getMock()],
            'public key' => [
                $this->getMockBuilder(OpenSslPemPublicKey::class)->disableOriginalConstructor()->getMock(),
            ],
        ];
    }

    public function invalidToVerifyKeyDataProvider()
    {
        return [
            'not pem key' => [$this->getMockBuilder(KeyInterface::class)->getMock()],
            'private key' => [
                $this->getMockBuilder(OpenSslPemPrivateKey::class)->disableOriginalConstructor()->getMock(),
            ],
        ];
    }

    public function verifyDataProvider()
    {
        $privateKey = $this->loadKey('private.pem');
        $privatekeyResource = openssl_get_privatekey($privateKey);

        $data = [];
        foreach (self::SIGNATURE_ALGORITHMS as $algorithm) {
            $string = uniqid();
            if (!openssl_sign($string, $signature, $privatekeyResource, $algorithm)) {
                trigger_error('test openssl_sign returned false, it is not valid behaviour.');
            }

            $data[sprintf('%s, positive', $algorithm)] = [
                openssl_pkey_get_public($this->loadKey('public.pem')),
                $this->getMockBuilder(OpenSslPemPublicKey::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['open', 'close', 'getType'])
                    ->getMock(),
                $algorithm,
                $string,
                $signature,
                true,
            ];

            $data[sprintf('%s, negative', $algorithm)] = [
                openssl_pkey_get_public($this->loadKey('public.pem')),
                $this->getMockBuilder(OpenSslPemPublicKey::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['open', 'close', 'getType'])
                    ->getMock(),
                $algorithm,
                $string,
                'not real signature',
                false,
            ];
        }

        return $data;
    }

    public function signDataProvider()
    {
        $keys = [
            'private' => [
                openssl_get_privatekey($this->loadKey('private.pem')),
                $this->getMockBuilder(OpenSslPemPrivateKey::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['open', 'close', 'getType'])
                    ->getMock(),
            ],
            'encrypted private' => [
                openssl_get_privatekey($this->loadKey('private_encrypted.pem'), 'test123'),
                $this->getMockBuilder(OpenSslPemPrivateKey::class)
                    ->disableOriginalConstructor()
                    ->setMethods(['open', 'close', 'getType'])
                    ->getMock(),
            ],
        ];

        $data = [];
        foreach (self::SIGNATURE_ALGORITHMS as $algorithm) {
            foreach ($keys as $keyName => $pair) {
                [$resource, $key] = $pair;

                $data[sprintf('%s, %s', $keyName, $algorithm)] = [$resource, $key, $algorithm, uniqid()];
            }
        }

        return $data;
    }

    public function successConstructDataProvider()
    {
        $data = [];
        foreach (self::SIGNATURE_ALGORITHMS_MAPPING as $name => $mapped) {
            $data[$name] = [$name, $mapped, sprintf('%s-%s', self::KEY_TYPE, $name)];
        }

        return $data;
    }

    private function loadKey(string $keyName)
    {
        return file_get_contents(__DIR__.'/../../fixtures/keys/'.$keyName);
    }
}
