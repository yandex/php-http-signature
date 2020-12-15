<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\Digest\DigestAlgorithmFactory;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\Digest\DigestAlgorithm\OpenSslBasedHashAlgorithm;
use Yandex\Eats\Digest\DigestAlgorithmFactory\OpenSslAlgorithmFactory;
use Yandex\Eats\Digest\Exception\UnknownDigestAlgorithmException;

class OpenSslAlgorithmFactoryTest extends TestCase
{
    /**
     * @var OpenSslAlgorithmFactory
     */
    private $factory;

    protected function setUp()
    {
        parent::setUp();

        $this->factory = new OpenSslAlgorithmFactory();
    }

    /**
     * @param string $algorithm
     * @dataProvider makeDataProvider
     */
    public function testMake(string $algorithm)
    {
        $this->assertInstanceOf(OpenSslBasedHashAlgorithm::class, $this->factory->make($algorithm));
    }

    public function testMakeFailure()
    {
        $this->expectException(UnknownDigestAlgorithmException::class);
        $this->factory->make('unknown');
    }

    public function makeDataProvider()
    {
        foreach (openssl_get_md_methods() as $opensslAlgorithm) {
            yield $opensslAlgorithm => [$opensslAlgorithm];
        }
    }
}
