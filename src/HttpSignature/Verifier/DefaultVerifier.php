<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Verifier;

use Yandex\Eats\HttpSignature\HeadersAccessor\HeadersAccessorInterface;
use Yandex\Eats\HttpSignature\KeyProvider\KeyProviderInterface;
use Yandex\Eats\HttpSignature\Signature;
use Yandex\Eats\HttpSignature\SignatureAlgorithmFactory\SignatureAlgorithmFactoryInterface;
use Yandex\Eats\HttpSignature\SigningString\SigningStringBuilderInterface;

class DefaultVerifier implements VerifierInterface
{
    /**
     * @var SignatureAlgorithmFactoryInterface
     */
    private $algorithmFactory;

    /**
     * @var KeyProviderInterface
     */
    private $keyProvider;

    /**
     * @var SigningStringBuilderInterface
     */
    private $signingStringBuilder;

    public function __construct(
        SignatureAlgorithmFactoryInterface $algorithmFactory,
        KeyProviderInterface $keyProvider,
        SigningStringBuilderInterface $signingStringBuilder
    ) {
        $this->algorithmFactory = $algorithmFactory;
        $this->keyProvider = $keyProvider;
        $this->signingStringBuilder = $signingStringBuilder;
    }

    public function verify(Signature $signature, HeadersAccessorInterface $headersAccessor): bool
    {
        $signatureAlgorithm = $this->algorithmFactory->make($signature->getAlgorithm());
        $key = $this->keyProvider->fetch($signature->getKeyId());

        $signingString = $this->signingStringBuilder->build($headersAccessor, $signature->getHeaders());

        return $signatureAlgorithm->verify($key, $signingString, $signature->getBinarySignature());
    }
}
