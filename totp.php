<?php

class TOTP {

    public static function generate_secret(): string {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes  = random_bytes(10);
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[ord($bytes[$i % 10]) % 32];
        }
        return $secret;
    }

    private static function base32_decode(string $secret): string {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret   = strtoupper($secret);
        $buffer   = 0;
        $bits     = 0;
        $output   = '';

        foreach (str_split($secret) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $buffer = ($buffer << 5) | $pos;
            $bits  += 5;
            if ($bits >= 8) {
                $output .= chr(($buffer >> ($bits - 8)) & 0xFF);
                $bits   -= 8;
            }
        }
        return $output;
    }

    public static function get_code(string $secret, int $time_step = 0): string {
        $key      = self::base32_decode($secret);
        $counter  = intdiv(time(), 30) + $time_step;
        $msg      = pack('J', $counter);            // 8-byte big-endian
        $hmac     = hash_hmac('sha1', $msg, $key, true);
        $offset   = ord($hmac[19]) & 0x0F;
        $code     = (
            ((ord($hmac[$offset])     & 0x7F) << 24) |
            ((ord($hmac[$offset + 1]) & 0xFF) << 16) |
            ((ord($hmac[$offset + 2]) & 0xFF) <<  8) |
             (ord($hmac[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    public static function verify(string $secret, string $code): bool {
        $code = trim($code);
        foreach ([-1, 0, 1] as $step) {
            if (hash_equals(self::get_code($secret, $step), $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Build an otpauth:// URI for QR code generation.
     */
    public static function get_uri(string $secret, string $email, string $issuer = 'TravelSc'): string {
        return 'otpauth://totp/'
            . rawurlencode($issuer) . ':' . rawurlencode($email)
            . '?secret=' . $secret
            . '&issuer=' . rawurlencode($issuer);
    }
}
