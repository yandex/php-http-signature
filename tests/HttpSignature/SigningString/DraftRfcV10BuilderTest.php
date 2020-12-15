<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\SigningString;

use ArrayObject;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;

class DraftRfcV10BuilderTest extends TestCase
{
    /**
     * @var HeadersAccessorInterface|MockObject
     */
    private $headersAccessorMock;

    /**
     * @var array
     */
    private $headersMap;

    /**
     * @var DraftRfcV10Builder
     */
    private $builder;

    protected function setUp()
    {
        $this->headersMap = [
            ['(request-target)', 'get /foo',],
            ['Host', 'example.org'],
            ['Date', 'Tue, 07 Jun 2014 20:51:35 GMT'],
            ['X-Example', 'Example header with some whitespace.'],
            ['Cache-Control', 'max-age=60, must-revalidate'],
            ['host', 'example.org_l'],
            ['date', 'Tue, 07 Jun 2014 20:51:35 GMT_l'],
            ['HOST', 'example.org_u'],
            ['DATE', 'Tue, 07 Jun 2014 20:51:35 GMT_u'],
        ];

        $this->headersAccessorMock = $this->createMock(HeadersAccessorInterface::class);

        $this->builder = new DraftRfcV10Builder();
    }

    /**
     * @param iterable $headers
     * @param string $expected
     *
     * @dataProvider inputDataProvider
     */
    public function testBuild(iterable $headers, string $expected)
    {
        $this->headersAccessorMock
            ->method('fetch')
            ->willReturnMap($this->headersMap);

        $this->assertSame($expected, $this->builder->build($this->headersAccessorMock, $headers));
    }

    /**
     * @param iterable $headers
     *
     * @dataProvider invalidInputDataProvider
     */
    public function testFailedBuild($headers)
    {
        $this->expectException(InvalidArgumentException::class);

        (new DraftRfcV10Builder())->build($this->headersAccessorMock, $headers);
    }

    public function testAccessorThrowsException()
    {
        $exception = new RuntimeException('Fake exception');
        $this->headersAccessorMock->method('fetch')->willThrowException($exception);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Fake exception');

        $this->builder->build($this->headersAccessorMock, []);
    }

    public function testIteratorThrowsException()
    {
        $generator = function (): iterable {
            yield 'Host';
            throw new Exception('Fake exception');
        };

        $this->expectException(Exception::class);
        $this->builder->build($this->headersAccessorMock, $generator());
    }

    public function inputDataProvider()
    {
        yield 'no headers' => [
            [],
            "date: Tue, 07 Jun 2014 20:51:35 GMT_l",
        ];

        yield 'all lower' => [
            ['host', 'date'],
            "host: example.org_l\ndate: Tue, 07 Jun 2014 20:51:35 GMT_l",
        ];

        yield 'all upper' => [
            ['HOST', 'DATE'],
            "host: example.org_u\ndate: Tue, 07 Jun 2014 20:51:35 GMT_u",
        ];

        yield 'different order' => [
            ['Date', 'Host'],
            "date: Tue, 07 Jun 2014 20:51:35 GMT\nhost: example.org",
        ];

        yield 'not unique headers' => [
            ['Date', 'Host', 'Date'],
            "date: Tue, 07 Jun 2014 20:51:35 GMT\nhost: example.org\ndate: Tue, 07 Jun 2014 20:51:35 GMT",
        ];

        yield 'array as iterator' => [
            ['Host', 'Date'],
            "host: example.org\ndate: Tue, 07 Jun 2014 20:51:35 GMT",
        ];

        $iterator = new ArrayObject(['Host', 'Date']);

        yield 'iterator as iterator' => [
            $iterator,
            "host: example.org\ndate: Tue, 07 Jun 2014 20:51:35 GMT",
        ];

        $generatorCreator = function (): iterable {
            yield 'Host';
            yield 'Date';
        };

        yield 'generator as iterator' => [
            $generatorCreator(),
            "host: example.org\ndate: Tue, 07 Jun 2014 20:51:35 GMT",
        ];
    }

    public function invalidInputDataProvider()
    {
        yield 'iterator has int' => [
            ['Date', 1],
        ];

        yield 'iterator has float' => [
            ['Date', 1.1],
        ];

        yield 'iterator has callable' => [
            [
                'Date',
                function () {
                    return 1;
                },
            ],
        ];

        yield 'iterator has object' => [
            [
                'Date',
                new class
                {
                },
            ],
        ];

        yield 'iterator has array' => [
            ['Date', []],
        ];
    }
}
