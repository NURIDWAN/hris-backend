<?php

namespace App\Helpers;

class UserAgentParser
{
    /**
     * Parse user agent string into browser and OS info.
     *
     * @return array{browser: string, os: string}
     */
    public static function parse(?string $userAgent): array
    {
        if (! $userAgent) {
            return ['browser' => 'Unknown', 'os' => 'Unknown'];
        }

        return [
            'browser' => self::parseBrowser($userAgent),
            'os' => self::parseOs($userAgent),
        ];
    }

    private static function parseBrowser(string $ua): string
    {
        if (preg_match('/Edg[e\/](\d+)/i', $ua)) {
            return 'Edge';
        }

        if (preg_match('/Chrome\/(\d+)/i', $ua) && ! preg_match('/Chromium/i', $ua)) {
            return 'Chrome';
        }

        if (preg_match('/Firefox\/(\d+)/i', $ua)) {
            return 'Firefox';
        }

        if (preg_match('/Safari\/(\d+)/i', $ua) && preg_match('/Version\//i', $ua)) {
            return 'Safari';
        }

        if (preg_match('/Opera|OPR/i', $ua)) {
            return 'Opera';
        }

        return 'Unknown';
    }

    private static function parseOs(string $ua): string
    {
        if (preg_match('/Windows NT/i', $ua)) {
            return 'Windows';
        }

        if (preg_match('/Macintosh|Mac OS X/i', $ua)) {
            return 'macOS';
        }

        if (preg_match('/iPhone|iPad/i', $ua)) {
            return 'iOS';
        }

        if (preg_match('/Android/i', $ua)) {
            return 'Android';
        }

        if (preg_match('/Linux/i', $ua)) {
            return 'Linux';
        }

        return 'Unknown';
    }
}
