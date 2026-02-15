<?php
/**
 * User Agent Parser
 * Parses User-Agent strings into human-readable device information
 */

declare(strict_types=1);

/**
 * Parse User-Agent string into readable device info
 *
 * @param string|null $userAgent The User-Agent string
 * @return array Parsed device information
 */
function parseUserAgent(?string $userAgent): array
{
    if (!$userAgent) {
        return [
            'device_type' => 'Unknown',
            'device_icon' => '❓',
            'os' => 'Unknown',
            'browser' => 'Unknown',
            'full_string' => 'Unknown Device'
        ];
    }

    // Detect device type
    $deviceType = 'Desktop';
    $deviceIcon = '🖥️';

    if (preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
        if (preg_match('/iPad|Tablet/i', $userAgent)) {
            $deviceType = 'Tablet';
            $deviceIcon = '📱';
        } else {
            $deviceType = 'Mobile';
            $deviceIcon = '📱';
        }
    }

    // Detect OS
    $os = 'Unknown OS';
    if (preg_match('/Windows NT 10\.0/i', $userAgent)) {
        $os = 'Windows 10/11';
    } elseif (preg_match('/Windows NT 6\.3/i', $userAgent)) {
        $os = 'Windows 8.1';
    } elseif (preg_match('/Windows NT 6\.2/i', $userAgent)) {
        $os = 'Windows 8';
    } elseif (preg_match('/Windows NT 6\.1/i', $userAgent)) {
        $os = 'Windows 7';
    } elseif (preg_match('/Windows/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac OS X ([\d_]+)/i', $userAgent, $matches)) {
        $version = str_replace('_', '.', $matches[1]);
        $os = 'macOS ' . $version;
    } elseif (preg_match('/iPhone OS ([\d_]+)/i', $userAgent, $matches)) {
        $version = str_replace('_', '.', $matches[1]);
        $os = 'iOS ' . $version;
        $deviceIcon = '📱';
    } elseif (preg_match('/Android ([\d.]+)/i', $userAgent, $matches)) {
        $os = 'Android ' . $matches[1];
        $deviceIcon = '📱';
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $os = 'Linux';
    }

    // Detect Browser
    $browser = 'Unknown Browser';
    if (preg_match('/Edg\/([\d.]+)/i', $userAgent, $matches)) {
        $browser = 'Edge ' . explode('.', $matches[1])[0];
    } elseif (preg_match('/Chrome\/([\d.]+)/i', $userAgent, $matches)) {
        // Check if it's actually Chrome or a Chrome-based browser
        if (preg_match('/OPR\/([\d.]+)/i', $userAgent, $operaMatches)) {
            $browser = 'Opera ' . explode('.', $operaMatches[1])[0];
        } else {
            $browser = 'Chrome ' . explode('.', $matches[1])[0];
        }
    } elseif (preg_match('/Safari\/([\d.]+)/i', $userAgent, $matches)) {
        // Real Safari (not Chrome masquerading)
        if (!preg_match('/Chrome/i', $userAgent)) {
            // Try to get version from "Version/XX.X"
            if (preg_match('/Version\/([\d.]+)/i', $userAgent, $versionMatches)) {
                $browser = 'Safari ' . explode('.', $versionMatches[1])[0];
            } else {
                $browser = 'Safari';
            }
        }
    } elseif (preg_match('/Firefox\/([\d.]+)/i', $userAgent, $matches)) {
        $browser = 'Firefox ' . explode('.', $matches[1])[0];
    } elseif (preg_match('/MSIE ([\d.]+)/i', $userAgent, $matches)) {
        $browser = 'IE ' . explode('.', $matches[1])[0];
    } elseif (preg_match('/Trident.*rv:([\d.]+)/i', $userAgent, $matches)) {
        $browser = 'IE ' . explode('.', $matches[1])[0];
    }

    // Build full readable string
    $fullString = "{$deviceIcon} {$deviceType} · {$os} · {$browser}";

    return [
        'device_type' => $deviceType,
        'device_icon' => $deviceIcon,
        'os' => $os,
        'browser' => $browser,
        'full_string' => $fullString,
        'raw_user_agent' => $userAgent
    ];
}

/**
 * Get short device description for UI display
 *
 * @param string|null $userAgent The User-Agent string
 * @return string Short human-readable device description
 */
function getDeviceDescription(?string $userAgent): string
{
    $parsed = parseUserAgent($userAgent);
    return $parsed['full_string'];
}
