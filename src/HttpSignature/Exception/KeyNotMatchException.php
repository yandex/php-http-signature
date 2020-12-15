<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Exception;

/**
 * Occurs when provided to Clerk key is not matches with provided signing algorithm.
 * For example: trying to make a hmac-sign with pem key.
 *
 * Important: this is a logic exception and not inherited from the abstract HttpSignatureException.
 */
class KeyNotMatchException extends \LogicException
{

}
