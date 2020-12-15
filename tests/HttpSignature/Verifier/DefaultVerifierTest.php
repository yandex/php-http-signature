<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\Verifier;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\KeyNotFoundException;
use Yandex\Eats\HttpSignature\Exception\KeyNotMatchException;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Exception\UnknownSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\Exception\UnsupportedSignatureAlgorithmException;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\KeyProvider\KeyProviderInterface;
use Yandex\Eats\HttpSignature\Signature;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\SignatureAlgorithmInterface;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\SignatureAlgorithmFactoryInterface;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;
use Yandex\Eats\HttpSignature\Verifier\DefaultVerifier;

class DefaultVerifierTest extends TestCase
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
     * @var KeyProviderInterface|MockObject
     */
    private $keyProviderMock;

    /**
     * @var SignatureAlgorithmInterface|MockObject
     */
    private $signAlgorithmMock;

    /**
     * @var SignatureAlgorithmFactoryInterface|MockObject
     */
    private $algorithmFactoryMock;

    /**
     * @var DraftRfcV10Builder|MockObject
     */
    private $signingStringBuilderMock;

    /**
     * @var Signature
     */
    private $signature;

    /**
     * @var HeadersListAccessor
     */
    private $headersAccessor;

    /**
     * @var DefaultVerifier
     */
    private $verifier;

    protected function setUp()
    {
        $this->signature = new Signature(
            self::KEY_ID,
            self::SIGNATURE_BINARY,
            self::SIGNATURE_ALGORITHM,
            self::HEADERS
        );

        $this->headersAccessor = new HeadersListAccessor(
            self::REQUEST_HEADERS,
            self::REQUEST_METHOD,
            self::REQUEST_URI
        );

        $this->keyMock = $this->createMock(KeyInterface::class);
        $this->keyProviderMock = $this->createMock(KeyProviderInterface::class);

        $this->signAlgorithmMock = $this->createMock(SignatureAlgorithmInterface::class);
        $this->algorithmFactoryMock = $this->createMock(SignatureAlgorithmFactoryInterface::class);

        $this->signingStringBuilderMock = $this->createMock(DraftRfcV10Builder::class);

        $this->verifier = new DefaultVerifier(
            $this->algorithmFactoryMock,
            $this->keyProviderMock,
            $this->signingStringBuilderMock
        );
    }

    /**
     * @param bool $expected
     *
     * @dataProvider verifyDataProvider
     */
    public function testVerify(bool $expected)
    {
        $this->signingStringBuilderMock
            ->expects(self::once())
            ->method('build')
            ->with($this->headersAccessor, $this->signature->getHeaders())
            ->willReturn(self::SIGNING_STRING);

        $this->algorithmFactoryMock
            ->expects(self::once())
            ->method('make')
            ->with($this->signature->getAlgorithm())
            ->willReturn($this->signAlgorithmMock);

        $this->keyProviderMock
            ->expects(self::once())
            ->method('fetch')
            ->with($this->signature->getKeyId())
            ->willReturn($this->keyMock);

        $this->signAlgorithmMock
            ->expects(self::once())
            ->method('verify')
            ->with($this->keyMock, self::SIGNING_STRING, $this->signature->getBinarySignature())
            ->willReturn($expected);

        $this->assertSame($expected, $this->verifier->verify($this->signature, $this->headersAccessor));
    }

    /**
     * @param callable $mockGetter Data provider called before setUp, so we need to access mock after test started.
     * @param string $method
     * @param \Throwable $exception
     *
     * @dataProvider exceptionsDataProvider
     */
    public function testVerifyFailed(callable $mockGetter, string $method, \Throwable $exception)
    {
        $this->algorithmFactoryMock
            ->expects(self::once())
            ->method('make')
            ->with($this->signature->getAlgorithm())
            ->willReturn($this->signAlgorithmMock);

        /** @var MockObject $mock */
        $mock = $mockGetter($this);
        $mock->method($method)->willThrowException($exception);

        $this->expectException(get_class($exception));
        $this->expectExceptionMessage($exception->getMessage());

        $this->verifier->verify($this->signature, $this->headersAccessor);
    }

    public function verifyDataProvider()
    {
        return [
            'verified successfully' => [true],
            'verification failed' => [false],
        ];
    }

    public function exceptionsDataProvider()
    {
        return [
            'unknown signature algorithm' => [
                function (DefaultVerifierTest $test): MockObject {
                    return $test->algorithmFactoryMock;
                },
                'make',
                new UnknownSignatureAlgorithmException('Fake UnknownSignatureAlgorithm'),
            ],
            'unsupported signature algorithm' => [
                function (DefaultVerifierTest $test): MockObject {
                    return $test->algorithmFactoryMock;
                },
                'make',
                new UnsupportedSignatureAlgorithmException('Fake UnsupportedSignatureAlgorithm'),
            ],
            'key not found' => [
                function (DefaultVerifierTest $test): MockObject {
                    return $test->keyProviderMock;
                },
                'fetch',
                new KeyNotFoundException('Fake KeyNotFound'),
            ],
            'key not match' => [
                function (DefaultVerifierTest $test): MockObject {
                    return $test->signAlgorithmMock;
                },
                'verify',
                new KeyNotMatchException('Fake KeyNotMatch'),
            ],
            'signature corrupted' => [
                function (DefaultVerifierTest $test): MockObject {
                    return $test->signAlgorithmMock;
                },
                'verify',
                new SignatureCorruptedException('Fake SignatureCorrupted'),
            ],
        ];
    }
}
