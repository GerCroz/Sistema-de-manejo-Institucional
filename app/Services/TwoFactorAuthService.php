<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorAuthService
{
    private const BASE32_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Genera una clave secreta aleatoria Base32 para TOTP.
     */
    public function generateSecretKey(int $length = 16): string
    {
        $secret = '';
        $max = strlen(self::BASE32_CHARS) - 1;

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_CHARS[random_int(0, $max)];
        }

        return $secret;
    }

    /**
     * Calcula el código TOTP de 6 dígitos actual para un secreto dado.
     */
    public function getOtp(string $secret, ?int $timestamp = null): string
    {
        $timestamp = $timestamp ?? time();
        $timeSlice = (int) floor($timestamp / 30);

        $key = $this->base32Decode($secret);
        $timePacked = pack('J', $timeSlice);

        $hash = hash_hmac('sha1', $timePacked, $key, true);
        $offset = ord($hash[19]) & 0x0F;

        $otpBinary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($otpBinary % 1000000), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Valida si el código de 6 dígitos ingresado coincide (con margen de ±1 paso de 30s).
     */
    public function verify(string $secret, string $code, int $discrepancy = 1): bool
    {
        $code = trim($code);

        if (strlen($code) !== 6 || ! ctype_digit($code)) {
            return false;
        }

        $now = time();
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedOtp = $this->getOtp($secret, $now + ($i * 30));
            if (hash_equals($calculatedOtp, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera un conjunto de códigos de recuperación de respaldo.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $part1 = strtoupper(Str::random(5));
            $part2 = strtoupper(Str::random(5));
            $codes[] = "{$part1}-{$part2}";
        }

        return $codes;
    }

    /**
     * Valida y consume un código de recuperación.
     */
    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $code = trim(strtoupper($code));
        $codes = $user->two_factor_recovery_codes ?? [];

        if (! is_array($codes) || empty($codes)) {
            return false;
        }

        $index = array_search($code, $codes, true);
        if ($index !== false) {
            unset($codes[$index]);
            $user->two_factor_recovery_codes = array_values($codes);
            $user->save();

            return true;
        }

        return false;
    }

    /**
     * Genera la URL del protocolo otpauth:// para escanear en apps autenticadoras.
     */
    public function getOtpAuthUrl(string $company, string $holder, string $secret): string
    {
        $companyEncoded = rawurlencode($company);
        $holderEncoded = rawurlencode($holder);

        return "otpauth://totp/{$companyEncoded}:{$holderEncoded}?secret={$secret}&issuer={$companyEncoded}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Decodifica una cadena Base32 a binario crudo.
     */
    private function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $binary = '';

        foreach (str_split($secret) as $char) {
            $pos = strpos(self::BASE32_CHARS, $char);
            if ($pos !== false) {
                $binary .= sprintf('%05b', $pos);
            }
        }

        $result = '';
        $length = strlen($binary);
        for ($i = 0; $i + 8 <= $length; $i += 8) {
            $result .= chr((int) bindec(substr($binary, $i, 8)));
        }

        return $result;
    }
}
