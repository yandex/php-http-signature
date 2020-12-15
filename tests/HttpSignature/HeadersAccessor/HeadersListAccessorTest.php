<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\HeadersAccessor;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;

class HeadersListAccessorTest extends TestCase
{
    /**
     * @param HeadersListAccessor $accessor
     * @param string $header
     * @param string $expected
     *
     * @dataProvider headersDataProvider
     */
    public function testFetch(HeadersListAccessor $accessor, string $header, string $expected)
    {
        $this->assertSame($expected, $accessor->fetch($header));
    }

    public function headersDataProvider()
    {
        return [
            'request-target' => [
                new HeadersListAccessor([], 'get', '/Foo?q=1'),
                '(request-target)',
                'get /Foo?q=1',
            ],
            'empty if not exists' => [
                new HeadersListAccessor([], 'get', '/'),
                'host',
                '',
            ],
            'successful fetching' => [
                new HeadersListAccessor([], 'get', '/'),
                'host',
                '',
            ],
            'multi whitespaces' => [
                new HeadersListAccessor(['date' => 'foo  bar'], 'get', '/'),
                'date',
                'foo bar',
            ],
            'multiline' => [
                new HeadersListAccessor(['date' => "foo\n    bar"], 'get', '/'),
                'date',
                'foo bar',
            ],
            'multiple headers' => [
                new HeadersListAccessor(['date' => ['foo', 'hello']], 'get', '/'),
                'date',
                'foo, hello',
            ],
        ];
    }
}
