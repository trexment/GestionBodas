<?php

namespace App\Livewire\Admin;

use App\Models\Event;
use App\Models\Invoice;
use App\Services\BillingReportService;
use Carbon\Carbon;
use Livewire\Component;

class BillingManager extends Component
{
    // Vista activa: 'events' (Control de pagos por evento) o 'ledger' (Libro cronológico de cobros/facturas)
    public string $viewMode = 'events';

    // Filtros
    public string $year = 'all';
    public string $dateBasis = 'payment_date'; // 'payment_date' | 'event_date'
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
    public bool $quickGenerateReceipt = true;

    // Modal para Registrar / Editar Reserva y Pagos a Proveedor (Fotomatón / Plataforma 360º)
    public bool $showProviderModal = false;
    public ?int $provEventId = null;
    public string $provEventName = '';
    public string $provKey = 'logrono';
    public string $provName = 'Logroño';
    public string $provService = 'Fotomatón + Plataforma 360º';
    public $provClientPrice = 0;
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
    public string $provNotes = '';

    public function mount()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }

        $this->quickDate = now()->format('Y-m-d');
    }

    public function resetFilters()
    {
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

    public function filterByMethod(string $method)
    {
        $this->paymentMethod = ($this->paymentMethod === $method) ? 'all' : $method;
    }

    public function filterByPaymentStatus(string $status)
    {
        $this->paymentStatus = $status;
    }

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
            $this->quickNotes = (string) ($event->deposit_notes ?? '');
            $this->quickGenerateReceipt = true;
        } else {
            $this->quickAmount = $fb['pending_amount'] > 0 ? $fb['pending_amount'] : $fb['final_paid_amount'];
            $this->quickMethod = $fb['final_method'] ?: 'cash';
            $this->quickDate = $fb['final_paid_date']
                ? Carbon::parse($fb['final_paid_date'])->format('Y-m-d')
                : now()->format('Y-m-d');
            $this->quickNotes = '';
            $this->quickGenerateReceipt = true;
        }

        $this->showQuickPaymentModal = true;
    }

    public function closeQuickPaymentModal()
    {
        $this->showQuickPaymentModal = false;
        $this->quickEventId = null;
    }

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

        if ($this->quickPaymentType === 'deposit') {
            $hasPaid = $amount > 0;
            $newStatus = in_array($event->status, ['completed'], true) ? 'completed' : ($hasPaid ? 'confirmed' : $event->status);

            $event->update([
                'status' => $newStatus,
                'deposit_paid' => $hasPaid,
                'deposit_paid_amount' => $amount,
                'deposit_payment_method' => $this->quickMethod,
                'deposit_paid_at' => $this->quickDate,
                'deposit_notes' => $this->quickNotes,
            ]);

            if ($this->quickGenerateReceipt && $hasPaid) {
                $noteText = "Señal de reserva cobrada vía {$methodLabel} el " . Carbon::parse($this->quickDate)->format('d/m/Y') . ($this->quickNotes ? " ({$this->quickNotes})" : '');

                $existingSignalInv = $event->invoices->first(function ($inv) {
                    $n = mb_strtolower((string) ($inv->notes ?? ''));
                    return str_contains($n, 'señal') || str_contains($n, 'senal');
                });

                if ($existingSignalInv) {
                    $existingSignalInv->update([
                        'amount' => $amount,
                        'total' => $amount,
                        'issue_date' => $this->quickDate,
                        'status' => 'paid',
                        'payment_method' => $this->quickMethod,
                        'notes' => $noteText,
                    ]);
                } else {
                    $nextNum = Invoice::nextNumber('recibo');
                    $event->invoices()->create([
                        'quote_id' => $latestQuote?->id,
                        'invoice_number' => $nextNum,
                        'type' => 'recibo',
                        'amount' => $amount,
                        'tax' => 0.00,
                        'tax_rate' => 0.00,
                        'total' => $amount,
                        'issue_date' => $this->quickDate,
                        'status' => 'paid',
                        'payment_method' => $this->quickMethod,
                        'notes' => $noteText,
                    ]);
                }
            }

            session()->flash('message', "✅ Señal de " . number_format($amount, 2, ',', '.') . " € ({$methodLabel}) guardada en '{$event->name}'.");
        } else {
            if ($amount > 0) {
                $nextNum = Invoice::nextNumber('recibo');
                $noteText = "Liquidación final del evento cobrada vía {$methodLabel} el " . Carbon::parse($this->quickDate)->format('d/m/Y') . ($this->quickNotes ? " ({$this->quickNotes})" : '');

                $event->invoices()->create([
                    'quote_id' => $latestQuote?->id,
                    'invoice_number' => $nextNum,
                    'type' => 'recibo',
                    'amount' => $amount,
                    'tax' => 0.00,
                    'tax_rate' => 0.00,
                    'total' => $amount,
                    'issue_date' => $this->quickDate,
                    'status' => 'paid',
                    'payment_method' => $this->quickMethod,
                    'notes' => $noteText,
                ]);
            }

            session()->flash('message', "✅ Cobro final de " . number_format($amount, 2, ',', '.') . " € ({$methodLabel}) registrado en '{$event->name}'.");
        }

        $this->closeQuickPaymentModal();
    }

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

    public function openProviderModal(?int $eventId = null, string $focusStep = 'deposit')
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

        $this->loadEventIntoProviderModal($event, $focusStep);
        $this->showProviderModal = true;
    }

    public function updatedProvEventId($value)
    {
        if (!$value) {
            return;
        }
        $event = Event::with(['quotes.items', 'providerBookings'])->find((int) $value);
        if ($event) {
            $this->loadEventIntoProviderModal($event, 'deposit');
        }
    }

    public function updatedProvKey($value)
    {
        $catalog = \App\Models\Setting::getPhotoboothProviders();
        if (isset($catalog[$value])) {
            $this->provName = (string) ($catalog[$value]['name'] ?? ucfirst($value));
        } elseif ($value === 'vitoria') {
            $this->provName = 'Vitoria (FotomatonCheese)';
        } elseif ($value === 'logrono') {
            $this->provName = 'Logroño';
        }
    }

    protected function loadEventIntoProviderModal(Event $event, string $focusStep = 'deposit'): void
    {
        $prov = $event->external_provider_summary;

        $this->provEventId = $event->id;
        $this->provEventName = $event->name;
        $this->provKey = (string) ($prov['provider_key'] ?: 'logrono');
        $this->provName = (string) ($prov['provider_name'] ?: 'Logroño');
        $this->provService = (string) ($prov['service_description'] ?: 'Fotomatón / Plataforma 360º');
        $this->provClientPrice = (float) ($prov['client_price'] ?? 0);
        $this->provDetectedItemsText = (string) ($prov['detected_services_with_prices'] ?: ($prov['detected_services'] ?? ''));
        $this->provTotalCost = (float) ($prov['provider_total_cost'] > 0 ? $prov['provider_total_cost'] : ($prov['client_price'] > 0 ? $prov['client_price'] : 0));

        $this->provDepositPaid = $focusStep === 'deposit' ? true : (bool) $prov['deposit_paid'];
        $this->provDepositAmount = $prov['deposit_amount'] > 0 ? (float) $prov['deposit_amount'] : (float) ($prov['suggested_deposit'] ?: 100);
        $this->provDepositDate = $prov['deposit_date'] ? Carbon::parse($prov['deposit_date'])->format('Y-m-d') : now()->format('Y-m-d');
        $this->provDepositMethod = (string) ($prov['deposit_method'] ?: 'bizum');

        if ($focusStep === 'final') {
            $this->provFinalPaid = true;
            $alreadyDep = $this->provDepositPaid ? (float) $this->provDepositAmount : 0.0;
            $rem = max(0.0, round((float) $this->provTotalCost - $alreadyDep, 2));
            $this->provFinalAmount = $prov['final_amount'] > 0 ? (float) $prov['final_amount'] : $rem;
            $this->provFinalDate = $prov['final_date'] ? Carbon::parse($prov['final_date'])->format('Y-m-d') : now()->format('Y-m-d');
            $this->provFinalMethod = (string) ($prov['final_method'] ?: 'transfer');
        } else {
            $this->provFinalPaid = (bool) $prov['final_paid'];
            $this->provFinalAmount = (float) ($prov['final_amount'] ?? 0);
            $this->provFinalDate = $prov['final_date'] ? Carbon::parse($prov['final_date'])->format('Y-m-d') : now()->format('Y-m-d');
            $this->provFinalMethod = (string) ($prov['final_method'] ?: 'transfer');
        }

        $this->provNotes = (string) ($prov['notes'] ?? '');
    }

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
        $detectedPrice = (float) ($prov['detected_client_price'] ?: ($prov['client_price'] ?? 0));
        if ($detectedPrice > 0) {
            $this->provClientPrice = $detectedPrice;
            $this->provTotalCost = $detectedPrice;
        }
        if (!empty($prov['detected_services'])) {
            $this->provService = $prov['detected_services'];
        }
        $this->provDetectedItemsText = (string) ($prov['detected_services_with_prices'] ?: ($prov['detected_services'] ?? ''));
    }

    public function fillProviderRemaining()
    {
        $dep = $this->provDepositPaid ? (float) $this->provDepositAmount : 0.0;
        $this->provFinalPaid = true;
        $this->provFinalAmount = max(0.0, round((float) $this->provTotalCost - $dep, 2));
        if (empty($this->provFinalDate)) {
            $this->provFinalDate = now()->format('Y-m-d');
        }
    }

    public function closeProviderModal()
    {
        $this->showProviderModal = false;
    }

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

        $data = [
            'provider_key' => $this->provKey ?: 'logrono',
            'provider_name' => trim($this->provName),
            'service_description' => trim($this->provService),
            'client_price' => round((float) ($this->provClientPrice > 0 ? $this->provClientPrice : $this->provTotalCost), 2),
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

        $existing = $event->providerBookings->first();
        if ($existing) {
            $existing->update($data);
        } else {
            $event->providerBookings()->create($data);
        }

        $this->closeProviderModal();
        session()->flash('message', "📸 Reserva y pagos de Fotomatón / 360º actualizados en '{$event->name}'.");
    }

    public function deleteProviderBooking(int $bookingId)
    {
        \App\Models\EventProviderBooking::ensureTableExists();
        $booking = \App\Models\EventProviderBooking::find($bookingId);
        if ($booking) {
            $booking->delete();
            session()->flash('message', "Registro de reserva a proveedor eliminado.");
        }
    }

    public function getActiveFiltersProperty(): array
    {
        return [
            'year' => $this->year,
            'date_basis' => $this->dateBasis,
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

    public function render()
    {
        $report = BillingReportService::buildReportData($this->activeFilters);

        // Obtener años disponibles dinámicamente
        $eventYears = Event::whereNotNull('event_date')
            ->selectRaw('YEAR(event_date) as y')
            ->distinct()
            ->pluck('y')
            ->toArray();
        $invoiceYears = Invoice::whereNotNull('issue_date')
            ->selectRaw('YEAR(issue_date) as y')
            ->distinct()
            ->pluck('y')
            ->toArray();
        $currentYear = (int) date('Y');
        $availableYears = array_unique(array_merge($eventYears, $invoiceYears, [$currentYear - 1, $currentYear, $currentYear + 1, $currentYear + 2]));
        rsort($availableYears);

        $exportQuery = http_build_query(array_filter($this->activeFilters, fn($v) => $v !== '' && $v !== null));

        $allEventsList = Event::orderByDesc('event_date')
            ->select('id', 'name', 'event_date')
            ->get();

        return view('livewire.admin.billing-manager', [
            'eventsRows' => $report['events_rows'],
            'ledgerRows' => $report['ledger_rows'],
            'providerRows' => $report['provider_rows'] ?? [],
            'kpis' => $report['kpis'],
            'availableYears' => $availableYears,
            'exportQuery' => $exportQuery,
            'allEventsList' => $allEventsList,
        ])->layout('components.layouts.app', ['header' => 'Facturación y Control de Ingresos']);
    }
}
