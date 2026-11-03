<?php

namespace App\Services\Auth;

/**
 * TOTP (RFC 6238) puro PHP, sem dependência externa — o mesmo arquivo do
 * Sócrates, do USI e do Synapse, que é o molde da casa. Compatível com Google
 * Authenticator, Authy, 1Password etc.
 *
 * Só existe o método do aplicativo, como no Sócrates: código por e-mail cairia
 * no mesmo canal que recupera a senha, e deixaria de ser um segundo fator.
 */
class TotpService
{
    private const SECRET_BYTES = 20; // 160 bits -> 32 caracteres base32, sem padding

    private const PERIOD = 30;

    private const DIGITS = 6;

    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecretKey(): string
    {
        return $this->base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /** O issuer é o nome que aparece no aplicativo autenticador. */
    public function getOtpAuthUrl(string $secret, string $email, ?string $issuer = null): string
    {
        $issuer ??= (string) config('marca.nome');
        $label = rawurlencode("{$issuer}:{$email}");
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => 'SHA1',
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$query}";
    }

    /**
     * @param  int  $window  quantos períodos de 30s para trás/frente aceitar
     *                       (tolerância a relógio dessincronizado).
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $secretBytes = $this->base32Decode($secret);
        $timeCounter = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateCode($secretBytes, $timeCounter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Só existe pra teste conseguir produzir um código válido sem
     * ReflectionMethod (o teste de 2FA do USI precisa de reflection pra
     * chegar no generateCode privado -- sinal de que faltava este helper).
     */
    public function codeFor(string $secret, ?int $timeCounter = null): string
    {
        return $this->generateCode(
            $this->base32Decode($secret),
            $timeCounter ?? (int) floor(time() / self::PERIOD),
        );
    }

    private function generateCode(string $secretBytes, int $timeCounter): string
    {
        $binaryCounter = pack('N*', 0).pack('N*', $timeCounter);
        $hash = hash_hmac('sha1', $binaryCounter, $secretBytes, true);

        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binary % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string
    {
        $binaryString = '';
        foreach (str_split($data) as $char) {
            $binaryString .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($binaryString, 5) as $chunk) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            $result .= self::ALPHABET[bindec($chunk)];
        }

        return $result;
    }

    private function base32Decode(string $base32): string
    {
        $base32 = strtoupper(rtrim($base32, '='));
        $binaryString = '';

        foreach (str_split($base32) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binaryString, 8) as $byte) {
            if (strlen($byte) < 8) {
                continue;
            }
            $bytes .= chr(bindec($byte));
        }

        return $bytes;
    }
}
