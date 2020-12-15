<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Signature;

class SignatureTest extends TestCase
{
    private const SIGNATURE_BINARY = '🦄❤️🍔';
    private const SIGNATURE = '8J+mhOKdpO+4j/CfjZQ=';
    private const KEY_ID = 'AmazingKeyId';
    private const ALGORITHM = 'rsa-sha512';
    private const HEADERS = ['date', 'host', 'content-type', 'content-length'];

    /**
     * @var Signature
     */
    private $signature;

    protected function setUp()
    {
        parent::setUp();

        $this->signature = new Signature(
            self::KEY_ID,
            self::SIGNATURE_BINARY,
            self::ALGORITHM,
            self::HEADERS
        );
    }

    public function testBase64Encoding()
    {
        $this->assertSame(self::SIGNATURE, $this->signature->getSignature());
    }

    /**
     * @param array $headers
     * @param bool $expectedHasHeaders
     * @param array $expectedHeaders
     *
     * @dataProvider headersDataProvider
     */
    public function testHeadersProcessing(array $headers, bool $expectedHasHeaders, array $expectedHeaders)
    {
        $signature = new Signature(self::KEY_ID, self::SIGNATURE_BINARY, null, $headers);

        $this->assertEquals($expectedHasHeaders, $signature->hasHeaders());
        $this->assertEquals($expectedHeaders, $signature->getHeaders());
    }

    /**
     * @param string|null $algorithm
     * @param bool $expectedHasAlgorithm
     * @param string|null $expectedAlgorithm
     *
     * @dataProvider algorithmsDataProvider
     */
    public function testAlgorithmProcessing(?string $algorithm, bool $expectedHasAlgorithm, ?string $expectedAlgorithm)
    {
        $signature = new Signature(self::KEY_ID, self::SIGNATURE_BINARY, $algorithm, []);

        $this->assertEquals($expectedHasAlgorithm, $signature->hasAlgorithm());
        $this->assertEquals($expectedAlgorithm, $signature->getAlgorithm());
    }

    public function headersDataProvider()
    {
        return [
            'case-insensitive' => [
                ['HoSt', 'daTE'],
                true,
                ['host', 'date'],
            ],
            'empty headers' => [
                [],
                false,
                [],
            ],
            'whitespaces' => [
                [' host', 'date ', 'content-length', ' content-type '],
                true,
                ['host', 'date', 'content-length', 'content-type'],
            ],
            'not unique' => [
                ['host', 'date', 'host'],
                true,
                ['host', 'date', 'host'],
            ],
            'reorder' => [
                ['host', 'date'],
                true,
                ['host', 'date'],
            ],
        ];
    }

    public function algorithmsDataProvider()
    {
        return [
            'case-insensitive' => [
                'Rsa-sHA256',
                true,
                'rsa-sha256',
            ],
            'empty algorithm' => [
                null,
                false,
                null,
            ],
        ];
    }
}
