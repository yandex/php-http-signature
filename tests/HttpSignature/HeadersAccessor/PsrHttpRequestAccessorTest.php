<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\HeadersAccessor;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Yandex\Eats\HttpSignature\HeadersAccessor\PsrHttpRequestAccessor;

class PsrHttpRequestAccessorTest extends TestCase
{
    /**
     * @var RequestInterface|MockObject
     */
    private $psrRequestMock;

    /**
     * @var PsrHttpRequestAccessor
     */
    private $accessor;

    protected function setUp()
    {
        parent::setUp();

        $this->psrRequestMock = $this->getMockBuilder(RequestInterface::class)
            ->setMethods(['getHeaderLine', 'getMethod', 'getUri'])
            ->getMockForAbstractClass();

        $this->accessor = new PsrHttpRequestAccessor($this->psrRequestMock);
    }

    /**
     * @param string $header
     * @param string $psrReturn
     * @param string $expected
     * @param string $method
     * @param string $uri
     *
     * @dataProvider headersDataProvider
     */
    public function testFetch(
        string $header,
        string $psrReturn,
        string $expected,
        string $method = 'get',
        string $uri = '/'
    ) {
        $this->psrRequestMock
            ->method('getHeaderLine')
            ->willReturn($psrReturn);

        $this->psrRequestMock
            ->method('getMethod')
            ->willReturn($method);

        $this->psrRequestMock
            ->method('getUri')
            ->willReturn($uri);

        $this->assertSame($expected, $this->accessor->fetch($header));
    }

    public function headersDataProvider()
    {
        return [
            'request-target' => [
                '(request-target)',
                '',
                'get /Foo?q=1',
                'GET',
                '/Foo?q=1',
            ],
            'request-target independent' => [
                '(request-target)',
                'something',
                'get /Foo?q=1',
                'GET',
                '/Foo?q=1',
            ],
            'empty if not exists' => [
                'host',
                '',
                '',
            ],
            'successful fetching' => [
                'host',
                'example.com',
                'example.com',
            ],
            'multi whitespaces' => [
                'x-header',
                'foo  bar',
                'foo bar',
            ],
            'multiline' => [
                'x-header',
                "foo\n    bar",
                'foo bar',
            ],
        ];
    }
}
