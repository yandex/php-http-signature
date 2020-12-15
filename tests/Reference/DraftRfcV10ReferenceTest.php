<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\Reference;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Clerk\ClerkInterface;
use Yandex\Eats\HttpSignature\Clerk\DefaultClerk;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;
use Yandex\Eats\HttpSignature\KeyLoader\KeyLoaderInterface;
use Yandex\Eats\HttpSignature\KeyLoader\OpenSslPemKeyLoader;
use Yandex\Eats\HttpSignature\KeyProvider\DefaultKeyProvider;
use Yandex\Eats\HttpSignature\KeyProvider\KeyProviderInterface;
use Yandex\Eats\HttpSignature\KeyStorage\ArrayBasedKeyStorage;
use Yandex\Eats\HttpSignature\KeyStorage\KeyStorageInterface;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\OpenSslAsymmetricAlgorithmsFactory;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\SignatureAlgorithmFactoryInterface;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SignatureMarshaller\SignatureMarshallerInterface;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;
use Yandex\Eats\HttpSignature\SigningString\SigningStringBuilderInterface;
use Yandex\Eats\HttpSignature\Verifier\DefaultVerifier;
use Yandex\Eats\HttpSignature\Verifier\VerifierInterface;

/**
 * Reference tests for Draft RFC v10 implementation.
 * {@see https://tools.ietf.org/html/draft-cavage-http-signatures-10#appendix-C}
 */
class DraftRfcV10ReferenceTest extends TestCase
{
    private const REFERENCE_KEY_ID = 'Test';
    private const REFERENCE_KEY_TYPE = 'rsa';
    private const REFERENCE_SIGN_ALGORITHM = 'rsa-sha256';

    private const FIXTURES_PATH = __DIR__.'/../fixtures/reference/';

    /**
     * @var KeyInterface
     */
    private $privateKey;

    /**
     * @var KeyInterface
     */
    private $publicKey;

    /**
     * @var HeadersAccessorInterface
     */
    private $headersAccessor;

    /**
     * @var SignatureAlgorithmFactoryInterface
     */
    private $algorithmFactory;

    /**
     * @var ClerkInterface
     */
    private $clerk;

    /**
     * @var VerifierInterface
     */
    private $verifier;

    /**
     * @var SigningStringBuilderInterface
     */
    private $signingStringBuilder;

    /**
     * @var SignatureMarshallerInterface
     */
    private $marshaller;

    /**
     * @var KeyStorageInterface
     */
    private $keyStorage;

    /**
     * @var KeyLoaderInterface
     */
    private $keyLoader;

    /**
     * @var KeyProviderInterface
     */
    private $keyProvider;

    protected function setUp()
    {
        parent::setUp();

        $this->marshaller = new DraftRfcV10Marshaller();
        $this->signingStringBuilder = new DraftRfcV10Builder();

        $this->privateKey = new OpenSslPemPrivateKey(
            self::REFERENCE_KEY_ID,
            file_get_contents(self::FIXTURES_PATH.'/private.pem')
        );

        $this->publicKey = new OpenSslPemPublicKey(
            self::REFERENCE_KEY_ID,
            file_get_contents(self::FIXTURES_PATH.'/public.pem')
        );

        $storageKey = [
            'type' => 'pem',
            'is_public' => true,
            'content' => (string)$this->publicKey,
        ];

        $this->keyStorage = new ArrayBasedKeyStorage([self::REFERENCE_KEY_ID => $storageKey]);
        $this->keyLoader = new OpenSslPemKeyLoader();
        $this->keyProvider = new DefaultKeyProvider($this->keyStorage, [$this->keyLoader]);

        $this->algorithmFactory = new OpenSslAsymmetricAlgorithmsFactory([self::REFERENCE_KEY_TYPE]);

        $this->clerk = new DefaultClerk(
            $this->algorithmFactory->make(self::REFERENCE_SIGN_ALGORITHM),
            $this->signingStringBuilder
        );

        $this->verifier = new DefaultVerifier(
            $this->algorithmFactory,
            $this->keyProvider,
            $this->signingStringBuilder
        );

        // Reference HTTP request:
        // POST /foo?param=value&pet=dog HTTP/1.1
        // Host: example.com
        // Date: Sun, 05 Jan 2014 21:31:40 GMT
        // Content-Type: application/json
        // Digest: SHA-256=X48E9qOokqqrvdts8nOJRJN3OWDUoyWxBf7kbu9DBPE=
        // Content-Length: 18
        //
        // {"hello": "world"}
        $this->headersAccessor = new HeadersListAccessor(
            [
                'Host' => 'example.com',
                'Date' => 'Sun, 05 Jan 2014 21:31:40 GMT',
                'Content-Type' => 'application/json',
                'Digest' => 'SHA-256=X48E9qOokqqrvdts8nOJRJN3OWDUoyWxBf7kbu9DBPE=',
                'Content-Length' => '18',
            ],
            'POST',
            '/foo?param=value&pet=dog'
        );
    }

