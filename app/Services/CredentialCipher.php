<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Authenticated encryption for credentials stored in platform_settings.
 *
 * New credentials use OpenSSL AES-256-GCM (enc:v2). APP_ENCRYPTION_KEY must
 * be base64-encoded 32-byte random data stored outside the database and web root.
 * Legacy Sodium (enc:v1) values remain readable only while Sodium is available.
 */
final class CredentialCipher
{
    private const PREFIX = 'enc:v2:';
    private const LEGACY_PREFIX = 'enc:v1:';
    private const CIPHER = 'aes-256-gcm';
    private const IV_LENGTH = 12;
    private const TAG_LENGTH = 16;

    private static function key(): string
    {
        $encoded = (string) (getenv('APP_ENCRYPTION_KEY') ?: ($_ENV['APP_ENCRYPTION_KEY'] ?? ''));
        $key = base64_decode(trim($encoded), true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('APP_ENCRYPTION_KEY must be a base64-encoded 32-byte key.');
        }

        return $key;
    }

    public static function encrypt(string $plainText): string
    {
        if (!function_exists('openssl_encrypt')) {
            throw new RuntimeException('The PHP OpenSSL extension is required to store API credentials securely.');
        }

        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';
        $cipherText = openssl_encrypt(
            $plainText,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($cipherText === false || strlen($tag) !== self::TAG_LENGTH) {
            throw new RuntimeException('Unable to encrypt the integration credential.');
        }

        return self::PREFIX . base64_encode($iv . $tag . $cipherText);
    }

    public static function decrypt(string $storedValue): string
    {
        if (str_starts_with($storedValue, self::LEGACY_PREFIX)) {
            return self::decryptLegacySodium($storedValue);
        }

        if (!str_starts_with($storedValue, self::PREFIX)) {
            return $storedValue; // Legacy plaintext; Setting migrates it when a key is configured.
        }

        if (!function_exists('openssl_decrypt')) {
            throw new RuntimeException('The PHP OpenSSL extension is required to read encrypted API credentials.');
        }

        $payload = base64_decode(substr($storedValue, strlen(self::PREFIX)), true);
        if ($payload === false || strlen($payload) <= self::IV_LENGTH + self::TAG_LENGTH) {
            throw new RuntimeException('Stored credential has an invalid encrypted format.');
        }

        $iv = substr($payload, 0, self::IV_LENGTH);
        $tag = substr($payload, self::IV_LENGTH, self::TAG_LENGTH);
        $cipherText = substr($payload, self::IV_LENGTH + self::TAG_LENGTH);
        $plainText = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            self::key(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plainText === false) {
            throw new RuntimeException('Unable to decrypt a stored credential. Check APP_ENCRYPTION_KEY.');
        }

        return $plainText;
    }

    private static function decryptLegacySodium(string $storedValue): string
    {
        if (!function_exists('sodium_crypto_secretbox_open')) {
            throw new RuntimeException(
                'This credential uses the legacy Sodium format. Enable Sodium temporarily and re-save the credential to migrate it to OpenSSL.'
            );
        }

        $payload = base64_decode(substr($storedValue, strlen(self::LEGACY_PREFIX)), true);
        if ($payload === false || strlen($payload) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            throw new RuntimeException('Stored legacy credential has an invalid encrypted format.');
        }

        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipherText = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plainText = sodium_crypto_secretbox_open($cipherText, $nonce, self::key());

        if ($plainText === false) {
            throw new RuntimeException('Unable to decrypt a legacy credential. Check APP_ENCRYPTION_KEY.');
        }

        return $plainText;
    }

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX) || str_starts_with($value, self::LEGACY_PREFIX);
    }

    public static function keyIsConfigured(): bool
    {
        try {
            self::key();
            return function_exists('openssl_encrypt') && function_exists('openssl_decrypt');
        } catch (\Throwable) {
            return false;
        }
    }
}
