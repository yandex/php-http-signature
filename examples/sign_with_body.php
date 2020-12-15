<?php
declare(strict_types=1);

use Yandex\Eats\Digest\DigestAlgorithm\OpenSslBasedHashAlgorithm;
use Yandex\Eats\HttpSignature\Clerk\DefaultClerk;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\HeadersEnum;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;

require __DIR__.'/../vendor/autoload.php';

const SIGN_ALGORITHM = 'sha512';
const DIGEST_ALGORITHM = 'sha256';

$now = (new DateTimeImmutable('now', new DateTimeZone('Europe/Moscow')));

const REQUEST_METHOD = 'POST';
const REQUEST_URI = '/hello?foo=1';
const REQUEST_BODY = '{"phone_number": "+79991234556"}';

$requestHeaders = [
    'Host' => 'example.com',
    'Date' => 'Tue, 07 Jun 2014 20:51:35 GMT',
    'X-Api-Token' => 'super secret token!',
];

$digestAlgorithm = new OpenSslBasedHashAlgorithm(DIGEST_ALGORITHM);
$digest = $digestAlgorithm->hash(REQUEST_BODY);

$requestHeaders[HeadersEnum::HEADER_DIGEST] = (string)$digest;

$headersAccessor = new HeadersListAccessor($requestHeaders, REQUEST_METHOD, REQUEST_URI);
$signingStringBuilder = new DraftRfcV10Builder();

$privateKey = new OpenSslPemPrivateKey('rsa_pair-1', file_get_contents(__DIR__.'/keys/private.pem'), 'test123');

$algorithm = new OpenSslBasedAsymmetricAlgorithm($privateKey->getType(), SIGN_ALGORITHM);
$clerk = new DefaultClerk($algorithm, $signingStringBuilder);
$marshaller = new DraftRfcV10Marshaller();

$signature = $clerk->sign($privateKey, $headersAccessor, ['host', 'date', 'X-api-token', HeadersEnum::HEADER_DIGEST]);
$signatureString = $marshaller->marshall($signature);

// that headers you need to provide into your request
$additionalHeaders = [
    HeadersEnum::HEADER_DIGEST => $requestHeaders[HeadersEnum::HEADER_DIGEST],
    HeadersEnum::HEADER_SIGNATURE => $signatureString,
    HeadersEnum::HEADER_AUTHORIZATION => sprintf('%s %s', HeadersEnum::AUTHORIZATION_SCHEME, $signatureString),
];

print_r($additionalHeaders);
