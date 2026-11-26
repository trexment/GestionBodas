<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Invoice;
use App\Services\BillingReportService;
use Carbon\Carbon;
use Livewire\Component;

/**
 * Componente Livewire para la gestión de facturación, control de ingresos por evento, libro de cobros y pagos a proveedores externos.
 */
class BillingManager extends Component
{
    // Vista activa: 'events' (Control de pagos por evento) o 'ledger' (Libro cronológico de cobros/facturas)
    public string $viewMode = 'events';

    // Filtros
    public string $season = 'all'; // 'all' | '2026' | '2027' | '2028' (Año de celebración del evento / Temporada)
    public string $year = 'all';
    public string $dateBasis = 'event_date'; // 'event_date' (por defecto) | 'payment_date'
    public string $eventSortOrder = 'event_date_asc'; // 'event_date_asc' (Nov 2026 -> Dic 2026 -> 2027...) | 'event_date_desc'
    public string $quarter = 'all'; // 'all' | '1' | '2' | '3' | '4'
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $paymentStatus = 'with_payments'; // 'all' | 'with_payments' | 'paid' | 'partial' | 'unpaid'
    public string $paymentMethod = 'all'; // 'all' | 'bizum' | 'transfer' | 'cash' | 'card' | 'other'
    public string $docType = 'all'; // 'all' | 'factura' | 'recibo'
    public string $brand = 'all'; // 'all' | 'nunez_and_son' | 'javnx'
    public string $eventStatus = 'active'; // 'active' | 'confirmed_completed' | 'all'
    public string $search = '';

    // Modal Rápido para Registrar / Editar Pago de un Evento desde el panel
    public bool $showQuickPaymentModal = false;
    public ?int $quickEventId = null;
    public string $quickEventName = '';
    public string $quickPaymentType = 'deposit'; // 'deposit' (Señal) | 'final' (Liquidación final)
    public $quickAmount = 0;
    public string $quickMethod = 'bizum';
    public string $quickDate = '';
    public string $quickNotes = '';
    public string $quickCollectedBy = 'Fran';
    public bool $quickGenerateReceipt = true;

    // Modal para Registrar / Editar Reserva y Pagos a Proveedor (Fotomatón / Plataforma 360º)
    public bool $showProviderModal = false;
    public ?int $provBookingId = null;
    public ?int $provEventId = null;
    public string $provEventName = '';
    public string $provKey = 'logrono';
    public string $provName = 'Logroño';
    public string $provService = 'Fotomatón + Plataforma 360º';
    public $provClientPrice = 0;
    public float $provDetectedQuotePrice = 0.0;
    public string $provDetectedItemsText = '';
    public $provTotalCost = 0;
    public bool $provDepositPaid = true;
    public $provDepositAmount = 100;
    public string $provDepositDate = '';
    public string $provDepositMethod = 'bizum';
    public bool $provFinalPaid = false;
    public $provFinalAmount = 0;
    public string $provFinalDate = '';
    public string $provFinalMethod = 'transfer';
    public string $provPaidBy = 'Fran';
    public string $provNotes = '';
    public bool $provHasRefund = false;
    public $provRefundAmount = 0;
    public string $provRefundRecipient = '50_50';
    public $provRefundFranAmount = 0;
    public $provRefundMiguelAmount = 0;
    public $provManagementFee = 0;
    public $provSocialSecurityFee = 0;
    public $provTaxFee = 0;
    public $provRegistrationFee = 0;
    public string $provSettlementNumber = '';
    public string $provInvoiceNumber = '';

    // Modal para Registrar / Editar Compra de Material
    public bool $showMaterialModal = false;
    public ?int $materialExpenseId = null;
    public string $matConcept = '';
    public string $matCategory = 'general';
    public $matAmount = 0;
    public string $matPurchaseDate = '';
    public string $matPaidBy = 'Fran';
    public string $matPaymentMethod = 'card';
    public string $matSupplier = '';
    public ?int $matEventId = null;
    public string $matSeason = '';
    public string $matInvoiceNumber = '';
    public string $matNotes = '';

    // Modal para Configurar Socios y Porcentajes de Reparto
    public bool $showPartnersModal = false;
    public array $partnersList = [];

    // Modal para Personalizar Reparto del Evento
    public bool $showEventSplitModal = false;
    public ?int $splitEventId = null;
    public string $splitEventName = '';
    public float $splitEventTotal = 0.0;
    public float $splitEventCosts = 0.0;
    public float $splitEventNet = 0.0;
    public string $splitMode = 'custom_amounts'; // 'custom_amounts' | 'percentages'
    public array $splitPartners = [];
    public string $splitNotes = '';

