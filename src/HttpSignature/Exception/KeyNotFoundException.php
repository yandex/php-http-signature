<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Exception;

/**
 * Thrown when called key was not found by provided id.
 * But only on the provider and upper level, not storage. Because empty data is not exception situation for storage as is.
 */
class KeyNotFoundException extends HttpSignatureException
{

}
