<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Authenticated encryption for credentials stored in platform_settings.
 *
 * APP_ENCRYPTION_KEY must be base64-encoded 32-byte random data and must be
 * stored outside the database and web root.
 */
final class CredentialCipher
{
    private const PREFIX = 'enc:v1:';

    private static function key(): string
    {
        $encoded = (string) (getenv('APP_ENCRYPTION_KEY') ?: ($_ENV['APP_ENCRYPTION_KEY'] ?? ''));
        $key = base64_decode(trim($encoded), true);
        if ($key === false || strlen($key) !== SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        return $key;
    }

    public static function encrypt(string $plainText): string
    {
        if (!function_exists('sodium_crypto_secretbox')) {
            throw new RuntimeException('The PHP Sodium extension is required to store API credentials securely.');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = sodium_crypto_secretbox($plainText, $nonce, self::key());

        return self::PREFIX . base64_encode($nonce . $cipherText);
    }

    public static function decrypt(string $storedValue): string
    {
        if (!str_starts_with($storedValue, self::PREFIX)) {
            return $storedValue; // Legacy plaintext; Setting migrates it when a key is configured.
        }
        if (!function_exists('sodium_crypto_secretbox_open')) {
            throw new RuntimeException('The PHP Sodium extension is required to read encrypted API credentials.');
        }

        $payload = base64_decode(substr($storedValue, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Stored credential has an invalid encrypted format.');
        }

        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plainText = sodium_crypto_secretbox_open($cipherText, $nonce, self::key());
        if ($plainText === false) {
            throw new RuntimeException('Unable to decrypt a stored credential. Check APP_ENCRYPTION_KEY.');
        }

        return $plainText;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX);
    }

    public static function keyIsConfigured(): bool
    {
        try {
            self::key();
            return function_exists('sodium_crypto_secretbox');
        } catch (\Throwable) {
            return false;
        }
    }
}
