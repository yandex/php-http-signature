<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Exception;

/**
 * Basic runtime exception for all library. Catching of this exception is not covering a logic exceptions cases.
 */
abstract class HttpSignatureException extends \RuntimeException
{

}
