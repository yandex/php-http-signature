<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\Clerk;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Clerk\DefaultClerk;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Signature;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\SignatureAlgorithmInterface;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;

class DefaultClerkTest extends TestCase
{
    private const KEY_ID = 'rsa_v1';

    private const HEADERS = ['Host', 'Date'];

    private const REQUEST_HEADERS = ['Host' => 'example.com', 'Date' => 'Tue, 07 Jun 2014 20:51:35 GMT'];
    private const REQUEST_METHOD = 'get';
    private const REQUEST_URI = '/foo?q=1';

    private const SIGNING_STRING = <<<TXT
(request-target): get /foo?q=1
host: example.com
date: Tue, 07 Jun 2014 20:51:35 GMT
TXT;

    private const SIGNATURE_BINARY = 'some bytes 🍔';
    private const SIGNATURE_ALGORITHM = 'rsa-sha512';

    /**
     * @var KeyInterface|MockObject
     */
    private $keyMock;

    /**
     * @var SignatureAlgorithmInterface|MockObject
     */
    private $signAlgorithmMock;

    /**
     * @var DraftRfcV10Builder|MockObject
     */
    private $signingStringBuilderMock;

    /**
     * @var HeadersListAccessor
     */
    private $headersAccessor;

    /**
     * @var DefaultClerk
     */
    private $clerk;

    /**
     * @var Signature
     */
    private $signature;

    protected function setUp()
    {
        $this->headersAccessor = new HeadersListAccessor(
            self::REQUEST_HEADERS,
            self::REQUEST_METHOD,
            self::REQUEST_URI
        );

        $this->keyMock = $this->createMock(KeyInterface::class);
        $this->signAlgorithmMock = $this->createMock(SignatureAlgorithmInterface::class);
        $this->signingStringBuilderMock = $this->createMock(DraftRfcV10Builder::class);

        $this->clerk = new DefaultClerk($this->signAlgorithmMock, $this->signingStringBuilderMock);

        $this->signature = new Signature(
            self::KEY_ID,
            self::SIGNATURE_BINARY,
            self::SIGNATURE_ALGORITHM,
            self::HEADERS
        );
    }

    public function testSign()
    {
        $this->signingStringBuilderMock
            ->expects(self::once())
            ->method('build')
            ->with($this->headersAccessor, self::HEADERS)
            ->willReturn(self::SIGNING_STRING);

        $this->signAlgorithmMock
            ->expects(self::once())
            ->method('sign')
            ->with($this->keyMock, self::SIGNING_STRING)
            ->willReturn(self::SIGNATURE_BINARY);

        $this->signAlgorithmMock
            ->expects(self::once())
            ->method('getAlgorithmName')
            ->with()
            ->willReturn(self::SIGNATURE_ALGORITHM);

        $this->keyMock
            ->expects(self::once())
            ->method('getId')
            ->with()
            ->willReturn(self::KEY_ID);

        $this->assertEquals(
            $this->signature,
            $this->clerk->sign($this->keyMock, $this->headersAccessor, self::HEADERS)
        );
    }

    /**
     * @param callable $mockGetter Data provider called before setUp, so we need to access mock after test started.
     * @param string $method
     * @param \Throwable $exception
     *
     * @dataProvider exceptionsDataProvider
     */
    public function testSignFailed(callable $mockGetter, string $method, \Throwable $exception)
    {
        /** @var MockObject $mock */
        $mock = $mockGetter($this);
        $mock->method($method)->willThrowException($exception);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->clerk->sign($this->keyMock, $this->headersAccessor, self::HEADERS);
    }

    public function exceptionsDataProvider()
    {
        return [
            'key not match' => [
                function (DefaultClerkTest $test): MockObject {
                    return $test->signAlgorithmMock;
                },
                'sign',
                new KeyNotMatchException('Fake KeyNotMatch'),
            ],
            'signature corrupted' => [
                function (DefaultClerkTest $test): MockObject {
                    return $test->signAlgorithmMock;
                },
                'sign',
                new SignatureCorruptedException('Fake SignatureCorrupted'),
            ],
        ];
    }
}
