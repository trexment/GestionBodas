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

        return view('livewire.admin.billing-manager', [
            'eventsRows' => $report['events_rows'],
            'ledgerRows' => $report['ledger_rows'],
            'kpis' => $report['kpis'],
            'availableYears' => $availableYears,
            'exportQuery' => $exportQuery,
        ])->layout('components.layouts.app', ['header' => 'Facturación y Control de Ingresos']);
    }
}
