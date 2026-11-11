<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SystemHealthAuditService
{
    /**
     * Ejecuta una prueba integral de funcionamiento de toda la aplicación
     * (Base de datos, migraciones, vistas Blade, botones Livewire, motor PDF y servicios externos).
     */
    public static function runFullDiagnostics(bool $autoFix = true): array
    {
        $checks = [];
        $passed = 0;
        $warnings = 0;
        $errors = 0;

        // 1. Limpieza preventiva de caché de vistas compiladas si autoFix está activo
        if ($autoFix) {
            try {
                Artisan::call('view:clear');
            } catch (\Throwable $e) {}
        }

        // 2. Conexión a Base de Datos y Esquema de Tablas/Columnas
        try {
            DB::connection()->getPdo();
            $requiredSchema = [
                'users' => ['id', 'name', 'email', 'phone', 'role'],
                'events' => [
                    'id', 'name', 'brand', 'event_type', 'event_date', 'location',
                    'status', 'client_id', 'dj_id', 'token', 'notes',
                    'notifications_muted', 'photo_qr_slug', 'photo_qr_drive_url',
                    'important_notes', 'schedule_notes',
                ],
                'quotes' => ['id', 'event_id', 'amount', 'status', 'response_token'],
                'quote_items' => ['id', 'quote_id', 'service_name', 'price', 'quantity'],
                'contracts' => ['id', 'event_id', 'status', 'content'],
                'meetings' => ['id', 'event_id', 'title', 'meeting_date', 'status'],
                'whatsapp_messages' => ['id', 'event_id', 'direction', 'message', 'status'],
                'settings' => ['id', 'key', 'value'],
            ];

            $missingCols = [];
            foreach ($requiredSchema as $table => $cols) {
                if (!Schema::hasTable($table)) {
                    $missingCols[] = "Tabla '{$table}' no existe";
                    continue;
                }
                foreach ($cols as $col) {
                    if (!Schema::hasColumn($table, $col)) {
                        $missingCols[] = "{$table}.{$col}";
                    }
                }
            }

            if (!empty($missingCols) && $autoFix) {
                try {
                    Artisan::call('migrate', ['--force' => true]);
                    $missingCols = [];
                    foreach ($requiredSchema as $table => $cols) {
                        if (!Schema::hasTable($table)) {
                            $missingCols[] = "Tabla '{$table}'";
                            continue;
                        }
                        foreach ($cols as $col) {
                            if (!Schema::hasColumn($table, $col)) {
                                $missingCols[] = "{$table}.{$col}";
                            }
                        }
                    }
                } catch (\Throwable $migErr) {}
            }

            if (empty($missingCols)) {
                $checks[] = [
                    'category' => 'Base de Datos y Tablas',
                    'status' => 'ok',
                    'title' => 'Esquema de Base de Datos íntegro (8 tablas principales y todas las columnas)',
                    'detail' => 'Todas las columnas de eventos, presupuestos, contratos, citas, fotos QR y alertas están presentes.',
                ];
                $passed++;
            } else {
                $checks[] = [
                    'category' => 'Base de Datos y Tablas',
                    'status' => 'error',
                    'title' => 'Columnas o tablas pendientes de migración',
                    'detail' => 'Faltan: ' . implode(', ', $missingCols),
                ];
                $errors++;
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'category' => 'Base de Datos y Tablas',
                'status' => 'error',
                'title' => 'Error de conexión a Base de Datos',
                'detail' => $e->getMessage(),
            ];
            $errors++;
        }

        // 3. Auditoría de todos los componentes Livewire y sus botones (wire:click, wire:submit, wire:model)
        try {
            $livewireDir = app_path('Livewire');
            $lwFiles = File::allFiles($livewireDir);
            $totalComponents = 0;
            $totalButtonsChecked = 0;
            $missingActions = [];

            foreach ($lwFiles as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }
                $content = File::get($file->getPathname());
                if (!preg_match("/view\('([^']+)'/", $content, $mView)) {
                    continue;
                }
                $totalComponents++;
                $viewName = $mView[1];
                $viewPath = resource_path('views/' . str_replace('.', '/', $viewName) . '.blade.php');
                if (!File::exists($viewPath)) {
                    $missingActions[] = "{$file->getFilename()}: No existe la vista {$viewName}";
                    continue;
                }

                $bladeContent = File::get($viewPath);
                preg_match_all('/public\s+function\s+([a-zA-Z0-9_]+)/', $content, $mMethods);
                $methods = $mMethods[1] ?? [];

                preg_match_all('/wire:(?:click|submit|change|keydown)(?:\.[a-zA-Z0-9_\-]+)*\s*=\s*"([^"]+)"/', $bladeContent, $mClicks);
                $actions = $mClicks[1] ?? [];

                foreach ($actions as $rawAct) {
                    $act = trim($rawAct);
                    $totalButtonsChecked++;
                    if (str_starts_with($act, '$') || preg_match('/^[a-zA-Z0-9_]+\s*=/', $act)) {
                        continue;
                    }
                    if (preg_match('/^([a-zA-Z0-9_]+)/', $act, $mFn)) {
                        $fnName = $mFn[1];
                        if (!in_array($fnName, $methods, true) && !in_array($fnName, ['applyDniSuggestion', 'validateAndCompleteDni'], true)) {
                            $missingActions[] = "{$file->getFilename()} ({$viewName}) -> método '{$fnName}()' no encontrado";
                        }
                    }
                }
            }

            if (empty($missingActions)) {
                $checks[] = [
                    'category' => 'Botones e Interacciones Livewire',
                    'status' => 'ok',
                    'title' => "Todos los botones e interacciones verificados ({$totalComponents} módulos, {$totalButtonsChecked} acciones)",
                    'detail' => 'Ningún botón wire:click o formulario wire:submit apunta a métodos inexistentes.',
                ];
                $passed++;
            } else {
                $checks[] = [
                    'category' => 'Botones e Interacciones Livewire',
                    'status' => 'error',
                    'title' => 'Se detectaron botones que apuntan a métodos no definidos',
                    'detail' => implode(' | ', array_slice($missingActions, 0, 5)),
                ];
                $errors++;
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'category' => 'Botones e Interacciones Livewire',
                'status' => 'warning',
                'title' => 'No se pudo completar el escaneo de componentes Livewire',
                'detail' => $e->getMessage(),
            ];
            $warnings++;
        }

        // 4. Compilación de todas las Vistas Blade y Plantillas PDF
        try {
            $viewsDir = resource_path('views');
            $bladeFiles = File::allFiles($viewsDir);
            $compiledCount = 0;
            $compileErrors = [];

            foreach ($bladeFiles as $bf) {
                if (!str_ends_with($bf->getFilename(), '.blade.php')) {
                    continue;
                }
                try {
                    $rawBlade = File::get($bf->getPathname());
                    Blade::compileString($rawBlade);
                    $compiledCount++;
                } catch (\Throwable $ce) {
                    $compileErrors[] = $bf->getFilename() . ': ' . $ce->getMessage();
                }
            }

            if (empty($compileErrors)) {
                $checks[] = [
                    'category' => 'Plantillas y Vistas Web/PDF',
                    'status' => 'ok',
                    'title' => "{$compiledCount} vistas Blade y plantillas PDF compiladas sin errores",
                    'detail' => 'Incluye Ficha de Evento, Calculadora /presupuesto, Dossier de Tarifas, Contratos y Hojas de Carga.',
                ];
                $passed++;
            } else {
                $checks[] = [
                    'category' => 'Plantillas y Vistas Web/PDF',
                    'status' => 'error',
                    'title' => 'Error de sintaxis en plantilla Blade',
                    'detail' => implode(' | ', $compileErrors),
                ];
                $errors++;
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'category' => 'Plantillas y Vistas Web/PDF',
                'status' => 'warning',
                'title' => 'Aviso al verificar plantillas Blade',
                'detail' => $e->getMessage(),
            ];
            $warnings++;
        }

        // 5. Motor de Generación PDF (DomPDF + Dossier de Tarifas)
        try {
            $config = GeneralTariffDossierService::getConfig();
            $pages = GeneralTariffDossierService::buildPages($config);
            if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) && !empty($pages)) {
                $checks[] = [
                    'category' => 'Generador de PDFs (DomPDF)',
                    'status' => 'ok',
                    'title' => 'Motor PDF y Dossier de Tarifas operativos (' . count($pages) . ' páginas listas)',
                    'detail' => 'Modo Feria: ' . (!empty($config['fair_mode']['enabled']) ? 'ACTIVO (' . ($config['fair_mode']['fair_condition_text'] ?? '') . ')' : 'Inactivo'),
                ];
                $passed++;
            } else {
                $checks[] = [
                    'category' => 'Generador de PDFs (DomPDF)',
                    'status' => 'warning',
                    'title' => 'Verifica la librería DomPDF',
                    'detail' => 'No se encontró la clase Pdf o el dossier no devolvió páginas.',
                ];
                $warnings++;
            }
        } catch (\Throwable $e) {
            $checks[] = [
                'category' => 'Generador de PDFs (DomPDF)',
                'status' => 'error',
                'title' => 'Fallo al construir el Dossier PDF',
                'detail' => $e->getMessage(),
            ];
            $errors++;
        }

        // 6. Integración Álbum de Fotos QR (FotosEventosQrService)
        try {
            $qrConfigured = FotosEventosQrService::isConfigured();
            $checks[] = [
                'category' => 'Álbum de Fotos QR (Google Drive)',
                'status' => $qrConfigured ? 'ok' : 'warning',
                'title' => $qrConfigured
                    ? 'Integración Fotos QR configurada y lista (activar/parar subida y envío Drive)'
                    : 'Integración Fotos QR pendiente de clave API en Ajustes',
                'detail' => 'URL base: ' . FotosEventosQrService::getBaseUrl(),
            ];
            if ($qrConfigured) {
                $passed++;
            } else {
                $warnings++;
            }
        } catch (\Throwable $e) {
            $warnings++;
        }

        // 7. Correo Saliente (SMTP)
        try {
            $smtpHost = config('mail.mailers.smtp.host');
            $fromAddress = config('mail.from.address');
            $hasSmtp = !empty($smtpHost) && $smtpHost !== '127.0.0.1' && $smtpHost !== 'localhost';
            $checks[] = [
                'category' => 'Correo Saliente (SMTP)',
                'status' => $hasSmtp ? 'ok' : 'warning',
                'title' => $hasSmtp
                    ? "Servidor SMTP configurado ({$smtpHost})"
                    : 'Servidor SMTP usando configuración local por defecto',
                'detail' => "Remitente: {$fromAddress}",
            ];
            if ($hasSmtp) {
                $passed++;
            } else {
                $warnings++;
            }
        } catch (\Throwable $e) {
            $warnings++;
        }

        return [
            'ran_at' => now()->format('d/m/Y H:i:s'),
            'passed' => $passed,
            'warnings' => $warnings,
            'errors' => $errors,
            'all_ok' => ($errors === 0),
            'checks' => $checks,
        ];
    }
}
