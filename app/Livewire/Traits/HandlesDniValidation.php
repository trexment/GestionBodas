<?php

namespace App\Livewire\Traits;

use App\Services\DocumentValidationService;

/**
 * Trait reutilizable para componentes Livewire que gestionan validación en tiempo real y sugerencia de corrección de DNI / NIE / CIF.
 */
trait HandlesDniValidation
{
    /**
     * Cache or store validations for DNI fields: ['field_name' => [...]]
     *
     * @var array<string, array|null>
     */
    public $dniValidations = [];

    /**
     * Process, autocomplete and validate a DNI field.
     *
     * @param string $field
     * @param string|null $value
     * @return void
     */
    public function validateAndCompleteDni(string $field, ?string $value): void
    {
        if (empty($value)) {
            $this->$field = '';
            $this->dniValidations[$field] = null;
            return;
        }

        $res = DocumentValidationService::autoCompleteAndValidate($value);

        // No sobrescribir ni autocompletar automáticamente lo que escribe el usuario; solo validar
        $this->$field = strtoupper(trim($value));

        $this->dniValidations[$field] = $res;
    }

    /**
     * Apply suggested correct DNI (1-click fix).
     *
     * @param string $field
     * @param string $suggested
     * @return void
     */
    public function applyDniSuggestion(string $field, string $suggested): void
    {
        $this->$field = $suggested;
        $this->dniValidations[$field] = DocumentValidationService::autoCompleteAndValidate($suggested);
    }
}
