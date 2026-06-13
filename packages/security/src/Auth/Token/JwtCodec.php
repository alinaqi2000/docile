<?php

declare(strict_types=1);

namespace Docile\Security\Auth\Token;

use Docile\Security\Exception\InvalidTokenException;

final class JwtCodec
{
    private const string ALGORITHM = 'HS256';

    /** Encode claims into a JWT token. */
    /** @param array<string, mixed> $claims */
    public function encode(array $claims, string $secret, int $ttl = 3600): string
    {
        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $now = time();
        $payload = $claims + [
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));

        $signature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /** Decode a JWT token and return claims. */
    /** @return array<string, mixed> */
    public function decode(string $token, string $secret): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new InvalidTokenException('Invalid token format.');
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $signature = $this->base64UrlDecode($signatureEncoded);
        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new InvalidTokenException('Invalid token signature.');
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true, 512, JSON_THROW_ON_ERROR);

        if (!is_array($payload)) {
            throw new InvalidTokenException('Invalid payload.');
        }

        if (!isset($payload['exp']) || !is_int($payload['exp'])) {
            throw new InvalidTokenException('Token missing expiration.');
        }

        if ($payload['exp'] < time()) {
            throw new InvalidTokenException('Token has expired.');
        }

        /** @var array<string, mixed> */
        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $decoded = base64_decode(strtr($data, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidTokenException('Invalid base64 encoding.');
        }

        return $decoded;
    }
}
