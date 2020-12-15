<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\Digest\DigestAlgorithm;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\Digest\Digest;
use Yandex\Eats\Digest\DigestAlgorithm\OpenSslBasedHashAlgorithm;
use Yandex\Eats\Digest\Exception\DigestFailedException;
use Yandex\Eats\Digest\Exception\UnsupportedDigestAlgorithmException;

class OpenSslBasedHashAlgorithmTest extends TestCase
{
    /**
     * @param $hashAlgorithm
     * @param $expectedOpenSslAlgoAlias
     *
     * @dataProvider successConstructDataProvider
     */
    public function testConstruct($hashAlgorithm, $expectedOpenSslAlgoAlias)
    {
        $algorithm = new OpenSslBasedHashAlgorithm($hashAlgorithm);

        $this->assertSame($hashAlgorithm, $algorithm->getHashAlgorithm());
        $this->assertTrue(strcasecmp($expectedOpenSslAlgoAlias, $algorithm->getMappedHashAlgorithm()) === 0);
        $this->assertContains($algorithm->getMappedHashAlgorithm(), openssl_get_md_methods());
    }

    public function testConstructFailure()
    {
        $this->expectException(UnsupportedDigestAlgorithmException::class);
        new OpenSslBasedHashAlgorithm('abrakadabra');
    }

    /**
     * @param $algorithm
     * @param $string
     * @param $expected
     *
     * @dataProvider hashSuccessDataProvider
     */
    public function testHashSuccessful($algorithm, $string, $expected)
    {
        $algorithm = new OpenSslBasedHashAlgorithm($algorithm);

        $this->assertEquals($expected, $algorithm->hash($string));
    }

    public function testHashFailure()
    {
        /** @var OpenSslBasedHashAlgorithm|MockObject $mock */
        $mock = $this->getMockBuilder(OpenSslBasedHashAlgorithm::class)
            ->disableOriginalConstructor()
            ->setMethods(null)
            ->getMock();

        $this->expectException(DigestFailedException::class);
        $mock->hash('test_data');
    }

    public function successConstructDataProvider()
    {
        $algorithms = openssl_get_md_methods();

        $data = [];
        foreach ($algorithms as $algorithm) {
            $alias = $algorithm;

            // change case of random letters
            for ($i = 0; $i < strlen($algorithm); ++$i) {
                $alias[$i] = rand(0, 1) === 1 ? strtoupper($algorithm[$i]) : strtolower($algorithm[$i]);
            }

            $data[$alias] = [$alias, $algorithm];
        }

        return $data;
    }

    public function hashSuccessDataProvider()
    {
        $strings = [
            'just string' => 'my amazing test string',
            'string with emojis' => '🍔',
            'big json' => file_get_contents(__DIR__.'/../../fixtures/big.json'),
        ];

        $algorithms = openssl_get_md_methods();

        $data = [];
        foreach ($algorithms as $algorithm) {
            foreach ($strings as $stringName => $string) {
                $expectedHash = openssl_digest($string, $algorithm);
                $expectedAlgorithmName = $algorithm;

                $caseName = sprintf('%s: %s', $algorithm, $stringName);
                $data[$caseName] = [
                    $algorithm,
                    $string,
                    new Digest($expectedAlgorithmName, $expectedHash),
                ];
            }
        }

        return $data;
    }

    protected function setUp()
    {
        parent::setUp();

        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('openssl is not supported');
        }
    }
}
