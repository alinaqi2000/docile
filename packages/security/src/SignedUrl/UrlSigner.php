<?php

declare(strict_types=1);

namespace Docile\Security\SignedUrl;

final class UrlSigner
{
    /** Sign a URL with a signature and expiration. */
    public function sign(string $url, string $secret, int $ttl = 3600): string
    {
        $expires = time() + $ttl;

        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url;
        }

        $urlWithoutFragment = $this->buildUrlWithoutFragment($parsed);
        $signature = hash_hmac('sha256', $urlWithoutFragment . $expires, $secret);

        $separator = isset($parsed['query']) ? '&' : '?';
        $signedUrl = $urlWithoutFragment . $separator . 'signature=' . $signature . '&expires=' . $expires;

        if (isset($parsed['fragment'])) {
            $signedUrl .= '#' . $parsed['fragment'];
        }

        return $signedUrl;
    }

    /** @param array<string, mixed> $parsed */
    private function buildUrlWithoutFragment(array $parsed): string
    {
        $scheme = $parsed['scheme'] ?? '';
        $host = $parsed['host'] ?? '';
        $port = $parsed['port'] ?? '';
        $user = $parsed['user'] ?? '';
        $pass = $parsed['pass'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = $parsed['query'] ?? '';

        $result = '';

        if (is_string($scheme) && $scheme !== '') {
            $result .= $scheme . '://';
        }

        if (is_string($user) && $user !== '') {
            $result .= $user;

            if (is_string($pass) && $pass !== '') {
                $result .= ':' . $pass;
            }

            $result .= '@';
        }

        if (is_string($host)) {
            $result .= $host;
        }

        if (is_string($port) && $port !== '' && $port !== '80' && $port !== '443') {
            $result .= ':' . $port;
        } elseif (is_int($port) && $port !== 80 && $port !== 443) {
            $result .= ':' . $port;
        }

        if (is_string($path) && $path !== '') {
            $result .= $path;
        }

        if (is_string($query) && $query !== '') {
            $result .= '?' . $query;
        }

        return $result;
    }

    /** Validate a signed URL. */
    public function validate(string $url, string $secret): bool
    {
        $parsed = parse_url($url);

        if ($parsed === false || !isset($parsed['query'])) {
            return false;
        }

        $query = $parsed['query'];
        if (!is_string($query)) {
            return false;
        }

        parse_str($query, $params);

        if (!isset($params['signature'], $params['expires'])) {
            return false;
        }

        $signature = $params['signature'];
        $expires = (int) $params['expires'];

        if ($expires < time()) {
            return false;
        }

        if (!is_string($signature)) {
            return false;
        }

        $urlWithoutSignature = $this->removeSignatureParams($parsed);

        $expectedSignature = hash_hmac('sha256', $urlWithoutSignature . $expires, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /** @param array<string, mixed> $parsed */
    private function removeSignatureParams(array $parsed): string
    {
        if (!isset($parsed['query'])) {
            return $this->buildUrlWithoutFragment($parsed);
        }

        $query = $parsed['query'];
        if (!is_string($query)) {
            return $this->buildUrlWithoutFragment($parsed);
        }

        parse_str($query, $params);
        unset($params['signature'], $params['expires']);

        $query = http_build_query($params);

        if ($query === '') {
            unset($parsed['query']);
        } else {
            $parsed['query'] = $query;
        }

        return $this->buildUrlWithoutFragment($parsed);
    }
}