    public function testKeysDetermination()
    {
        $this->assertEquals(self::REFERENCE_KEY_TYPE, $this->privateKey->getType());
        $this->assertEquals(self::REFERENCE_KEY_ID, $this->privateKey->getId());

        $this->assertEquals(self::REFERENCE_KEY_TYPE, $this->publicKey->getType());
        $this->assertEquals(self::REFERENCE_KEY_ID, $this->publicKey->getId());
    }

    /**
     * @param array $headers
     * @param string $expected
     *
     * @dataProvider toSignDataProvider
     */
    public function testSign(array $headers, string $expected)
    {
        $signature = $this->clerk->sign($this->privateKey, $this->headersAccessor, $headers);

        $this->assertSame($expected, $this->marshaller->marshall($signature));
    }

    /**
     * @param string $signature
     * @dataProvider toVerifyDataProvider
     */
    public function testVerify(string $signature)
    {
        $signatureDto = $this->marshaller->unmarshall($signature);

        $this->assertTrue($this->verifier->verify($signatureDto, $this->headersAccessor));
    }

    public function toSignDataProvider()
    {
        return [
            'C.1. Default Test' => [
                [],
                'keyId="Test",algorithm="rsa-sha256",signature="SjWJWbWN7i0wzBvtPl8rbASWz5xQW6mcJmn+ibttBqtifLN7Sazz6m79cNfwwb8DMJ5cou1s7uEGKKCs+FLEEaDV5lp7q25WqS+lavg7T8hc0GppauB6hbgEKTwblDHYGEtbGmtdHgVCk9SuS13F0hZ8FD0k/5OxEPXe5WozsbM="',
            ],
            'C.2. Basic Test' => [
                ['(request-target)', 'host', 'date'],
                'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date",signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
            ],
            'C.3. All Headers Test' => [
                ['(request-target)', 'host', 'date', 'content-type', 'digest', 'content-length'],
                'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
            ],
        ];
    }

    public function toVerifyDataProvider()
    {
        return [
            'C.1. Default Test' => [
                'keyId="Test",algorithm="rsa-sha256",signature="SjWJWbWN7i0wzBvtPl8rbASWz5xQW6mcJmn+ibttBqtifLN7Sazz6m79cNfwwb8DMJ5cou1s7uEGKKCs+FLEEaDV5lp7q25WqS+lavg7T8hc0GppauB6hbgEKTwblDHYGEtbGmtdHgVCk9SuS13F0hZ8FD0k/5OxEPXe5WozsbM="',
            ],
            'C.2. Basic Test' => [
                'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date",signature="qdx+H7PHHDZgy4y/Ahn9Tny9V3GP6YgBPyUXMmoxWtLbHpUnXS2mg2+SbrQDMCJypxBLSPQR2aAjn7ndmw2iicw3HMbe8VfEdKFYRqzic+efkb3nndiv/x1xSHDJWeSWkx3ButlYSuBskLu6kd9Fswtemr3lgdDEmn04swr2Os0="',
            ],
            'C.3. All Headers Test' => [
                'keyId="Test",algorithm="rsa-sha256",headers="(request-target) host date content-type digest content-length",signature="vSdrb+dS3EceC9bcwHSo4MlyKS59iFIrhgYkz8+oVLEEzmYZZvRs8rgOp+63LEM3v+MFHB32NfpB2bEKBIvB1q52LaEUHFv120V01IL+TAD48XaERZFukWgHoBTLMhYS2Gb51gWxpeIq8knRmPnYePbF5MOkR0Zkly4zKH7s1dE="',
            ],
        ];
    }
}

