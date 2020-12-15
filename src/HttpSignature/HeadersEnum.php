<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature;

class HeadersEnum
{
    public const HEADER_SIGNATURE = 'Signature';
    public const HEADER_DIGEST = 'Digest';
    public const HEADER_AUTHORIZATION = 'Authorization';

    public const PSEUDO_HEADER_REQUEST_TARGET = '(request-target)';

    public const AUTHORIZATION_SCHEME = 'Signature';
}