    /**
     * Inicializa el componente comprobando el rol de administrador y estableciendo la fecha actual para cobros rápidos.
     *
     * @return void
     */
    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }

        $this->quickDate = now()->format('Y-m-d');
    }

    /**
     * Restablece todos los filtros de búsqueda y visualización a sus valores predeterminados.
     *
     * @return void
     */
    public function resetFilters()
    {
        $this->season = 'all';
        $this->year = 'all';
        $this->dateBasis = 'payment_date';
        $this->quarter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->paymentStatus = 'with_payments';
        $this->paymentMethod = 'all';
        $this->docType = 'all';
        $this->brand = 'all';
        $this->eventStatus = 'active';
        $this->search = '';
    }

    /**
     * Filtra los registros por año de temporada del evento.
     *
     * @param string $season Año de la temporada o 'all'.
     * @return void
     */
    public function setSeason(string $season)
    {
        $this->season = $season;
    }

    /**
     * Alterna el filtro por método de pago.
     *
     * @param string $method Método de pago a filtrar.
     * @return void
     */
    public function filterByMethod(string $method)
    {
        $this->paymentMethod = ($this->paymentMethod === $method) ? 'all' : $method;
    }

    /**
     * Establece el filtro por estado de pago del evento.
     *
     * @param string $status Estado de pago ('all', 'with_payments', 'paid', 'partial', 'unpaid').
     * @return void
     */
    public function filterByPaymentStatus(string $status)
    {
        $this->paymentStatus = $status;
    }

    /**
     * Abre el modal de registro o edición rápida de un pago (señal o liquidación final) para un evento.
     *
     * @param int $eventId Identificador del evento.
     * @param string $type Tipo de pago ('deposit' o 'final').
     * @return void
     */
    public function openQuickPayment(int $eventId, string $type = 'deposit')
    {
        $event = Event::with(['quotes', 'invoices'])->find($eventId);
        if (!$event) {
            return;
        }

        $fb = $event->financial_breakdown;
        $this->quickEventId = $event->id;
        $this->quickEventName = $event->name;
        $this->quickPaymentType = $type;

        if ($type === 'deposit') {
            $this->quickAmount = $fb['deposit_amount'] > 0
                ? $fb['deposit_amount']
                : ($fb['signal_expected'] > 0 ? $fb['signal_expected'] : 200);
            $this->quickMethod = $fb['deposit_method'] ?: 'bizum';
            $this->quickDate = $fb['deposit_date']
                ? Carbon::parse($fb['deposit_date'])->format('Y-m-d')
                : now()->format('Y-m-d');
            $this->quickCollectedBy = (string) ($event->deposit_collected_by ?: ($fb['deposit_collected_by'] ?: 'Fran'));
            $this->quickNotes = (string) ($event->deposit_notes ?? '');
            $this->quickGenerateReceipt = true;
        } else {
            $this->quickAmount = $fb['pending_amount'] > 0 ? $fb['pending_amount'] : $fb['final_paid_amount'];
            $this->quickMethod = $fb['final_method'] ?: 'cash';
            $this->quickDate = $fb['final_paid_date']
                ? Carbon::parse($fb['final_paid_date'])->format('Y-m-d')
                : now()->format('Y-m-d');
            $this->quickCollectedBy = (string) ($fb['final_collected_by'] ?: 'Fran');
            $this->quickNotes = '';
            $this->quickGenerateReceipt = true;
        }

        $this->showQuickPaymentModal = true;
    }

    /**
     * Cierra el modal de pago rápido y limpia el identificador del evento activo.
     *
     * @return void
     */
    public function closeQuickPaymentModal()
    {
        $this->showQuickPaymentModal = false;
        $this->quickEventId = null;
    }

    /**
     * Valida y guarda el pago rápido (señal o cobro final) del evento, generando o actualizando su recibo correspondiente.
     *
     * @return void
     */
    public function saveQuickPayment()
    {
        $this->validate([
            'quickAmount' => 'required|numeric|min:0',
            'quickMethod' => 'required|in:bizum,transfer,cash,card,other',
            'quickDate' => 'required|date',
            'quickNotes' => 'nullable|string|max:500',
        ]);

        $event = Event::with(['quotes', 'invoices'])->find($this->quickEventId);
        if (!$event) {
            $this->closeQuickPaymentModal();
            return;
        }

        $amount = round((float) $this->quickAmount, 2);
        $methodLabel = match ($this->quickMethod) {
            'bizum' => 'Bizum',
            'transfer' => 'Transferencia Bancaria',
            'cash' => 'Efectivo',
            'card' => 'Tarjeta / TPV',
            default => 'Otro método',
        };

        $latestQuote = $event->quotes->where('status', 'accepted')->sortByDesc('id')->first()
            ?? $event->quotes->sortByDesc('id')->first();

        \App\Models\Event::ensureTokenColumnsExist();
        \App\Models\Invoice::ensureColumnsExist();

        if ($this->quickPaymentType === 'deposit') {
            $hasPaid = $amount > 0;
            $newStatus = in_array($event->status, ['completed'], true) ? 'completed' : ($hasPaid ? 'confirmed' : $event->status);

            $updateEvent = [
                'status' => $newStatus,
                'deposit_paid' => $hasPaid,
                'deposit_paid_amount' => $amount,
                'deposit_payment_method' => $this->quickMethod,
                'deposit_paid_at' => $this->quickDate,
                'deposit_notes' => $this->quickNotes,
            ];

            if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'deposit_collected_by')) {
                $updateEvent['deposit_collected_by'] = $this->quickCollectedBy ?: 'Fran';
            }

            $event->update($updateEvent);

            if ($this->quickGenerateReceipt && $hasPaid) {
                $noteText = "Señal de reserva cobrada vía {$methodLabel} el " . Carbon::parse($this->quickDate)->format('d/m/Y') . ($this->quickNotes ? " ({$this->quickNotes})" : '');

                $existingSignalInv = $event->invoices->first(function ($inv) {
                    $n = mb_strtolower((string) ($inv->notes ?? ''));
                    return str_contains($n, 'señal') || str_contains($n, 'senal');
                });

                $invData = [
                    'amount' => $amount,
                    'total' => $amount,
                    'issue_date' => $this->quickDate,
                    'status' => 'paid',
                    'payment_method' => $this->quickMethod,
                    'notes' => $noteText,
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                    $invData['collected_by'] = $this->quickCollectedBy ?: 'Fran';
                }

                if ($existingSignalInv) {
                    $existingSignalInv->update($invData);
                } else {
                    $nextNum = Invoice::nextNumber('recibo');
                    $invData['quote_id'] = $latestQuote?->id;
                    $invData['invoice_number'] = $nextNum;
                    $invData['type'] = 'recibo';
                    $invData['tax'] = 0.00;
                    $invData['tax_rate'] = 0.00;
                    $event->invoices()->create($invData);
                }
            }

            session()->flash('message', "✅ Señal de " . number_format($amount, 2, ',', '.') . " € ({$methodLabel} - Cobrado por {$this->quickCollectedBy}) guardada en '{$event->name}'.");
        } else {
            if ($amount > 0) {
                $noteText = "Liquidación final del evento cobrada vía {$methodLabel} el " . Carbon::parse($this->quickDate)->format('d/m/Y') . ($this->quickNotes ? " ({$this->quickNotes})" : '');

                $recData = [
                    'amount' => $amount,
                    'tax' => 0.00,
                    'tax_rate' => 0.00,
                    'total' => $amount,
                    'issue_date' => $this->quickDate,
                    'status' => 'paid',
                    'payment_method' => $this->quickMethod,
                    'notes' => $noteText,
                ];

                if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                    $recData['collected_by'] = $this->quickCollectedBy ?: 'Cuenta Conjunta / Fondo Común';
                }

                // Buscar si ya existe un recibo de cobro final para este evento
                $existingFinalInv = $event->invoices->first(function ($inv) {
                    $n = mb_strtolower((string) ($inv->notes ?? ''));
                    return (str_contains($n, 'liquidación') || str_contains($n, 'liquidacion') || str_contains($n, 'final')) && !str_contains($n, 'señal') && !str_contains($n, 'senal');
                }) ?? $event->invoices->where('type', 'recibo')->first(function ($inv) {
                    $n = mb_strtolower((string) ($inv->notes ?? ''));
                    return !str_contains($n, 'señal') && !str_contains($n, 'senal');
                });

                if ($existingFinalInv) {
                    $existingFinalInv->update($recData);
                } else {
                    $nextNum = Invoice::nextNumber('recibo');
                    $recData['quote_id'] = $latestQuote?->id;
                    $recData['invoice_number'] = $nextNum;
                    $recData['type'] = 'recibo';
                    $event->invoices()->create($recData);
                }
            }

            session()->flash('message', "✅ Cobro final de " . number_format($amount, 2, ',', '.') . " € ({$methodLabel} - Cobrado por {$this->quickCollectedBy}) guardado en '{$event->name}'.");
        }

        $this->closeQuickPaymentModal();
    }

    /**
     * Actualiza rápidamente el socio recaudador o asigna un pago / evento a Fondo Común.
     *
     * @param int $eventId Identificador del evento.
     * @param string $paymentType 'deposit' | 'final' | 'all'
     * @param string $newCollector 'Fran' | 'Miguel' | 'Ezequiel' | 'Cuenta Conjunta / Fondo Común'
     * @return void
     */
    public function setPaymentCollector(int $eventId, string $paymentType, string $newCollector): void
    {
        $event = Event::with('invoices')->find($eventId);
        if (!$event) {
            return;
        }

        \App\Models\Event::ensureTokenColumnsExist();
        \App\Models\Invoice::ensureColumnsExist();

        $collectorName = trim($newCollector) ?: 'Cuenta Conjunta / Fondo Común';

        if (in_array($paymentType, ['deposit', 'all'], true)) {
            if (\Illuminate\Support\Facades\Schema::hasColumn('events', 'deposit_collected_by')) {
                $event->update(['deposit_collected_by' => $collectorName]);
            }
            $signalInv = $event->invoices->first(function ($inv) {
                $n = mb_strtolower((string) ($inv->notes ?? ''));
                return str_contains($n, 'señal') || str_contains($n, 'senal') || str_contains($n, 'reserva');
            });
            if ($signalInv && \Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                $signalInv->update(['collected_by' => $collectorName]);
            }
        }

        if (in_array($paymentType, ['final', 'all'], true)) {
            $finalInvs = $event->invoices->filter(function ($inv) {
                $n = mb_strtolower((string) ($inv->notes ?? ''));
                return !str_contains($n, 'señal') && !str_contains($n, 'senal');
            });

            if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'collected_by')) {
                foreach ($finalInvs as $inv) {
                    $inv->update(['collected_by' => $collectorName]);
                }
            }

            // Si no tiene facturas/recibos creados pero el evento tiene deposit_collected_by
            if ($event->invoices->isEmpty() && \Illuminate\Support\Facades\Schema::hasColumn('events', 'deposit_collected_by')) {
                $event->update(['deposit_collected_by' => $collectorName]);
            }
        }

        session()->flash('message', "✅ Recaudador actualizado a '{$collectorName}' en '{$event->name}'.");
    }

    /**
     * Alterna el estado de una factura o recibo entre cobrado ('paid') y pendiente ('unpaid').
     *
     * @param int $invoiceId Identificador de la factura o recibo.
     * @return void
     */
    public function toggleInvoicePaid(int $invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return;
        }
        $newStatus = $invoice->status === 'paid' ? 'unpaid' : 'paid';
        $invoice->update(['status' => $newStatus]);
        session()->flash('message', "Documento {$invoice->invoice_number} marcado como " . ($newStatus === 'paid' ? 'COBRADO' : 'PENDIENTE') . ".");
    }

    /**
     * Actualiza el método de pago de una factura o recibo y lo sincroniza con el evento si corresponde a la señal.
     *
     * @param int $invoiceId Identificador de la factura o recibo.
     * @param string $method Nuevo método de pago.
     * @return void
     */
    public function updateInvoicePaymentMethod(int $invoiceId, string $method)
    {
        if (!in_array($method, ['bizum', 'transfer', 'cash', 'card', 'other'], true)) {
            return;
        }
        $invoice = Invoice::with('event')->find($invoiceId);
        if (!$invoice) {
            return;
        }
        $invoice->update(['payment_method' => $method]);

        // Si era el recibo de la señal, sincronizar también con el evento
        $notesLower = mb_strtolower((string) ($invoice->notes ?? ''));
        if ($invoice->event && (str_contains($notesLower, 'señal') || str_contains($notesLower, 'senal'))) {
            $invoice->event->update(['deposit_payment_method' => $method]);
        }

        session()->flash('message', "Método de pago de {$invoice->invoice_number} actualizado.");
    }

    // ==================== RESERVAS Y PAGOS A PROVEEDOR (FOTOMATÓN / 360º) ====================

    /**
     * Abre el modal para gestionar la reserva y pagos a proveedor externo de un evento.
     *
     * @param int|null $eventId Identificador del evento (o null para usar el más reciente).
     * @param string $focusStep Paso inicial en el que enfocar ('deposit' o 'final').
     * @param int|null $bookingId Identificador de una reserva específica a editar si existe.
     * @return void
     */
    public function openProviderModal(?int $eventId = null, string $focusStep = 'deposit', ?int $bookingId = null)
    {
        \App\Models\EventProviderBooking::ensureTableExists();

        if ($eventId) {
            $event = Event::with(['quotes.items', 'providerBookings'])->find($eventId);
        } else {
            $event = Event::with(['quotes.items', 'providerBookings'])->orderByDesc('event_date')->first();
        }

        if (!$event) {
            return;
        }

        $this->provBookingId = $bookingId;
        $this->loadEventIntoProviderModal($event, $focusStep, $bookingId);
        $this->showProviderModal = true;
    }

    /**
     * Recarga los datos del proveedor en el modal cuando cambia el evento seleccionado.
     *
     * @param mixed $value Identificador del evento seleccionado.
     * @return void
     */
    public function updatedProvEventId($value)
    {
        if (!$value) {
            return;
        }
        $event = Event::with(['quotes.items', 'providerBookings'])->find((int) $value);
        if ($event) {
            $this->provBookingId = null;
            $this->loadEventIntoProviderModal($event, 'deposit');
        }
    }

    /**
     * Actualiza el nombre del proveedor cuando se cambia la clave del proveedor seleccionado.
     *
     * @param mixed $value Clave del proveedor en el catálogo.
     * @return void
     */
    public function updatedProvKey($value)
    {
        $catalog = \App\Models\Setting::getPhotoboothProviders();
        if (isset($catalog[$value])) {
            $this->provName = (string) ($catalog[$value]['name'] ?? ucfirst($value));
        } elseif ($value === 'vitoria') {
            $this->provName = 'Vitoria (FotomatonCheese)';
        } elseif ($value === 'logrono') {
            $this->provName = 'Logroño';
        } elseif ($value === 'artistamente') {
            $this->provName = 'Artístamente';
        }
    }

    /**
     * Carga en las propiedades del modal la información de reserva y pagos a proveedor de un evento dado.
     *
     * @param Event $event Instancia del evento.
     * @param string $focusStep Paso a destacar ('deposit' o 'final').
     * @param int|null $bookingId Identificador de una reserva específica.
     * @return void
     */
    protected function loadEventIntoProviderModal(Event $event, string $focusStep = 'deposit', ?int $bookingId = null): void
    {
        $prov = $event->external_provider_summary;

        $booking = $bookingId
            ? $event->providerBookings->firstWhere('id', $bookingId)
            : $event->providerBookings->first();

        $this->provBookingId = $booking?->id;
        $this->provEventId = $event->id;
        $this->provEventName = $event->name;
        $this->provKey = (string) ($booking?->provider_key ?: ($prov['provider_key'] ?: 'logrono'));
        $this->provName = (string) ($booking?->provider_name ?: ($prov['provider_name'] ?: 'Logroño'));
        $this->provService = (string) ($booking?->service_description ?: ($prov['service_description'] ?: 'Fotomatón / Plataforma 360º'));
        $this->provDetectedQuotePrice = (float) ($prov['detected_client_price'] ?? 0);
        $this->provClientPrice = (float) ($booking ? $booking->client_price : ($prov['client_price'] ?? 0));
        $this->provDetectedItemsText = (string) ($prov['detected_services_with_prices'] ?: ($prov['detected_services'] ?? ''));
        $this->provTotalCost = (float) ($booking ? $booking->provider_total_cost : ($prov['provider_total_cost'] > 0 ? $prov['provider_total_cost'] : ($prov['client_price'] > 0 ? $prov['client_price'] : 0)));

        $depPaid = $booking ? (bool)$booking->deposit_paid : (bool)$prov['deposit_paid'];
        $depAmt = $booking ? (float)$booking->deposit_amount : (float)$prov['deposit_amount'];
        $this->provDepositPaid = $focusStep === 'deposit' ? true : $depPaid;
        $this->provDepositAmount = $depAmt > 0 ? $depAmt : (float) ($prov['suggested_deposit'] ?: 100);
        $this->provDepositDate = ($booking?->deposit_paid_at ?: $prov['deposit_date']) ? Carbon::parse($booking?->deposit_paid_at ?: $prov['deposit_date'])->format('Y-m-d') : now()->format('Y-m-d');
        $this->provDepositMethod = (string) (($booking?->deposit_payment_method ?: $prov['deposit_method']) ?: 'bizum');

        $finPaid = $booking ? (bool)$booking->final_paid : (bool)$prov['final_paid'];
        $finAmt = $booking ? (float)$booking->final_amount : (float)($prov['final_amount'] ?? 0);

        if ($focusStep === 'final') {
            $this->provFinalPaid = true;
            $alreadyDep = $this->provDepositPaid ? (float) $this->provDepositAmount : 0.0;
            $rem = max(0.0, round((float) $this->provTotalCost - $alreadyDep, 2));
            $this->provFinalAmount = $finAmt > 0 ? $finAmt : $rem;
            $this->provFinalDate = ($booking?->final_paid_at ?: $prov['final_date']) ? Carbon::parse($booking?->final_paid_at ?: $prov['final_date'])->format('Y-m-d') : now()->format('Y-m-d');
            $this->provFinalMethod = (string) (($booking?->final_payment_method ?: $prov['final_method']) ?: 'transfer');
        } else {
            $this->provFinalPaid = $finPaid;
            $this->provFinalAmount = $finAmt;
            $this->provFinalDate = ($booking?->final_paid_at ?: $prov['final_date']) ? Carbon::parse($booking?->final_paid_at ?: $prov['final_date'])->format('Y-m-d') : now()->format('Y-m-d');
            $this->provFinalMethod = (string) (($booking?->final_payment_method ?: $prov['final_method']) ?: 'transfer');
        }

        $this->provPaidBy = (string) ($booking?->paid_by ?: ($prov['paid_by'] ?: 'Fran'));
        $this->provNotes = (string) ($booking?->notes ?: ($prov['notes'] ?? ''));

        $this->provHasRefund = $booking ? (bool) $booking->has_refund : (bool) ($prov['has_refund'] ?? false);
        $this->provRefundAmount = $booking ? (float) $booking->refund_amount : (float) ($prov['refund_amount'] ?? 0);
        $this->provRefundRecipient = $booking ? (string) ($booking->refund_recipient ?: '50_50') : (string) ($prov['refund_recipient'] ?? '50_50');
        $this->provRefundFranAmount = $booking ? (float) $booking->refund_fran_amount : (float) ($prov['refund_fran_amount'] ?? 0);
        $this->provRefundMiguelAmount = $booking ? (float) $booking->refund_miguel_amount : (float) ($prov['refund_miguel_amount'] ?? 0);
        $this->provManagementFee = $booking ? (float) $booking->management_fee : (float) ($prov['management_fee'] ?? 0);
        $this->provSocialSecurityFee = $booking ? (float) $booking->social_security_fee : (float) ($prov['social_security_fee'] ?? 0);
        $this->provTaxFee = $booking ? (float) $booking->tax_fee : (float) ($prov['tax_fee'] ?? 0);
        $this->provRegistrationFee = $booking ? (float) $booking->registration_fee : (float) ($prov['registration_fee'] ?? 0);
        $this->provSettlementNumber = (string) ($booking?->settlement_number ?? ($prov['settlement_number'] ?? ''));
        $this->provInvoiceNumber = (string) ($booking?->invoice_number ?? ($prov['invoice_number'] ?? ''));

        if ($this->provHasRefund && $this->provRefundAmount > 0 && $this->provRefundFranAmount <= 0 && $this->provRefundMiguelAmount <= 0 && $this->provRefundRecipient === '50_50') {
            $this->provRefundFranAmount = round($this->provRefundAmount / 2, 2);
            $this->provRefundMiguelAmount = round($this->provRefundAmount - $this->provRefundFranAmount, 2);
        }
    }

    /**
     * Recalcula la distribución de la devolución al modificar el importe devuelto.
     */
    public function updatedProvRefundAmount($value): void
    {
        $amt = max(0.0, (float) $value);
        if ($this->provRefundRecipient === '50_50') {
            $this->provRefundFranAmount = round($amt / 2, 2);
            $this->provRefundMiguelAmount = round($amt - $this->provRefundFranAmount, 2);
        } elseif ($this->provRefundRecipient === 'fran') {
            $this->provRefundFranAmount = $amt;
            $this->provRefundMiguelAmount = 0.0;
        } elseif ($this->provRefundRecipient === 'miguel') {
            $this->provRefundMiguelAmount = $amt;
            $this->provRefundFranAmount = 0.0;
        } elseif ($this->provRefundRecipient === 'joint' || $this->provRefundRecipient === 'ezequiel') {
            $this->provRefundFranAmount = 0.0;
            $this->provRefundMiguelAmount = 0.0;
        }
    }

    /**
     * Recalcula la distribución de la devolución al cambiar el destinatario.
     */
    public function updatedProvRefundRecipient($value): void
    {
        $amt = max(0.0, (float) $this->provRefundAmount);
        if ($value === '50_50') {
            $this->provRefundFranAmount = round($amt / 2, 2);
            $this->provRefundMiguelAmount = round($amt - $this->provRefundFranAmount, 2);
        } elseif ($value === 'fran') {
            $this->provRefundFranAmount = $amt;
            $this->provRefundMiguelAmount = 0.0;
        } elseif ($value === 'miguel') {
            $this->provRefundMiguelAmount = $amt;
            $this->provRefundFranAmount = 0.0;
        } elseif ($value === 'joint' || $value === 'ezequiel') {
            $this->provRefundFranAmount = 0.0;
            $this->provRefundMiguelAmount = 0.0;
        }
    }

    /**
     * Calcula la devolución neta restando retenciones del total de factura a Artístamente.
     */
    public function calculateRefundFromFees(): void
    {
        $cost = (float) $this->provTotalCost;
        $fees = (float) $this->provManagementFee
            + (float) $this->provSocialSecurityFee
            + (float) $this->provTaxFee
            + (float) $this->provRegistrationFee;
        $this->provRefundAmount = max(0.0, round($cost - $fees, 2));
        $this->updatedProvRefundAmount($this->provRefundAmount);
    }

    /**
     * Aplica un preset rápido para rellenar proveedor y concepto (Artístamente, Fotomatón, etc.).
     *
     * @param string $preset Clave del preset ('artistamente', 'photobooth', 'photobooth_vitoria').
     * @return void
     */
    public function applyProviderPreset(string $preset): void
    {
        if ($preset === 'artistamente') {
            $this->provKey = 'artistamente';
            $this->provName = 'Artístamente (Events Branch S.L.)';
            $this->provService = 'Facturación y Liquidación Cooperativa / Alta Artistas';
            $this->provDetectedQuotePrice = 0.0;
            $this->provClientPrice = 169.40;
            $this->provTotalCost = 169.40;
            $this->provDepositPaid = false;
            $this->provDepositAmount = 0.0;
            $this->provFinalPaid = true;
            $this->provFinalAmount = 169.40;
            $this->provFinalDate = now()->format('Y-m-d');
            $this->provFinalMethod = 'transfer';
            $this->provPaidBy = 'Fran';
            $this->provHasRefund = true;
            $this->provRefundAmount = 136.44;
            $this->provRefundRecipient = '50_50';
            $this->provRefundFranAmount = 68.22;
            $this->provRefundMiguelAmount = 68.22;
            $this->provManagementFee = 10.00;
            $this->provSocialSecurityFee = 39.31;
            $this->provTaxFee = 14.24;
            $this->provRegistrationFee = 0.00;
            $this->provInvoiceNumber = 'PEV-322971-1';
            $this->provSettlementNumber = 'EAM-322971';
            $this->provNotes = 'Factura pagada a Artístamente. Liquidación neta devuelta: 136,44 €.';
        } elseif ($preset === 'photobooth_vitoria') {
            $this->provKey = 'vitoria';
            $this->provName = 'Vitoria (FotomatonCheese)';
            $this->provService = 'Fotomatón / Plataforma 360º';
            $this->provHasRefund = false;
            $this->provRefundAmount = 0.0;
            $this->syncProviderFromQuote();
        } elseif ($preset === 'photobooth') {
            $this->provKey = 'logrono';
            $this->provName = 'Logroño';
            $this->provService = 'Fotomatón / Plataforma 360º';
            $this->provHasRefund = false;
            $this->provRefundAmount = 0.0;
            $this->syncProviderFromQuote();
        }
    }

    /**
     * Prepara el formulario del modal para añadir un nuevo proveedor adicional al evento.
     *
     * @return void
     */
    public function resetProviderModalForNewBooking(): void
    {
        $this->provBookingId = null;
        $this->provKey = 'artistamente';
        $this->provName = 'Artístamente (Events Branch S.L.)';
        $this->provService = 'Facturación y Liquidación Cooperativa / Alta Artistas';
        $this->provDetectedQuotePrice = 0.0;
        $this->provClientPrice = 169.40;
        $this->provDetectedItemsText = '';
        $this->provTotalCost = 169.40;
        $this->provDepositPaid = false;
        $this->provDepositAmount = 0.0;
        $this->provDepositDate = now()->format('Y-m-d');
        $this->provDepositMethod = 'transfer';
        $this->provFinalPaid = true;
        $this->provFinalAmount = 169.40;
        $this->provFinalDate = now()->format('Y-m-d');
        $this->provFinalMethod = 'transfer';
        $this->provPaidBy = 'Fran';
        $this->provHasRefund = true;
        $this->provRefundAmount = 136.44;
        $this->provRefundRecipient = '50_50';
        $this->provRefundFranAmount = 68.22;
        $this->provRefundMiguelAmount = 68.22;
        $this->provManagementFee = 10.00;
        $this->provSocialSecurityFee = 39.31;
        $this->provTaxFee = 14.24;
        $this->provRegistrationFee = 0.00;
        $this->provInvoiceNumber = 'PEV-322971-1';
        $this->provSettlementNumber = 'EAM-322971';
        $this->provNotes = 'Factura pagada a Artístamente. Liquidación neta devuelta: 136,44 €.';
    }

    /**
     * Sincroniza los servicios y precios del proveedor externo a partir de las líneas del presupuesto del evento.
     *
     * @return void
     */
    public function syncProviderFromQuote()
    {
        if (!$this->provEventId) {
            return;
        }
        $event = Event::with(['quotes.items', 'providerBookings'])->find($this->provEventId);
        if (!$event) {
            return;
        }
        $prov = $event->external_provider_summary;
        $detectedPrice = (float) ($prov['detected_client_price'] ?? 0);
        $this->provDetectedQuotePrice = $detectedPrice;
        if ($detectedPrice > 0) {
            $this->provClientPrice = $detectedPrice;
            $this->provTotalCost = $detectedPrice;
        }
        if (!empty($prov['detected_services'])) {
            $this->provService = $prov['detected_services'];
        }
        $this->provDetectedItemsText = (string) ($prov['detected_services_with_prices'] ?: ($prov['detected_services'] ?? ''));
    }

    /**
     * Calcula y rellena automáticamente el importe restante pendiente de pago al proveedor externo.
     *
     * @return void
     */
    public function fillProviderRemaining()
    {
        $dep = $this->provDepositPaid ? (float) $this->provDepositAmount : 0.0;
        $this->provFinalPaid = true;
        $this->provFinalAmount = max(0.0, round((float) $this->provTotalCost - $dep, 2));
        if (empty($this->provFinalDate)) {
            $this->provFinalDate = now()->format('Y-m-d');
        }
    }

    /**
     * Cierra el modal de gestión de reserva a proveedor externo.
     *
     * @return void
     */
    public function closeProviderModal()
    {
        $this->showProviderModal = false;
        $this->provBookingId = null;
    }

    /**
     * Valida y guarda los datos de reserva y pagos realizados al proveedor externo para el evento seleccionado.
     *
     * @return void
     */
    public function saveProviderBooking()
    {
        \App\Models\EventProviderBooking::ensureTableExists();

        $this->validate([
            'provEventId' => 'required|integer|exists:events,id',
            'provName' => 'required|string|max:150',
            'provService' => 'required|string|max:255',
            'provTotalCost' => 'required|numeric|min:0',
            'provDepositAmount' => 'nullable|numeric|min:0',
            'provFinalAmount' => 'nullable|numeric|min:0',
        ]);

        $event = Event::with('providerBookings')->find($this->provEventId);
        if (!$event) {
            $this->closeProviderModal();
            return;
        }

        $depAmt = $this->provDepositPaid ? round((float) $this->provDepositAmount, 2) : 0.0;
        $finAmt = $this->provFinalPaid ? round((float) $this->provFinalAmount, 2) : 0.0;

        \App\Models\EventProviderBooking::ensureTableExists();

        $data = [
            'provider_key' => $this->provKey ?: 'logrono',
            'provider_name' => trim($this->provName),
            'service_description' => trim($this->provService),
            'client_price' => round((float) ($this->provDetectedQuotePrice > 0 ? $this->provDetectedQuotePrice : $this->provTotalCost), 2),
            'provider_total_cost' => round((float) $this->provTotalCost, 2),
            'deposit_paid' => $this->provDepositPaid && $depAmt > 0,
            'deposit_amount' => $depAmt,
            'deposit_paid_at' => ($this->provDepositPaid && $depAmt > 0 && !empty($this->provDepositDate)) ? $this->provDepositDate : null,
            'deposit_payment_method' => ($this->provDepositPaid && $depAmt > 0) ? $this->provDepositMethod : null,
            'final_paid' => $this->provFinalPaid && $finAmt > 0,
            'final_amount' => $finAmt,
            'final_paid_at' => ($this->provFinalPaid && $finAmt > 0 && !empty($this->provFinalDate)) ? $this->provFinalDate : null,
            'final_payment_method' => ($this->provFinalPaid && $finAmt > 0) ? $this->provFinalMethod : null,
            'notes' => trim($this->provNotes) ?: null,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'paid_by')) {
            $data['paid_by'] = $this->provPaidBy ?: 'Fran';
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('event_provider_bookings', 'has_refund')) {
            $data['has_refund'] = (bool) $this->provHasRefund;
            $data['refund_amount'] = $this->provHasRefund ? round((float) $this->provRefundAmount, 2) : 0.0;
            $data['refund_recipient'] = $this->provRefundRecipient ?: '50_50';
            $data['refund_fran_amount'] = $this->provHasRefund ? round((float) $this->provRefundFranAmount, 2) : 0.0;
            $data['refund_miguel_amount'] = $this->provHasRefund ? round((float) $this->provRefundMiguelAmount, 2) : 0.0;
            $data['management_fee'] = round((float) $this->provManagementFee, 2);
            $data['social_security_fee'] = round((float) $this->provSocialSecurityFee, 2);
            $data['tax_fee'] = round((float) $this->provTaxFee, 2);
            $data['registration_fee'] = round((float) $this->provRegistrationFee, 2);
            $data['settlement_number'] = trim((string) $this->provSettlementNumber) ?: null;
            $data['invoice_number'] = trim((string) $this->provInvoiceNumber) ?: null;
        }

        $existing = $this->provBookingId
            ? $event->providerBookings()->find($this->provBookingId)
            : $event->providerBookings->first();

        if ($existing) {
            $existing->update($data);
        } else {
            $event->providerBookings()->create($data);
        }

        $this->closeProviderModal();
        session()->flash('message', "📸 Gasto y pagos de proveedor ({$data['provider_name']} - Pagado por {$data['paid_by']}) guardados en '{$event->name}'.");
    }

    /**
     * Elimina un registro de reserva a proveedor externo.
     *
     * @param int $bookingId Identificador de la reserva a proveedor.
     * @return void
     */
    public function deleteProviderBooking(int $bookingId)
    {
        \App\Models\EventProviderBooking::ensureTableExists();
        $booking = \App\Models\EventProviderBooking::find($bookingId);
        if ($booking) {
            $booking->delete();
            session()->flash('message', "Registro de reserva a proveedor eliminado.");
        }
    }

    // ==================== GASTOS Y COMPRAS DE MATERIAL ====================

    /**
     * Abre el modal para registrar una nueva compra de material o editar una existente.
     *
     * @param int|null $id Identificador del gasto si se edita.
     * @return void
     */
    public function openMaterialModal(?int $id = null)
    {
        \App\Models\MaterialExpense::ensureTableExists();
        $this->resetValidation();
        $this->materialExpenseId = $id;

        if ($id) {
            $mat = \App\Models\MaterialExpense::find($id);
            if ($mat) {
                $this->matConcept = $mat->concept;
                $this->matCategory = $mat->category ?: 'general';
                $this->matAmount = (float) $mat->amount;
                $this->matPurchaseDate = $mat->purchase_date ? $mat->purchase_date->format('Y-m-d') : now()->format('Y-m-d');
                $this->matPaidBy = $mat->paid_by ?: 'Fran';
                $this->matPaymentMethod = $mat->payment_method ?: 'card';
                $this->matSupplier = (string) ($mat->supplier ?? '');
                $this->matEventId = $mat->event_id;
                $this->matSeason = (string) ($mat->season ?? ($this->season !== 'all' ? $this->season : date('Y')));
                $this->matInvoiceNumber = (string) ($mat->invoice_number ?? '');
                $this->matNotes = (string) ($mat->notes ?? '');
            }
        } else {
            $this->matConcept = '';
            $this->matCategory = 'general';
            $this->matAmount = 0;
            $this->matPurchaseDate = now()->format('Y-m-d');
            $this->matPaidBy = 'Fran';
            $this->matPaymentMethod = 'card';
            $this->matSupplier = '';
            $this->matEventId = null;
            $this->matSeason = $this->season !== 'all' ? $this->season : date('Y');
            $this->matInvoiceNumber = '';
            $this->matNotes = '';
        }

        $this->showMaterialModal = true;
    }

    /**
     * Abre el modal de gastos pre-rellenado para imputar un uso o gasto a partir de un evento o cobro.
     * Útil por ejemplo para registrar la cena de navidad financiada con un cobro en efectivo.
     */
    public function openExpenseForEvent(int $eventId, float $amount = 0.0, string $concept = '', string $paidBy = 'Fondo Común (Efectivo / Caja)', string $category = 'cena_empresa')
    {
        $this->openMaterialModal(null);
        $this->matEventId = $eventId;
        if ($amount > 0) {
            $this->matAmount = round($amount, 2);
        }
        if (!empty($concept)) {
            $this->matConcept = $concept;
        } else {
            $ev = Event::find($eventId);
            $this->matConcept = 'Cena de Navidad de empresa' . ($ev ? ' (' . $ev->name . ')' : '');
        }
        $this->matPaidBy = $paidBy;
        $this->matCategory = $category;
        $this->matPaymentMethod = str_contains(mb_strtolower($paidBy), 'efectivo') ? 'cash' : 'transfer';
        $this->showMaterialModal = true;
    }

    /**
     * Cierra el modal de compra de material.
     *
     * @return void
     */
    public function closeMaterialModal()
    {
        $this->showMaterialModal = false;
        $this->materialExpenseId = null;
    }

    /**
     * Valida y guarda la compra de material o gasto deducible.
     *
     * @return void
     */
    public function saveMaterialExpense()
    {
        \App\Models\MaterialExpense::ensureTableExists();

        $this->validate([
            'matConcept' => 'required|string|max:255',
            'matAmount' => 'required|numeric|min:0.01',
            'matPurchaseDate' => 'required|date',
            'matPaidBy' => 'required|string|max:100',
            'matCategory' => 'required|string|max:50',
            'matPaymentMethod' => 'required|string|max:50',
            'matEventId' => 'nullable|integer|exists:events,id',
            'matSeason' => 'nullable|string|max:10',
        ]);

        $seasonVal = $this->matSeason;
        if (empty($seasonVal) && !empty($this->matPurchaseDate)) {
            $seasonVal = Carbon::parse($this->matPurchaseDate)->format('Y');
        }

        $data = [
            'event_id' => $this->matEventId ?: null,
            'season' => $seasonVal,
            'concept' => trim($this->matConcept),
            'category' => $this->matCategory,
            'amount' => round((float) $this->matAmount, 2),
            'purchase_date' => $this->matPurchaseDate,
            'paid_by' => trim($this->matPaidBy),
            'payment_method' => $this->matPaymentMethod,
            'supplier' => trim($this->matSupplier) ?: null,
            'invoice_number' => trim($this->matInvoiceNumber) ?: null,
            'notes' => trim($this->matNotes) ?: null,
        ];

        if ($this->materialExpenseId) {
            $mat = \App\Models\MaterialExpense::find($this->materialExpenseId);
            if ($mat) {
                $mat->update($data);
                session()->flash('message', "🛠️ Gasto de material '{$mat->concept}' actualizado correctamente.");
            }
        } else {
            \App\Models\MaterialExpense::create($data);
            session()->flash('message', "🛠️ Compra de material '{$data['concept']}' (" . number_format($data['amount'], 2, ',', '.') . " €) registrada con éxito.");
        }

        $this->closeMaterialModal();
    }

    /**
     * Elimina un registro de compra de material.
     *
     * @param int $id
     * @return void
     */
    public function deleteMaterialExpense(int $id)
    {
        $mat = \App\Models\MaterialExpense::find($id);
        if ($mat) {
            $mat->delete();
            session()->flash('message', "Gasto de material eliminado.");
        }
    }

    // ==================== CONFIGURACIÓN DE SOCIOS Y LIQUIDACIÓN ====================

    /**
     * Abre el modal para configurar los socios y sus porcentajes de liquidación.
     *
     * @return void
     */
    public function openPartnersModal()
    {
        $this->resetValidation();
        $this->partnersList = \App\Models\Setting::getSettlementPartners();
        $this->showPartnersModal = true;
    }

    /**
     * Cierra el modal de socios.
     *
     * @return void
     */
    public function closePartnersModal()
    {
        $this->showPartnersModal = false;
    }

    /**
     * Añade una fila para un nuevo socio en el formulario.
     *
     * @return void
     */
    public function addPartnerRow()
    {
        if (!is_array($this->partnersList)) {
            $this->partnersList = [];
        }
        $this->partnersList[] = ['name' => '', 'percentage' => 0.0];
        $this->partnersList = array_values($this->partnersList);
    }

    /**
     * Elimina una fila de socio del formulario.
     *
     * @param int $index
     * @return void
     */
    public function removePartnerRow(int $index)
    {
        if (isset($this->partnersList[$index])) {
            unset($this->partnersList[$index]);
            $this->partnersList = array_values($this->partnersList);
        }
    }

    /**
     * Aplica una plantilla rápida de socios y porcentajes.
     *
     * @param string $type
     * @return void
     */
    public function setPartnersPreset(string $type): void
    {
        if ($type === '3_partners') {
            $this->partnersList = [
                ['name' => 'Fran', 'percentage' => 33.33],
                ['name' => 'Miguel', 'percentage' => 33.33],
                ['name' => 'Ezequiel', 'percentage' => 33.34],
            ];
        } elseif ($type === '2_partners') {
            $this->partnersList = [
                ['name' => 'Fran', 'percentage' => 50.0],
                ['name' => 'Miguel', 'percentage' => 50.0],
            ];
        }
    }

    /**
     * Valida y guarda los socios y sus porcentajes para el reparto de beneficios.
     *
     * @return void
     */
    public function savePartnersSettings()
    {
        $totalPct = 0;
        $cleaned = [];

        foreach ($this->partnersList as $p) {
            $name = trim((string)($p['name'] ?? ''));
            $pct = round((float)($p['percentage'] ?? 0), 2);
            if ($name !== '') {
                $cleaned[] = ['name' => $name, 'percentage' => $pct];
                $totalPct += $pct;
            }
        }

        if (empty($cleaned)) {
            $this->addError('partnersList', 'Debe haber al menos un socio configurado.');
            return;
        }

        if (abs($totalPct - 100.0) > 0.5) {
            $this->addError('partnersList', "La suma de porcentajes debe ser exactamente 100% (actualmente suma {$totalPct}%).");
            return;
        }

        \App\Models\Setting::setSettlementPartners($cleaned);
        $this->closePartnersModal();
        session()->flash('message', "⚙️ Configuración de socios y porcentajes de liquidación guardada.");
    }

    /**
     * Obtiene el arreglo de filtros activos aplicados actualmente en el panel de facturación.
     *
     * @return array<string, mixed>
     */
    public function getActiveFiltersProperty(): array
    {
        return [
            'season' => $this->season,
            'year' => $this->year,
            'date_basis' => $this->dateBasis,
            'event_sort_order' => $this->eventSortOrder,
            'quarter' => $this->quarter,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'payment_status' => $this->paymentStatus,
            'payment_method' => $this->paymentMethod,
            'doc_type' => $this->docType,
            'brand' => $this->brand,
            'event_status' => $this->eventStatus,
            'search' => $this->search,
            'view_mode' => $this->viewMode,
        ];
    }

    /**
     * Alterna en 1 clic si una señal o pago recibido inicialmente por un socio ya fue traspasado a la cuenta común.
     */
    public function toggleDepositTransferredToJoint(int $eventId): void
    {
        $event = Event::with('invoices')->find($eventId);
        if (!$event) return;

        \App\Models\Event::ensureTokenColumnsExist();
        \App\Models\Invoice::ensureColumnsExist();

        $newVal = !((bool) ($event->deposit_transferred_to_joint ?? false));
        $dateVal = $newVal ? now()->format('Y-m-d') : null;

        $event->update([
            'deposit_transferred_to_joint' => $newVal,
            'deposit_transferred_at' => $dateVal,
        ]);

        $signalInv = $event->invoices->first(function ($inv) {
            $n = mb_strtolower((string) ($inv->notes ?? ''));
            return str_contains($n, 'señal') || str_contains($n, 'senal') || str_contains($n, 'reserva');
        });
        if ($signalInv && \Illuminate\Support\Facades\Schema::hasColumn('invoices', 'transferred_to_joint')) {
            $signalInv->update([
                'transferred_to_joint' => $newVal,
                'transferred_at' => $dateVal,
            ]);
        }

        session()->flash('message', $newVal
            ? "🏦 Señal de '{$event->name}' marcada como TRASPASADA a la Cuenta Común."
            : "ℹ️ Señal de '{$event->name}' marcada como en mano del socio (pendiente traspaso)."
        );
    }

    /**
     * Alterna si un recibo o factura cobrada fue traspasada a la cuenta común.
     */
    public function toggleInvoiceTransferredToJoint(int $invoiceId): void
    {
        $inv = Invoice::find($invoiceId);
        if (!$inv) return;

        \App\Models\Invoice::ensureColumnsExist();
        $newVal = !((bool) ($inv->transferred_to_joint ?? false));
        $inv->update([
            'transferred_to_joint' => $newVal,
            'transferred_at' => $newVal ? now()->format('Y-m-d') : null,
        ]);

        session()->flash('message', $newVal
            ? "🏦 Cobro '{$inv->invoice_number}' marcado como TRASPASADO a la Cuenta Común."
            : "ℹ️ Cobro '{$inv->invoice_number}' marcado como pendiente de traspasar."
        );
    }

    /**
     * Alterna en 1 clic si una compra de material adelantada por un socio ya fue reembolsada desde la cuenta común.
     */
    public function toggleMaterialReimbursed(int $expenseId): void
    {
        \App\Models\MaterialExpense::ensureTableExists();
        $expense = \App\Models\MaterialExpense::find($expenseId);
        if (!$expense) return;

        $newVal = !((bool) ($expense->is_reimbursed ?? false));
        $expense->update([
            'is_reimbursed' => $newVal,
            'reimbursed_at' => $newVal ? now()->format('Y-m-d') : null,
        ]);

        session()->flash('message', $newVal
            ? "✅ Gasto de material '{$expense->concept}' marcado como REEMBOLSADO a {$expense->paid_by}."
            : "⏳ Gasto de material '{$expense->concept}' marcado como PENDIENTE de retorno a {$expense->paid_by}."
        );
    }

    /**
     * Alterna en 1 clic si un pago a proveedor adelantado por un socio (ej. Artístamente) ya fue reembolsado desde la cuenta común.
     */
    public function toggleProviderReimbursed(int $bookingId): void
    {
        \App\Models\EventProviderBooking::ensureTableExists();
        $booking = \App\Models\EventProviderBooking::find($bookingId);
        if (!$booking) return;

        $newVal = !((bool) ($booking->is_reimbursed ?? false));
        $booking->update([
            'is_reimbursed' => $newVal,
            'reimbursed_at' => $newVal ? now()->format('Y-m-d') : null,
        ]);

        session()->flash('message', $newVal
            ? "✅ Pago a proveedor '{$booking->provider_name}' marcado como REEMBOLSADO a {$booking->paid_by}."
            : "⏳ Pago a proveedor '{$booking->provider_name}' marcado como PENDIENTE de retorno a {$booking->paid_by}."
        );
    }

    /**
     * Marca el reparto de un evento como liquidado y transferido a los socios.
     */
    public function settleEventPartners(int $eventId, ?string $notes = null): void
    {
        \App\Models\Event::ensureTokenColumnsExist();
        $event = Event::find($eventId);
        if (!$event) return;

        $event->update([
            'settlement_status' => 'settled',
            'settlement_paid_at' => now()->format('Y-m-d'),
            'settlement_notes' => $notes ?: null,
        ]);

        session()->flash('message', "✅ Reparto del evento '{$event->name}' marcado como LIQUIDADO a socios.");
    }

    /**
     * Revierte el estado de liquidación de un evento a pendiente.
     */
    public function unsettleEventPartners(int $eventId): void
    {
        \App\Models\Event::ensureTokenColumnsExist();
        $event = Event::find($eventId);
        if (!$event) return;

        $event->update([
            'settlement_status' => 'pending',
            'settlement_paid_at' => null,
        ]);

        session()->flash('message', "ℹ️ Reparto del evento '{$event->name}' revertido a PENDIENTE de liquidar.");
    }

    /**
     * Abre el modal para personalizar el reparto de ingresos/beneficio de un evento concreto.
     */
    public function openEventSplitModal(int $eventId): void
    {
        \App\Models\Event::ensureTokenColumnsExist();
        $event = Event::with(['quotes.items', 'assistants', 'dj'])->find($eventId);
        if (!$event) return;

        $this->splitEventId = $event->id;
        $this->splitEventName = $event->name;

        // Obtener datos calculados del evento
        $report = BillingReportService::buildReportData(['event_id' => $event->id]);
        $evItem = collect($report['filtered_events'] ?? [])->firstWhere('event.id', $event->id);

        $this->splitEventTotal = (float)($evItem['settlement']['total_collected'] ?? 0.0);
        $this->splitEventCosts = (float)($evItem['settlement']['total_costs'] ?? 0.0);
        $this->splitEventNet = (float)($evItem['settlement']['net_profit'] ?? 0.0);

        $existingSplit = $event->settlement_custom_split;
        if (is_string($existingSplit)) {
            $existingSplit = json_decode($existingSplit, true);
        }

        if (!empty($existingSplit['partners'])) {
            $this->splitMode = $existingSplit['mode'] ?? 'custom_amounts';
            $this->splitPartners = (array) $existingSplit['partners'];
            $this->splitNotes = (string)($existingSplit['notes'] ?? '');
        } else {
            // Sugerir reparto inteligente por servicios del evento
            $suggested = $event->getEventSuggestedStaffSplit();
            $this->splitMode = $suggested['mode'] ?? 'custom_amounts';
            $this->splitPartners = (array) ($suggested['partners'] ?? []);
            $this->splitNotes = (string)($suggested['notes'] ?? '');
        }

        $this->showEventSplitModal = true;
    }

    /**
     * Cierra el modal de personalización de reparto.
     */
    public function closeEventSplitModal(): void
    {
        $this->showEventSplitModal = false;
        $this->splitEventId = null;
        $this->splitPartners = [];
    }

    /**
     * Aplica una plantilla de reparto rápido en el modal.
     */
    public function applySplitPreset(string $preset): void
    {
        if (!$this->splitEventId) return;
        $event = Event::with(['quotes.items', 'assistants', 'dj'])->find($this->splitEventId);
        if (!$event) return;

        if ($preset === 'by_services') {
            $suggested = $event->getEventSuggestedStaffSplit();
            $this->splitMode = 'custom_amounts';
            $this->splitPartners = (array) $suggested['partners'];
            $this->splitNotes = $suggested['notes'] ?? 'Reparto por servicios presupuestados';
        } elseif ($preset === 'staff_50_50') {
            $djName = $event->dj?->name ?: 'Miguel';
            $asName = $event->assistants->first()?->name ?: 'Fran';
            $half = round($this->splitEventTotal / 2, 2);
            $this->splitMode = 'custom_amounts';
            $this->splitPartners = [
                ['name' => $djName, 'amount' => $half, 'percentage' => 50.0, 'role' => 'DJ Titular', 'is_settled' => true],
                ['name' => $asName, 'amount' => $half, 'percentage' => 50.0, 'role' => 'Asistente / Equipo', 'is_settled' => true],
            ];
            $this->splitNotes = "Reparto 50% / 50% entre {$djName} y {$asName}";
        } elseif ($preset === 'global_partners') {
            $partners = \App\Models\Setting::getSettlementPartners();
            $this->splitMode = 'percentages';
            $this->splitPartners = array_map(function($p) {
                return [
                    'name' => $p['name'],
                    'amount' => round($this->splitEventNet * (((float)$p['percentage']) / 100), 2),
                    'percentage' => (float)$p['percentage'],
                    'role' => 'Socio (' . $p['percentage'] . '%)',
                    'is_settled' => false,
                ];
            }, $partners);
            $this->splitNotes = 'Reparto estándar entre los 3 socios';
        }
    }

    /**
     * Añade una fila de participante al reparto del evento.
     */
    public function addPartnerToSplit(): void
    {
        $this->splitPartners[] = [
            'name' => '',
            'amount' => 0.0,
            'percentage' => 0.0,
            'role' => '',
            'is_settled' => false,
        ];
    }

    /**
     * Elimina una fila del reparto del evento.
     */
    public function removePartnerFromSplit(int $index): void
    {
        unset($this->splitPartners[$index]);
        $this->splitPartners = array_values($this->splitPartners);
    }

    /**
     * Alterna en el modal si un socio ya tiene cobrado su importe.
     */
    public function togglePartnerSettledInSplit(int $index): void
    {
        if (isset($this->splitPartners[$index])) {
            $this->splitPartners[$index]['is_settled'] = !($this->splitPartners[$index]['is_settled'] ?? false);
        }
    }

    /**
     * Guarda la personalización de reparto del evento.
     */
    public function saveEventSplit(): void
    {
        if (!$this->splitEventId) return;
        $event = Event::find($this->splitEventId);
        if (!$event) return;

        \App\Models\Event::ensureTokenColumnsExist();

        $cleanPartners = [];
        foreach ($this->splitPartners as $p) {
            $name = trim((string)($p['name'] ?? ''));
            if ($name === '') continue;
            $amt = round((float)($p['amount'] ?? 0.0), 2);
            $pct = (float)($p['percentage'] ?? 0.0);
            $role = trim((string)($p['role'] ?? ''));
            $isSettled = !empty($p['is_settled']);

            $cleanPartners[] = [
                'name' => $name,
                'amount' => $amt,
                'percentage' => $pct,
                'role' => $role,
                'is_settled' => $isSettled,
            ];
        }

        $event->update([
            'settlement_custom_split' => [
                'mode' => $this->splitMode,
                'partners' => $cleanPartners,
                'notes' => trim($this->splitNotes),
                'updated_at' => now()->toIso8601String(),
            ],
        ]);

        $this->showEventSplitModal = false;
        session()->flash('message', "✅ Reparto personalizado guardado para '{$event->name}'.");
    }

    /**
     * Restablece el evento para usar el reparto general de socios.
     */
    public function resetEventSplitToDefault(): void
    {
        if (!$this->splitEventId) return;
        $event = Event::find($this->splitEventId);
        if (!$event) return;

        $event->update(['settlement_custom_split' => null]);
        $this->showEventSplitModal = false;
        session()->flash('message', "ℹ️ Reparto de '{$event->name}' restablecido al reparto estándar de socios.");
    }

    /**
     * Alterna en 1 clic desde la tarjeta del evento si un socio ya cobró o tiene liquidada su parte.
     */
    public function togglePartnerSettledDirect(int $eventId, string $partnerName): void
    {
        \App\Models\Event::ensureTokenColumnsExist();
        $event = Event::find($eventId);
        if (!$event) return;

        $split = $event->settlement_custom_split;
        if (is_string($split)) {
            $split = json_decode($split, true);
        }

        if (empty($split['partners'])) {
            $suggested = $event->getEventSuggestedStaffSplit();
            $split = [
                'mode' => $suggested['mode'] ?? 'custom_amounts',
                'partners' => $suggested['partners'] ?? [],
                'notes' => $suggested['notes'] ?? '',
            ];
        }

        $found = false;
        foreach ($split['partners'] as &$p) {
            if (strcasecmp(trim((string)($p['name'] ?? '')), trim($partnerName)) === 0) {
                $p['is_settled'] = !($p['is_settled'] ?? false);
                $found = true;
                break;
            }
        }

        if ($found) {
            $event->update(['settlement_custom_split' => $split]);
            session()->flash('message', "🔄 Estado de cobro de {$partnerName} actualizado.");
        }
    }

    /**
     * Genera los datos del informe de facturación y renderiza la vista correspondiente.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        $report = BillingReportService::buildReportData($this->activeFilters);

        // Obtener años y temporadas disponibles dinámicamente
        $eventYearsRaw = Event::whereNotNull('event_date')
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->selectRaw('YEAR(event_date) as y, COUNT(*) as cnt')
            ->groupBy('y')
            ->pluck('cnt', 'y')
            ->toArray();

        $eventYears = array_map('intval', array_keys($eventYearsRaw));
        $invoiceYears = Invoice::whereNotNull('issue_date')
            ->selectRaw('YEAR(issue_date) as y')
            ->distinct()
            ->pluck('y')
            ->map(fn($y) => (int) $y)
            ->toArray();
        $currentYear = (int) date('Y');
        $availableYears = array_unique(array_merge($eventYears, $invoiceYears, [$currentYear - 1, $currentYear, $currentYear + 1, $currentYear + 2]));
        rsort($availableYears);

        $availableSeasons = array_unique(array_merge($eventYears, [$currentYear, $currentYear + 1, $currentYear + 2]));
        sort($availableSeasons);

        $seasonCounts = [];
        foreach ($availableSeasons as $sy) {
            $seasonCounts[$sy] = (int) ($eventYearsRaw[$sy] ?? 0);
        }

        $exportQuery = http_build_query(array_filter($this->activeFilters, fn($v) => $v !== '' && $v !== null));

        $allEventsList = Event::orderByDesc('event_date')
            ->select('id', 'name', 'event_date')
            ->get();

        return view('livewire.admin.billing-manager', [
            'eventsRows' => $report['events_rows'],
            'ledgerRows' => $report['ledger_rows'],
            'providerRows' => $report['provider_rows'] ?? [],
            'materialRows' => $report['material_rows'] ?? [],
            'jointAccountLedger' => $report['joint_account_ledger'] ?? [],
            'kpis' => $report['kpis'],
            'partners' => \App\Models\Setting::getSettlementPartners(),
            'availableYears' => $availableYears,
            'availableSeasons' => $availableSeasons,
            'seasonCounts' => $seasonCounts,
            'exportQuery' => $exportQuery,
            'allEventsList' => $allEventsList,
        ])->layout('components.layouts.app', ['header' => 'Facturación y Control de Ingresos']);
    }
}
