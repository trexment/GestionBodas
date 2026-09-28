<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Contract;
use App\Models\Event;
use App\Models\Setting;
use App\Services\ContractTemplateService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function downloadInvoice(Invoice $invoice)
    {
        $invoice->load('event.client');

        $data = [
            'invoice' => $invoice,
            'event' => $invoice->event,
            'client' => $invoice->event->client,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        return $pdf->download('factura_' . $invoice->invoice_number . '.pdf');
    }

    public function downloadQuote(Quote $quote)
    {
        $quote->load('event.client');

        $data = [
            'quote' => $quote,
            'event' => $quote->event,
            'client' => $quote->event->client,
        ];

        $pdf = Pdf::loadView('pdf.quote', $data);
        return $pdf->download('presupuesto_evento_' . $quote->event->id . '.pdf');
    }
    
    public function downloadContract(Contract $contract)
    {
        $contract->load('event.client', 'event.quotes');
        
        $renderedData = ContractTemplateService::renderContract($contract);
        $renderedData['company_logo'] = Setting::get('company_logo');

        $pdf = Pdf::loadView('pdf.contract', $renderedData);
        return $pdf->download('contrato_evento_' . ($contract->event->id ?? $contract->id) . '.pdf');
    }

    public function downloadPackingList(Event $event)
    {
        $event->load(['client', 'dj', 'assistant', 'equipment']);

        $equipmentGrouped = $event->equipment->groupBy('category');

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'assistant' => $event->assistant,
            'equipmentGrouped' => $equipmentGrouped,
        ];

        $pdf = Pdf::loadView('pdf.packing-list', $data);
        return $pdf->download('hoja_de_carga_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }

    public function downloadMusicEscaleta(Event $event)
    {
        $event->load(['client', 'dj', 'assistant', 'musicRequests']);

        $requestsGrouped = $event->musicRequests->groupBy('category');

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'assistant' => $event->assistant,
            'requestsGrouped' => $requestsGrouped,
        ];

        $pdf = Pdf::loadView('pdf.music-escaleta', $data);
        return $pdf->download('escaleta_musical_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }
}
