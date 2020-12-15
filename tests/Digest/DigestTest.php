<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\Digest;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\Digest\Digest;
use Yandex\Eats\Digest\Exception\UnknownDigestFormatException;

class DigestTest extends TestCase
{
    /**
     * @param $digest
     * @param $expectedString
     *
     * @dataProvider toStringDataProvider
     */
    public function testToString($digest, $expectedString)
    {
        $this->assertEquals($expectedString, (string)$digest);
    }

    /**
     * @param string $formatted
     * @param Digest $expected
     *
     * @dataProvider headersDataProvider
     */
    public function testFromHeaderFormatted(string $formatted, Digest $expected)
    {
        $this->assertEquals($expected, Digest::fromHeader($formatted));
    }

    public function testFromHeaderUnknownFormat()
    {
        $digestHeader = 'SHA-256≠7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c';

        $this->expectException(UnknownDigestFormatException::class);
        Digest::fromHeader($digestHeader);
    }

    public function toStringDataProvider()
    {
        return [
            'sha-1, lower case' => [
                new Digest('sha-1', 'bXlfYW1hemluZ19zdHJpbmc='),
                'sha-1=bXlfYW1hemluZ19zdHJpbmc=',
            ],
            'sha-1, upper case' => [
                new Digest('SHA-1', 'bXlfYW1hemluZ19zdHJpbmc='),
                'SHA-1=bXlfYW1hemluZ19zdHJpbmc=',
            ],
            'md5, lower case' => [
                new Digest('md5', 'MDVmODM5N2JjOGNjNmI3ZmVmYmIzZTcyOTA3NWQ4ZGI=='),
                'md5=MDVmODM5N2JjOGNjNmI3ZmVmYmIzZTcyOTA3NWQ4ZGI==',
            ],
        ];
    }

    public function headersDataProvider()
    {
        return [
            'with dash' => [
                'SHA-256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c',
                new Digest('SHA-256', '7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c'),
            ],
            'without dash' => [
                'SHA256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c',
                new Digest('SHA256', '7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c'),
            ],
            'case-sensitive' => [
                'sHa-256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c',
                new Digest('sHa-256', '7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c'),
            ],
            'multiple equals sign' => [
                'SHA-256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c=',
                new Digest('SHA-256', '7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c='),
            ],
        ];
    }
}
