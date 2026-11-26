<?php

declare(strict_types=1);

namespace App\Support\Financeiro;

/** Centavos ⇄ "R$ 1.234,56". Valor viaja e se grava em centavos inteiros: float não guarda dinheiro. */
final class Dinheiro
{
    public static function formatar(?int $centavos): ?string
    {
        if ($centavos === null) {
            return null;
        }

        return 'R$ '.number_format($centavos / 100, 2, ',', '.');
    }

    /**
     * "1.234,56", "1234,56", "1234.56", "R$ 90" → centavos. Vazio → null.
     * Texto que não é número → false (a validação recusa).
     */
    public static function centavos(?string $texto): int|null|false
    {
        $texto = trim(str_replace(['R$', ' ', "\u{00A0}"], '', (string) $texto));

        if ($texto === '') {
            return null;
        }

        // Com vírgula, ela é o decimal e o ponto é milhar. Sem vírgula, um
        // ponto seguido de 1–2 dígitos no fim é decimal ("1234.5").
        if (str_contains($texto, ',')) {
            $texto = str_replace('.', '', $texto);
            $texto = str_replace(',', '.', $texto);
        } elseif (! preg_match('/\.\d{1,2}$/', $texto)) {
            $texto = str_replace('.', '', $texto);
        }

        if (! preg_match('/^\d+(\.\d{1,2})?$/', $texto)) {
            return false;
        }

        return (int) round(((float) $texto) * 100);
    }
}
