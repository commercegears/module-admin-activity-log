<?php
/**
 * Softaware Admin Activity Log
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\ActivityLog\Model;

/**
 * Matches an IP address (IPv4 or IPv6) against single addresses and CIDR ranges.
 */
class IpMatcher
{
    /**
     * @param string[] $rules
     */
    public function matches(string $ip, array $rules): bool
    {
        $binaryIp = @inet_pton($ip);
        if ($binaryIp === false) {
            return false;
        }
        foreach ($rules as $rule) {
            if ($this->matchesRule($binaryIp, $rule)) {
                return true;
            }
        }
        return false;
    }

    public function isValidRule(string $rule): bool
    {
        [$address, $bits] = array_pad(explode('/', $rule, 2), 2, null);
        $binary = @inet_pton((string)$address);
        if ($binary === false) {
            return false;
        }
        if ($bits === null) {
            return true;
        }
        return ctype_digit($bits) && (int)$bits <= strlen($binary) * 8;
    }

    private function matchesRule(string $binaryIp, string $rule): bool
    {
        [$address, $bits] = array_pad(explode('/', trim($rule), 2), 2, null);
        $binaryRule = @inet_pton((string)$address);
        if ($binaryRule === false || strlen($binaryRule) !== strlen($binaryIp)) {
            return false;
        }
        if ($bits === null) {
            return $binaryRule === $binaryIp;
        }
        $bits = (int)$bits;
        $bytes = intdiv($bits, 8);
        if (substr($binaryIp, 0, $bytes) !== substr($binaryRule, 0, $bytes)) {
            return false;
        }
        $remaining = $bits % 8;
        if ($remaining === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $remaining)) & 0xFF;
        return (ord($binaryIp[$bytes]) & $mask) === (ord($binaryRule[$bytes]) & $mask);
    }
}
