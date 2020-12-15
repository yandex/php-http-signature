<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureMarshaller;

use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Signature;

interface SignatureMarshallerInterface
{
    /**
     * @param Signature $signature
     *
     * @return string
     * @throws SignatureCorruptedException
     */
    public function marshall(Signature $signature): string;

    /**
     * @param string $raw
     *
     * @return Signature
     * @throws SignatureCorruptedException
     */
    public function unmarshall(string $raw): Signature;
}
