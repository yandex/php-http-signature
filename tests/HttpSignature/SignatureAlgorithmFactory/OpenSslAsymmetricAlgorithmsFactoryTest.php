<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\SignatureAlgorithmFactory;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\UnknownSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\OpenSslAsymmetricAlgorithmsFactory;

class OpenSslAsymmetricAlgorithmsFactoryTest extends TestCase
{
    /**
     * @param OpenSslAsymmetricAlgorithmsFactory $factory
     * @param string $algorithm
     * @param string $expectedClass
     * @dataProvider successfulMakeDataProvider
     */
    public function testSuccessfullyMake(
        OpenSslAsymmetricAlgorithmsFactory $factory,
        string $algorithm,
        string $expectedClass
    ) {
        $implementation = $factory->make($algorithm);

        $this->assertInstanceOf($expectedClass, $implementation);
    }

    /**
     * @param OpenSslAsymmetricAlgorithmsFactory $factory
     * @param string $algorithm
     *
     * @dataProvider failureMakeDataProvider
     */
    public function testFailureMake(OpenSslAsymmetricAlgorithmsFactory $factory, string $algorithm)
    {
        $this->expectException(UnknownSignatureAlgorithmException::class);
        $factory->make($algorithm);
    }

    public function successfulMakeDataProvider()
    {
        return [
            'default key types' => [
                new OpenSslAsymmetricAlgorithmsFactory(),
                'rsa-sha256',
                OpenSslBasedAsymmetricAlgorithm::class,
            ],
            'custom key types' => [
                new OpenSslAsymmetricAlgorithmsFactory(['pem']),
                'pem-sha256',
                OpenSslBasedAsymmetricAlgorithm::class,
            ],
            'mixed key types' => [
                new OpenSslAsymmetricAlgorithmsFactory(['pem', 'rsa']),
                'rsa-sha256',
                OpenSslBasedAsymmetricAlgorithm::class,
            ],
            'not sha256 algorithm' => [
                new OpenSslAsymmetricAlgorithmsFactory(),
                'rsa-md5',
                OpenSslBasedAsymmetricAlgorithm::class,
            ],
        ];
    }

    public function failureMakeDataProvider()
    {
        return [
            'not allowed key type' => [
                new OpenSslAsymmetricAlgorithmsFactory(['pem']),
                'rsa-sha256',
            ],
            'unsupported algorithm' => [
                new OpenSslAsymmetricAlgorithmsFactory(),
                'unknownAlgorithm',
            ],
        ];
    }
}
