<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar;

final class AuthResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly int $status,
        public readonly ?string $authMethod,
        public readonly string $actor,
    ) {}

    public static function allowed(string $authMethod, string $actor): self
    {
        return new self(true, 200, $authMethod, $actor);
    }

    public static function unauthorized(string $actor): self
    {
        return new self(false, 401, null, $actor);
    }

    public static function forbidden(string $authMethod, string $actor): self
    {
        return new self(false, 403, $authMethod, $actor);
    }
}
