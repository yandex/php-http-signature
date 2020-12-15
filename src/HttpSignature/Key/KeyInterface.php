<?php
declare(strict_types=1);

namespace Yandex\Eats\HttpSignature\Key;

interface KeyInterface
{
    public function getId(): string;

    public function getType(): string;

    public function __toString(): string;
}
