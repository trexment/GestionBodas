/**
 * Núñez and Son - Validador y Autocompletador Inteligente de DNI / NIE / CIF
 */
(function() {
    'use strict';

    const DNI_LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    window.DniHelper = {
        LETTERS: DNI_LETTERS,

        clean(val) {
            if (!val) return '';
            return String(val).toUpperCase().replace(/[\s\-\.]/g, '');
        },

        calculateDniLetter(num) {
            const cleanDigits = String(num).replace(/\D/g, '');
            if (!cleanDigits) return '';
            const n = parseInt(cleanDigits, 10);
            return DNI_LETTERS[n % 23];
        },

        calculateNieLetter(prefix, digits) {
            const p = String(prefix).toUpperCase();
            let pNum = '0';
            if (p === 'X') pNum = '0';
            else if (p === 'Y') pNum = '1';
            else if (p === 'Z') pNum = '2';
            const fullStr = pNum + String(digits).replace(/\D/g, '').padStart(7, '0');
            const n = parseInt(fullStr, 10);
            return DNI_LETTERS[n % 23];
        },

        autoComplete(val) {
            const clean = this.clean(val);
            if (!clean) return { value: '', wasCompleted: false };

            // 1. Exactamente 7 u 8 dígitos numéricos -> Auto-añade letra de DNI
            if (/^\d{7,8}$/.test(clean)) {
                const padded = clean.padStart(8, '0');
                const letter = this.calculateDniLetter(padded);
                return {
                    value: padded + letter,
                    wasCompleted: true,
                    expectedLetter: letter,
                    type: 'DNI'
                };
            }

            // 2. NIE sin letra: X, Y o Z seguido de 7 dígitos -> Auto-añade letra de NIE
            const nieMatch = clean.match(/^([XYZ])(\d{7})$/);
            if (nieMatch) {
                const letter = this.calculateNieLetter(nieMatch[1], nieMatch[2]);
                return {
                    value: clean + letter,
                    wasCompleted: true,
                    expectedLetter: letter,
                    type: 'NIE'
                };
            }

            return { value: clean, wasCompleted: false };
        },

        validate(val) {
            const clean = this.clean(val);
            if (!clean) {
                return { valid: false, type: 'EMPTY', formatted: '', message: null };
            }

            // DNI completo: 1-8 dígitos + 1 letra
            const dniMatch = clean.match(/^(\d{1,8})([A-Z])$/);
            if (dniMatch) {
                const padded = dniMatch[1].padStart(8, '0');
                const letter = dniMatch[2];
                const expected = this.calculateDniLetter(padded);
                const suggested = padded + expected;

                if (letter === expected) {
                    return {
                        valid: true,
                        type: 'DNI',
                        formatted: padded + letter,
                        suggested: suggested,
                        expectedLetter: expected,
                        message: 'DNI válido'
                    };
                } else {
                    return {
                        valid: false,
                        type: 'DNI',
                        formatted: padded + letter,
                        suggested: suggested,
                        expectedLetter: expected,
                        message: `Letra '${letter}' incorrecta. Le corresponde la '${expected}'.`
                    };
                }
            }

            // NIE completo: [XYZ] + 7 dígitos + 1 letra
            const nieMatch = clean.match(/^([XYZ])(\d{7})([A-Z])$/);
            if (nieMatch) {
                const prefix = nieMatch[1];
                const digits = nieMatch[2];
                const letter = nieMatch[3];
                const expected = this.calculateNieLetter(prefix, digits);
                const suggested = prefix + digits + expected;

                if (letter === expected) {
                    return {
                        valid: true,
                        type: 'NIE',
                        formatted: clean,
                        suggested: suggested,
                        expectedLetter: expected,
                        message: 'NIE válido'
                    };
                } else {
                    return {
                        valid: false,
                        type: 'NIE',
                        formatted: clean,
                        suggested: suggested,
                        expectedLetter: expected,
                        message: `Letra '${letter}' incorrecta. Le corresponde la '${expected}'.`
                    };
                }
            }

            // Solo números (incompleto)
            if (/^\d{1,8}$/.test(clean)) {
                const expected = this.calculateDniLetter(clean);
                return {
                    valid: false,
                    type: 'INCOMPLETE',
                    formatted: clean,
                    suggested: clean.padStart(8, '0') + expected,
                    expectedLetter: expected,
                    message: `Falta la letra. Le corresponde la '${expected}'.`
                };
            }

            return {
                valid: false,
                type: 'UNKNOWN',
                formatted: clean,
                suggested: null,
                message: 'Formato de documento no válido.'
            };
        }
    };

    // Auto-escucha global para cualquier input con atributo data-dni-input o data-dni
    function handleDniInput(e) {
        const target = e.target;
        if (!target || !target.matches('input[data-dni-input], input[data-dni]')) return;

        // Convertir a mayúsculas
        const initialVal = target.value;
        const upperVal = initialVal.toUpperCase();
        if (initialVal !== upperVal) {
            target.value = upperVal;
        }

        // Si el usuario introduce 8 dígitos y no hay letra todavía, autocompletar de inmediato
        const cleanDigits = upperVal.replace(/[\s\-\.]/g, '');
        if (/^\d{8}$/.test(cleanDigits) || /^([XYZ])\d{7}$/.test(cleanDigits)) {
            const completed = window.DniHelper.autoComplete(cleanDigits);
            if (completed.wasCompleted && completed.value !== upperVal) {
                target.value = completed.value;
                // Disparar evento para sincronizar con Livewire
                target.dispatchEvent(new Event('input', { bubbles: true }));
                target.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    }

    function handleDniBlur(e) {
        const target = e.target;
        if (!target || !target.matches('input[data-dni-input], input[data-dni]')) return;

        const completed = window.DniHelper.autoComplete(target.value);
        if (completed.wasCompleted && completed.value !== target.value) {
            target.value = completed.value;
            target.dispatchEvent(new Event('input', { bubbles: true }));
            target.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    document.addEventListener('input', handleDniInput, true);
    document.addEventListener('blur', handleDniBlur, true);

})();
