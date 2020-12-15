<?php
declare(strict_types=1);

namespace Yandex\Eats\Tests\HttpSignature\SignatureMarshaller;

use PHPUnit\Framework\TestCase;
use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Signature;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;

class DraftRfcV10MarshallerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            random_bytes(8);
        } catch (\Exception $e) {
            $this->markTestSkipped('random_bytes not supported but required for signature test.');
        }
    }

    /**
     * @param Signature $signature
     * @param string $expected
     *
     * @dataProvider signaturesDataProvider
     */
    public function testMarshall(Signature $signature, string $expected)
    {
        $marshaller = new DraftRfcV10Marshaller();

        $this->assertSame($expected, $marshaller->marshall($signature));
    }

    /**
     * @param string $signature
     * @param Signature $expected
     *
     * @dataProvider stringsDataProvider
     */
    public function testSuccessfulUnmarshall(string $signature, Signature $expected)
    {
        $marshaller = new DraftRfcV10Marshaller();

        $this->assertEquals($expected, $marshaller->unmarshall($signature));
    }

    /**
     * @param string $signature
     *
     * @dataProvider invalidStringsDataProvider
     */
    public function testFailureUnmarshall(string $signature)
    {
        $marshaller = new DraftRfcV10Marshaller();

        $this->expectException(SignatureCorruptedException::class);
        $marshaller->unmarshall($signature);
    }

    public function invalidStringsDataProvider()
    {
        $bytes = random_bytes(rand(256, 4096));
        $sign = base64_encode($bytes);

        return [
            'without key id' => [
                'algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
            ],
            'without signature' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date"',
            ],
            'invalid signature encoding' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="not_base64"',
            ],
            'invalid input' => [
                'hello',
            ],
            'not comma delimiter' => [
                'keyId="some-key";"algorithm="RSA-SHA256";headers="date";signature="'.$sign.'"',
            ],
            'no value in optional part' => [
                'keyId="some-key";"algorithm="RSA-SHA256";headers;signature="'.$sign.'"',
            ],
        ];
    }

    public function stringsDataProvider()
    {
        $bytes = random_bytes(rand(256, 4096));
        $sign = base64_encode($bytes);

        return [
            'simple signature, one header' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'simple signature, multiple headers' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date host (request-target)",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date', 'host', '(request-target)']),
            ],
            'simple signature, save headers order' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="(request-target) date host",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['(request-target)', 'date', 'host']),
            ],
            'simple signature, headers case-insensitive' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="(request-target) date host",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['(ReQuEsT-tArGEt)', 'Date', 'host']),
            ],
            'simple signature, not unique headers' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date host (request-target) date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date', 'host', '(request-target)', 'date']),
            ],
            'simple signature, fingerprint as key id' => [
                'keyId="ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff', $bytes, 'RSA-SHA256', ['date']),
            ],
            'simple signature, algorithm case-sensitive' => [
                'keyId="some-key",algorithm="RSA-sha256",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-sha256', ['date']),
            ],
            'simple signature, key id case-sensitive' => [
                'keyId="sOmE-KEy",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('sOmE-KEy', $bytes, 'RSA-SHA256', ['date']),
            ],
            'simple signature, signature case-sensitive' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="c3RhdGljX3NpZ25hdHVyZQ=="',
                new Signature('some-key', 'static_signature', 'RSA-SHA256', ['date']),
            ],
            'without headers' => [
                'keyId="some-key",algorithm="RSA-SHA256",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256'),
            ],
            'without algorithm' => [
                'keyId="some-key",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, null, ['date']),
            ],
            'required only' => [
                'keyId="some-key",signature="'.$sign.'"',
                new Signature('some-key', $bytes, null),
            ],
            'duplicated key id' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'",keyId="other-id"',
                new Signature('other-id', $bytes, 'RSA-SHA256', ['date']),
            ],
            'duplicated algorithm' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'",,algorithm="RSA-SHA512"',
                new Signature('some-key', $bytes, 'RSA-SHA512', ['date']),
            ],
            'duplicated headers' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",headers="",signature="'.$sign.'",algorithm="RSA-SHA256"',
                new Signature('some-key', $bytes, 'RSA-SHA256'),
            ],
            'duplicated signature' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="first-signature",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'unsupported data' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'",unknown="hello"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'unsupported data in middle of input' => [
                'keyId="some-key",unknown="hello",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'redundant ending comma' => [
                'keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'",',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'redundant leading comma' => [
                ',keyId="some-key",algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'redundant in-middle comma' => [
                'keyId="some-key",,algorithm="RSA-SHA256",headers="date",signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
            'comma + space as parts delimiter' => [
                'keyId="some-key", algorithm="RSA-SHA256", headers="date", signature="'.$sign.'"',
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
            ],
        ];
    }

    public function signaturesDataProvider()
    {
        $bytes = random_bytes(rand(256, 4096));
        $sign = base64_encode($bytes);

        return [
            'full signature, one header' => [
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date']),
                'keyId="some-key",algorithm="rsa-sha256",headers="date",signature="'.$sign.'"',
            ],
            'full signature, multiple headers' => [
                new Signature('some-key', $bytes, 'RSA-SHA256', ['date', 'host', '(request-target)']),
                'keyId="some-key",algorithm="rsa-sha256",headers="date host (request-target)",signature="'.$sign.'"',
            ],
            'full signature, other headers order' => [
                new Signature('some-key', $bytes, 'RSA-SHA256', ['(request-target)', 'date', 'host']),
                'keyId="some-key",algorithm="rsa-sha256",headers="(request-target) date host",signature="'.$sign.'"',
            ],
            'full signature, headers case-insensitive' => [
                new Signature('some-key', $bytes, 'RSA-SHA256', ['(ReQuEsT-tArGEt)', 'Date', 'host']),
                'keyId="some-key",algorithm="rsa-sha256",headers="(request-target) date host",signature="'.$sign.'"',
            ],
            'full signature, not unique headers' => [
                new Signature('some-key', $bytes, 'RSA-SHA256', ['(request-target)', 'date', 'host', 'date']),
                'keyId="some-key",algorithm="rsa-sha256",headers="(request-target) date host date",signature="'.$sign.'"',
            ],
            'full signature, fingerprint as key id' => [
                new Signature('ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff', $bytes, 'RSA-SHA256', ['date']),
                'keyId="ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff:ff",algorithm="rsa-sha256",headers="date",signature="'.$sign.'"',
            ],
            'full signature, algorithm case-insensitive' => [
                new Signature('some-key', $bytes, 'RSA-sha256', ['date']),
                'keyId="some-key",algorithm="rsa-sha256",headers="date",signature="'.$sign.'"',
            ],
            'full signature, key id case-sensitive' => [
                new Signature('sOmE-KEy', $bytes, 'RSA-SHA256', ['date']),
                'keyId="sOmE-KEy",algorithm="rsa-sha256",headers="date",signature="'.$sign.'"',
            ],
            'full signature, signature case-sensitive' => [
                new Signature('some-key', 'static_signature', 'RSA-SHA256', ['date']),
                'keyId="some-key",algorithm="rsa-sha256",headers="date",signature="c3RhdGljX3NpZ25hdHVyZQ=="',
            ],
            'full signature, empty header' => [
                new Signature('some-key', 'static_signature', 'RSA-SHA256', ['date', '']),
                'keyId="some-key",algorithm="rsa-sha256",headers="date",signature="c3RhdGljX3NpZ25hdHVyZQ=="',
            ],
            'without headers' => [
                new Signature('some-key', $bytes, 'RSA-SHA256'),
                'keyId="some-key",algorithm="rsa-sha256",signature="'.$sign.'"',
            ],
            'without algorithm' => [
                new Signature('some-key', $bytes, null, ['date']),
                'keyId="some-key",headers="date",signature="'.$sign.'"',
            ],
            'required only' => [
                new Signature('some-key', $bytes, null),
                'keyId="some-key",signature="'.$sign.'"',
            ],
        ];
    }
}
