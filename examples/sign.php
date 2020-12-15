<?php
declare(strict_types=1);

use Yandex\Eats\HttpSignature\Clerk\DefaultClerk;
use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersListAccessor;
use Yandex\Eats\HttpSignature\HeadersEnum;
use Yandex\Eats\HttpSignature\Key\OpenSslPemPrivateKey;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\OpenSslBasedAsymmetricAlgorithm;
use Yandex\Eats\HttpSignature\SignatureMarshaller\DraftRfcV10Marshaller;
use Yandex\Eats\HttpSignature\SigningString\DraftRfcV10Builder;

require __DIR__.'/../vendor/autoload.php';

const SIGN_ALGORITHM = 'sha512';

const REQUEST_METHOD = 'GET';
const REQUEST_URI = '/hello?foo=1';
const REQUEST_HEADERS = [
    'Host' => 'example.com',
    'Date' => 'Tue, 07 Jun 2014 20:51:35 GMT',
    'X-Api-Token' => 'super secret token!',
];

$headersAccessor = new HeadersListAccessor(REQUEST_HEADERS, REQUEST_METHOD, REQUEST_URI);
$signingStringBuilder = new DraftRfcV10Builder();

$privateKey = new OpenSslPemPrivateKey('rsa_pair-1', file_get_contents(__DIR__.'/keys/private.pem'), 'test123');

$algorithm = new OpenSslBasedAsymmetricAlgorithm($privateKey->getType(), SIGN_ALGORITHM);
$clerk = new DefaultClerk($algorithm, $signingStringBuilder);
$marshaller = new DraftRfcV10Marshaller();

$signature = $clerk->sign($privateKey, $headersAccessor, ['host', 'date', 'X-api-token']);
$signatureString = $marshaller->marshall($signature);

// that headers you need to provide into your request
$additionalHeaders = [
    HeadersEnum::HEADER_SIGNATURE => $signatureString,
    HeadersEnum::HEADER_AUTHORIZATION => sprintf('%s %s', HeadersEnum::AUTHORIZATION_SCHEME, $signatureString),
];

print_r($additionalHeaders);
