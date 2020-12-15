<?php
declare(strict_types=1);

use Yandex\Eats\HttpSignature\Clerk\DefaultClerk;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPublicKey;
use Yandex\Eats\HttpSignature\KeyLoader\OpenSslPemKeyLoader;
use Yandex\Eats\HttpSignature\KeyProvider\DefaultKeyProvider;
use Yandex\Eats\HttpSignature\KeyStorage\ArrayBasedKeyStorage;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\OpenSslAsymmetricAlgorithmsFactory;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;
use Yandex\Eats\HttpSignature\Verifier\DefaultVerifier;

require __DIR__.'/../vendor/autoload.php';

const SIGN_ALGORITHM = 'sha512';

$now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')));

$requestMethod = 'GET';
$requestUri = '/hello?foo=1';
$requestHeaders = [
    'Host' => 'example.com',
    'Date' => $now->format('r'),
    'X-Api-Token' => 'super secret token!',
];

$headersAccessor = new HeadersListAccessor($requestHeaders, $requestMethod, $requestUri);
$signingStringBuilder = new DraftRfcV10Builder();

$privateKey = new OpenSslPemPrivateKey('rsa_pair-1', file_get_contents(__DIR__.'/keys/private.pem'), 'test123');
$publicKey = new OpenSslPemPublicKey('rsa_pair-1', file_get_contents(__DIR__.'/keys/public.pem'));

$algorithm = new OpenSslBasedAsymmetricAlgorithm($privateKey->getType(), SIGN_ALGORITHM);
$clerk = new DefaultClerk($algorithm, $signingStringBuilder);
$marshaller = new DraftRfcV10Marshaller();

$signature = $clerk->sign($privateKey, $headersAccessor, ['host', 'date', 'X-api-token']);
$signatureString = $marshaller->marshall($signature);

echo 'Signature built successfully: '.$signatureString.PHP_EOL.PHP_EOL;

$keyStorage = new ArrayBasedKeyStorage(
    [
        'rsa_pair-1' => [
            'type' => 'pem',
            'is_public' => true,
            'content' => (string)$publicKey,
        ],
    ]
);

$keyLoader = new OpenSslPemKeyLoader();
$keyProvider = new DefaultKeyProvider($keyStorage, [$keyLoader]);
$algorithmFactory = new OpenSslAsymmetricAlgorithmsFactory();

$verifier = new DefaultVerifier($algorithmFactory, $keyProvider, $signingStringBuilder);

$signatureToVerify = $marshaller->unmarshall($signatureString);

if ($verifier->verify($signatureToVerify, $headersAccessor)) {
    echo 'Signature is match.';
} else {
    echo 'Signature is not match.';
}
