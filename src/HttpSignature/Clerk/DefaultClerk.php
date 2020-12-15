<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Clerk;

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\Key\KeyInterface;
use Yandex\Eats\HttpSignature\Signature;
use Yandex\Eats\HttpSignature\SignatureAlgorithm\SignatureAlgorithmInterface;
use Yandex\Eats\HttpSignature\SigningString\SigningStringBuilderInterface;

class DefaultClerk implements ClerkInterface
{
    /**
     * @var SignatureAlgorithmInterface
     */
    private $signatureAlgorithm;

    /**
     * @var SigningStringBuilderInterface
     */
    private $signingStringBuilder;

    public function __construct(
        SignatureAlgorithmInterface $signatureAlgorithm,
        SigningStringBuilderInterface $signingStringBuilder
    ) {
        $this->signatureAlgorithm = $signatureAlgorithm;
        $this->signingStringBuilder = $signingStringBuilder;
    }

    public function sign(KeyInterface $key, HeadersAccessorInterface $headersAccessor, array $headers): Signature
    {
        $signingString = $this->signingStringBuilder->build($headersAccessor, $headers);
        $binarySignature = $this->signatureAlgorithm->sign($key, $signingString);

        return new Signature(
            $key->getId(),
            $binarySignature,
            $this->signatureAlgorithm->getAlgorithmName(),
            $headers
        );
    }
}
