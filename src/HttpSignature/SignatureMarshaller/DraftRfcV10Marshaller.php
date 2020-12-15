<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\SignatureMarshaller;

use Yandex\Eats\HttpSignature\Exception\SignatureCorruptedException;
use Yandex\Eats\HttpSignature\Signature;

class DraftRfcV10Marshaller implements SignatureMarshallerInterface
{
    private const HEADER_DELIMITER = ' ';

    private const PARTS_DELIMITER = ',';

    private const PAIR_VALUE_QUOTE = '"';
    private const PAIR_MASK = '%s="%s"';
    private const PAIR_PATTERN = '#^(\w+)="(.*)"$#';
    private const PAIR_REGEXP_MATCHES = 3; // 1 = full, 2 = key, 3 = value

    private const KEY_KEY_ID = 'keyId';
    private const KEY_ALGORITHM = 'algorithm';
    private const KEY_HEADERS = 'headers';
    private const KEY_SIGNATURE = 'signature';

    private const SORTED_PARTS = [self::KEY_KEY_ID, self::KEY_ALGORITHM, self::KEY_HEADERS, self::KEY_SIGNATURE];

    public function marshall(Signature $signature): string
    {
        $parts[self::KEY_KEY_ID] = $signature->getKeyId();
        $parts[self::KEY_SIGNATURE] = $signature->getSignature();

        if ($signature->hasHeaders()) {
            $headers = array_filter(
                $signature->getHeaders(),
                function (string $header) {
                    return trim($header) !== '';
                }
            );

            $parts[self::KEY_HEADERS] = implode(self::HEADER_DELIMITER, $headers);
        }

        if ($signature->hasAlgorithm()) {
            $parts[self::KEY_ALGORITHM] = $signature->getAlgorithm();
        }

        $pairs = [];
        foreach (self::SORTED_PARTS as $name) {
            if (array_key_exists($name, $parts)) {
                $pairs[] = sprintf(self::PAIR_MASK, $name, $parts[$name]);
            }
        }

        return implode(self::PARTS_DELIMITER, $pairs);
    }

    public function unmarshall(string $raw): Signature
    {
        $seekFor = array_flip(self::SORTED_PARTS);

        $parts = explode(self::PARTS_DELIMITER, $raw);
        $parts = array_reverse($parts); // last definitions is most priority

        $data = [];
        foreach ($parts as $part) {
            $part = trim($part);
            preg_match(self::PAIR_PATTERN, $part, $matches);

            if (count($matches) !== self::PAIR_REGEXP_MATCHES) {
                continue;
            }

            [, $key, $value] = $matches;

            if (!array_key_exists($key, $seekFor)) {
                continue; // ignore unknown parts
            }

            $data[$key] = trim($value, self::PAIR_VALUE_QUOTE);
            unset($seekFor[$key]);
        }

        if (!array_key_exists(self::KEY_KEY_ID, $data)) {
            throw new SignatureCorruptedException('Key id was not found in input');
        }

        if (!array_key_exists(self::KEY_SIGNATURE, $data)) {
            throw new SignatureCorruptedException('Signature was not found in input');
        }

        $binarySignature = base64_decode($data[self::KEY_SIGNATURE], true);
        if ($binarySignature === false) {
            throw new SignatureCorruptedException('Signature has invalid encoding');
        }

        $headers = [];
        if (array_key_exists(self::KEY_HEADERS, $data) && strlen($data[self::KEY_HEADERS]) > 0) {
            $headers = explode(self::HEADER_DELIMITER, $data[self::KEY_HEADERS]);
        }

        return new Signature(
            $data[self::KEY_KEY_ID],
            $binarySignature,
            $data[self::KEY_ALGORITHM] ?? null,
            $headers
        );
    }
}
