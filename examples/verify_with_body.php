<?php
declare(strict_types=1);

use Yandex\Eats\Digest\Digest;
use Yandex\Eats\Digest\DigestAlgorithmFactory\OpenSslAlgorithmFactory;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\HeadersEnum;
use Yandex\Eats\HttpSignature\KeyLoader\OpenSslPemKeyLoader;
use Yandex\Eats\HttpSignature\KeyProvider\DefaultKeyProvider;
use Yandex\Eats\HttpSignature\KeyStorage\ArrayBasedKeyStorage;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\OpenSslAsymmetricAlgorithmsFactory;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;
use Yandex\Eats\HttpSignature\Verifier\DefaultVerifier;

require __DIR__.'/../vendor/autoload.php';

const SIGNATURE = 'keyId="rsa_pair-1",algorithm="rsa-sha512",headers="host date x-api-token digest",signature="GKkhkJqf9ab0kzbr3OsaDixcXS+kh0vREjp12t90evi3cJcathw9NY3OacNMWZr9gWGWNNS/t1YijXji45Kr1VXZrr8Lq2DjhTQp36g1AuIZQ3kTusdCrHtZ5E7Ygqi18O8rGxivvPFX+JAV7zEphZax0HRLQVfuR40kzFwBBqr1j5NsZuZocWS1Un/4Z9+hZOS+VKISXIYJsnZjHT8f3B8IsjplAKJfI/mHZd4i4TQVzg3RxKZIS5BKjQYbivpGQHrz76qSFRf6fUfUC8Z3kueh70p05lIL/RFcOaIuGM7oUInz21EMt3BPlOMqQfgYHPWGCbMl5kJ1PiqzV34KZg=="';
const DIGEST_ALGORITHM = 'sha256';

const REQUEST_METHOD = 'POST';
const REQUEST_URI = '/hello?foo=1';
const REQUEST_HEADERS = [
    'Host' => 'example.com',
    'Date' => 'Tue, 07 Jun 2014 20:51:35 GMT',
    'X-Api-Token' => 'super secret token!',
    HeadersEnum::HEADER_DIGEST => 'SHA256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c',
];
const REQUEST_BODY = '{"phone_number": "+79991234556"}';

$digest = Digest::fromHeader(REQUEST_HEADERS[HeadersEnum::HEADER_DIGEST]);
$digestAlgorithmsFactory = new OpenSslAlgorithmFactory();
$digestAlgorithm = $digestAlgorithmsFactory->make($digest->getAlgorithm());

if ($digestAlgorithm->hash(REQUEST_BODY)->getHash() === $digest->getHash()) {
    echo "Digest is match.".PHP_EOL;
    echo "Now we must check the Signature.".PHP_EOL.PHP_EOL;
} else {
    echo "Digest is not match. Abort checking.".PHP_EOL;

    exit(1);
}

$headersAccessor = new HeadersListAccessor(REQUEST_HEADERS, REQUEST_METHOD, REQUEST_URI);

$marshaller = new DraftRfcV10Marshaller();
$signingStringBuilder = new DraftRfcV10Builder();

$keyStorage = new ArrayBasedKeyStorage(
    [
        'rsa_pair-1' => [
            'type' => 'pem',
            'is_public' => true,
            'content' => file_get_contents(__DIR__.'/keys/public.pem'),
        ],
    ]
);

$keyLoader = new OpenSslPemKeyLoader();
$keyProvider = new DefaultKeyProvider($keyStorage, [$keyLoader]);
$algorithmFactory = new OpenSslAsymmetricAlgorithmsFactory();

$verifier = new DefaultVerifier($algorithmFactory, $keyProvider, $signingStringBuilder);

$signatureToVerify = $marshaller->unmarshall(SIGNATURE);

if ($verifier->verify($signatureToVerify, $headersAccessor)) {
    echo 'Signature is match. All fine.'.PHP_EOL;

    exit(0);
} else {
    echo 'Signature is not match.'.PHP_EOL;

    exit(1);
}