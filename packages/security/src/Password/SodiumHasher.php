<?php

declare(strict_types=1);

namespace Docile\Security\Password;

final class SodiumHasher implements HasherInterface
{
    #[\Override]
    public function hash(string $plain): string
    {
        return sodium_crypto_pwhash_str(
            $plain,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
        );
    }

    #[\Override]
    public function verify(string $plain, string $hash): bool
    {
        return sodium_crypto_pwhash_str_verify($hash, $plain);
    }

    #[\Override]
    public function needsRehash(string $hash): bool
    {
        return sodium_crypto_pwhash_str_needs_rehash(
            $hash,
            SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE,
            SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE,
        );
    }
}
