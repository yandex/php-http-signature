<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Exception;

/**
 * Thrown on key loader level if key cannot be loaded by current loader.
 * It can happen if loader miss-configured and key that taken from storage is not allowed in called loader.
 */
class KeyCorruptedException extends HttpSignatureException
{

}
