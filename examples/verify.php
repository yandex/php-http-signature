<?php
declare(strict_types=1);

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\KeyLoader\OpenSslPemKeyLoader;
use Yandex\Eats\HttpSignature\KeyProvider\DefaultKeyProvider;
use Yandex\Eats\HttpSignature\KeyStorage\ArrayBasedKeyStorage;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\OpenSslAsymmetricAlgorithmsFactory;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;
use Yandex\Eats\HttpSignature\Verifier\DefaultVerifier;

require __DIR__.'/../vendor/autoload.php';

const SIGNATURE = 'keyId="rsa_pair-1",algorithm="rsa-sha512",headers="host date x-api-token",signature="hj53QixUYcFWVuQUWRQxMeMiiwSGGP+N2idsrAa1JEJNGhM1EDW988hFzjN5moOgmbR+0eM6AdIDs3mIBWkCGytbEAxBHLoHOF6FW10CUKqow85TtAJK/00OR/EXgZR1pF1muH75+32fae875+YK2Ywvxrqh5CpZec8YfEPL5/iwhHxNyoheF9t/+iXjN5JRAtrSuGfpxcpC072xKAU8lJs1ii1nt3Q0UxWg2LcfW99Ibn1qRh7RtjTPgswRx+CUA7bdMCIWi7+9beFilQZnPBqifratFt/JGggHRuD93DIwJgoWR4+W0mwGjFjHrbjk6RNasVJ/81MfGYtXvx2Agg=="';

const REQUEST_METHOD = 'GET';
const REQUEST_URI = '/hello?foo=1';
const REQUEST_HEADERS = [
    'Host' => 'example.com',
    'Date' => 'Tue, 07 Jun 2014 20:51:35 GMT',
    'X-Api-Token' => 'super secret token!',
];

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
    echo 'Signature is match.'.PHP_EOL;

    exit(0);
} else {
    echo 'Signature is not match.'.PHP_EOL;

    exit(1);
}