<?php

namespace App\Services;

class DocumentValidationService
{
    /**
     * DNI/NIE Control letter lookup table (Modulo 23).
     */
    const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /**
     * Validate and check Spanish DNI, NIE or CIF.
     *
     * @param string|null $document
     * @return array{valid: bool, type: string, formatted: string, message: string|null, expected_letter: string|null}
     */
    public static function validate(?string $document): array
    {
        if (empty($document)) {
            return [
                'valid' => false,
                'type' => 'EMPTY',
                'formatted' => '',
                'message' => 'El DNI/NIE es obligatorio.',
                'expected_letter' => null,
            ];
        }

        $clean = strtoupper(trim(preg_replace('/[\s\-\.]/', '', $document)));

        // 1. Check DNI (8 digits + 1 letter)
        if (preg_match('/^(\d{1,8})([A-Z])$/', $clean, $matches)) {
            $number = (int)$matches[1];
            $letter = $matches[2];
            $paddedNumber = str_pad($number, 8, '0', STR_PAD_LEFT);
            $expectedLetter = self::DNI_LETTERS[$number % 23];

            if ($letter === $expectedLetter) {
                return [
                    'valid' => true,
                    'type' => 'DNI',
                    'formatted' => $paddedNumber . $letter,
                    'message' => null,
                    'expected_letter' => $expectedLetter,
                ];
            } else {
                return [
                    'valid' => false,
                    'type' => 'DNI',
                    'formatted' => $paddedNumber . $letter,
                    'message' => "La letra del DNI no es correcta. Para el número {$paddedNumber} le corresponde la letra {$expectedLetter}.",
                    'expected_letter' => $expectedLetter,
                ];
            }
        }

        // 2. Check NIE (X, Y, Z + 7 digits + 1 letter)
        if (preg_match('/^([XYZ])(\d{7})([A-Z])$/', $clean, $matches)) {
            $prefix = $matches[1];
            $digits = $matches[2];
            $letter = $matches[3];

            $prefixNum = match ($prefix) {
                'X' => 0,
                'Y' => 1,
                'Z' => 2,
            };

            $fullNumber = (int)($prefixNum . $digits);
            $expectedLetter = self::DNI_LETTERS[$fullNumber % 23];

            if ($letter === $expectedLetter) {
                return [
                    'valid' => true,
                    'type' => 'NIE',
                    'formatted' => $prefix . $digits . $letter,
                    'message' => null,
                    'expected_letter' => $expectedLetter,
                ];
            } else {
                return [
                    'valid' => false,
                    'type' => 'NIE',
                    'formatted' => $prefix . $digits . $letter,
                    'message' => "La letra del NIE no es correcta. Para {$prefix}{$digits} le corresponde la letra {$expectedLetter}.",
                    'expected_letter' => $expectedLetter,
                ];
            }
        }

        // 3. Check CIF (Empresas / Personas jurídicas)
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

            // Some entities require letter, some digit, some allow either
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
                    'message' => null,
                    'expected_letter' => (string)$controlDigit,
                ];
            } else {
                return [
                    'valid' => false,
                    'type' => 'CIF',
                    'formatted' => $clean,
                    'message' => "El código de control del CIF no es válido.",
                    'expected_letter' => null,
                ];
            }
        }

        // Only 8 digits without letter
        if (preg_match('/^\d{7,8}$/', $clean)) {
            $num = (int)$clean;
            $exp = self::DNI_LETTERS[$num % 23];
            return [
                'valid' => false,
                'type' => 'INCOMPLETE',
                'formatted' => $clean,
                'message' => "Falta la letra del DNI. Le corresponde la letra {$exp}.",
                'expected_letter' => $exp,
            ];
        }

        return [
            'valid' => false,
            'type' => 'UNKNOWN',
            'formatted' => $clean,
            'message' => 'Formato de DNI / NIE / CIF no válido (ej: 12345678Z o Y1234567Z).',
            'expected_letter' => null,
        ];
    }
}
