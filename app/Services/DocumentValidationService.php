<?php

namespace App\Services;

class DocumentValidationService
{
    /**
     * DNI/NIE Control letter lookup table (Modulo 23).
     */
    const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /**
     * Calculate control letter for a DNI number.
     */
    public static function calculateDniLetter(int|string $number): string
    {
        $cleanNumber = (int)preg_replace('/\D/', '', (string)$number);
        return self::DNI_LETTERS[$cleanNumber % 23];
    }

    /**
     * Calculate control letter for a NIE prefix and digits.
     */
    public static function calculateNieLetter(string $prefix, int|string $digits): string
    {
        $prefix = strtoupper($prefix);
        $prefixNum = match ($prefix) {
            'X' => 0,
            'Y' => 1,
            'Z' => 2,
            default => 0,
        };
        $cleanDigits = str_pad(preg_replace('/\D/', '', (string)$digits), 7, '0', STR_PAD_LEFT);
        $fullNumber = (int)($prefixNum . $cleanDigits);
        return self::DNI_LETTERS[$fullNumber % 23];
    }

    /**
     * Auto-completes control letters if missing and validates DNI, NIE or CIF.
     *
     * @param string|null $document
     * @return array{valid: bool, type: string, formatted: string, suggested: string|null, message: string|null, expected_letter: string|null, was_completed: bool}
     */
    public static function autoCompleteAndValidate(?string $document): array
    {
        if (empty($document)) {
            return [
                'valid' => false,
                'type' => 'EMPTY',
                'formatted' => '',
                'suggested' => null,
                'message' => null,
                'expected_letter' => null,
                'was_completed' => false,
            ];
        }

        $clean = strtoupper(trim(preg_replace('/[\s\-\.]/', '', (string)$document)));

        // 1. Only digits (7 or 8 digits) -> Auto-complete DNI letter!
        if (preg_match('/^\d{7,8}$/', $clean)) {
            $num = (int)$clean;
            $paddedNumber = str_pad($clean, 8, '0', STR_PAD_LEFT);
            $expectedLetter = self::DNI_LETTERS[$num % 23];
            $completed = $paddedNumber . $expectedLetter;

            return [
                'valid' => true,
                'type' => 'DNI',
                'formatted' => $completed,
                'suggested' => $completed,
                'message' => "Letra '{$expectedLetter}' añadida automáticamente.",
                'expected_letter' => $expectedLetter,
                'was_completed' => true,
            ];
        }

        // 2. NIE without letter: X, Y, Z + 7 digits -> Auto-complete NIE letter!
        if (preg_match('/^([XYZ])(\d{7})$/', $clean, $m)) {
            $prefix = $m[1];
            $digits = $m[2];
            $expectedLetter = self::calculateNieLetter($prefix, $digits);
            $completed = $prefix . $digits . $expectedLetter;

            return [
                'valid' => true,
                'type' => 'NIE',
                'formatted' => $completed,
                'suggested' => $completed,
                'message' => "Letra '{$expectedLetter}' de NIE añadida automáticamente.",
                'expected_letter' => $expectedLetter,
                'was_completed' => true,
            ];
        }

        // 3. User still typing digits (1 to 6 digits)
        if (preg_match('/^\d{1,6}$/', $clean)) {
            return [
                'valid' => false,
                'type' => 'INCOMPLETE',
                'formatted' => $clean,
                'suggested' => null,
                'message' => 'Introduce los 8 dígitos para calcular la letra.',
                'expected_letter' => null,
                'was_completed' => false,
            ];
        }

        // 4. Check full DNI (1 to 8 digits + 1 letter)
        if (preg_match('/^(\d{1,8})([A-Z])$/', $clean, $matches)) {
            $number = (int)$matches[1];
            $letter = $matches[2];
            $paddedNumber = str_pad($number, 8, '0', STR_PAD_LEFT);
            $expectedLetter = self::DNI_LETTERS[$number % 23];
            $suggested = $paddedNumber . $expectedLetter;

            if ($letter === $expectedLetter) {
                return [
                    'valid' => true,
                    'type' => 'DNI',
                    'formatted' => $paddedNumber . $letter,
                    'suggested' => $suggested,
                    'message' => 'DNI válido',
                    'expected_letter' => $expectedLetter,
                    'was_completed' => false,
                ];
            } else {
                return [
                    'valid' => false,
                    'type' => 'DNI',
                    'formatted' => $paddedNumber . $letter,
                    'suggested' => $suggested,
                    'message' => "La letra '{$letter}' es incorrecta. Para el número {$paddedNumber} le corresponde la letra '{$expectedLetter}'.",
                    'expected_letter' => $expectedLetter,
                    'was_completed' => false,
                ];
            }
        }

        // 5. Check full NIE (X, Y, Z + 7 digits + 1 letter)
        if (preg_match('/^([XYZ])(\d{7})([A-Z])$/', $clean, $matches)) {
            $prefix = $matches[1];
            $digits = $matches[2];
            $letter = $matches[3];
            $expectedLetter = self::calculateNieLetter($prefix, $digits);
            $suggested = $prefix . $digits . $expectedLetter;

            if ($letter === $expectedLetter) {
                return [
                    'valid' => true,
                    'type' => 'NIE',
                    'formatted' => $prefix . $digits . $letter,
                    'suggested' => $suggested,
                    'message' => 'NIE válido',
                    'expected_letter' => $expectedLetter,
                    'was_completed' => false,
                ];
            } else {
                return [
                    'valid' => false,
                    'type' => 'NIE',
                    'formatted' => $prefix . $digits . $letter,
                    'suggested' => $suggested,
                    'message' => "La letra '{$letter}' no es correcta para {$prefix}{$digits}. Le corresponde la letra '{$expectedLetter}'.",
                    'expected_letter' => $expectedLetter,
                    'was_completed' => false,
                ];
            }
        }

        // 6. Check CIF (Empresas / Personas jurídicas)
        if (preg_match('/^([ABCDEFGHJNPQRSUVW])(\d{7})([0-9A-J])$/', $clean, $matches)) {
            $letter = $matches[1];
            $digits = $matches[2];
            $control = $matches[3];

            $evenSum = 0;
            $oddSum = 0;

            for ($i = 0; $i < 7; $i++) {
                $n = (int)$digits[$i];
                if ($i % 2 === 0) { // Odd positions (0-indexed: 0, 2, 4, 6)
                    $d = $n * 2;
                    $oddSum += ($d > 9) ? ($d - 9) : $d;
                } else { // Even positions (1, 3, 5)
                    $evenSum += $n;
                }
            }

            $totalSum = $evenSum + $oddSum;
            $controlDigit = (10 - ($totalSum % 10)) % 10;
            $controlLetters = 'JABCDEFGHI';
            $expectedControlLetter = $controlLetters[$controlDigit];

            $isLetterType = in_array($letter, ['P', 'Q', 'S', 'K', 'W']);
            $isDigitType = in_array($letter, ['A', 'B', 'E', 'H']);

            $isValid = false;
            if ($isLetterType && $control === $expectedControlLetter) {
                $isValid = true;
            } elseif ($isDigitType && (int)$control === $controlDigit) {
                $isValid = true;
            } elseif ($control === (string)$controlDigit || $control === $expectedControlLetter) {
                $isValid = true;
            }

            if ($isValid) {
                return [
                    'valid' => true,
                    'type' => 'CIF',
                    'formatted' => $clean,
                    'suggested' => $clean,
                    'message' => 'CIF válido',
                    'expected_letter' => (string)$controlDigit,
                    'was_completed' => false,
                ];
            } else {
                $expected = $isLetterType ? $expectedControlLetter : (string)$controlDigit;
                $suggestedCif = $letter . $digits . $expected;
                return [
                    'valid' => false,
                    'type' => 'CIF',
                    'formatted' => $clean,
                    'suggested' => $suggestedCif,
                    'message' => "El código de control del CIF no es válido.",
                    'expected_letter' => $expected,
                    'was_completed' => false,
                ];
            }
        }

        return [
            'valid' => false,
            'type' => 'UNKNOWN',
            'formatted' => $clean,
            'suggested' => null,
            'message' => 'Formato no reconocido (ej: 12345678Z, Y1234567Z o B12345678).',
            'expected_letter' => null,
            'was_completed' => false,
        ];
    }

    /**
     * Backward-compatible validate method.
     *
     * @param string|null $document
     * @return array{valid: bool, type: string, formatted: string, message: string|null, expected_letter: string|null, suggested: string|null, was_completed: bool}
     */
    public static function validate(?string $document): array
    {
        return self::autoCompleteAndValidate($document);
    }
}
