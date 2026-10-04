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
        $invoice->load(['event.client', 'quote.items']);

        $brandKey = $invoice->event ? ($invoice->event->brand_clean ?? null) : null;
        $brand = Setting::getBrandInfo($brandKey);

        $company = [
            'name' => $brand['name'] ?: Setting::getCompanyName('Núñez and Son'),
            'subtitle' => $brand['subtitle'] ?: Setting::get('company_subtitle', 'Sound in Motion'),
            'cif' => Setting::get('company_cif', '78902362B'),
            'phone' => $brand['phone'] ?: Setting::get('company_phone', '+34 622 62 47 90'),
            'phone_2' => $brand['phone_2'] ?: Setting::get('company_phone_2', ''),
            'email' => $brand['email'] ?: Setting::get('company_email', 'info@nunezandson.com'),
            'website' => $brand['website'] ?: Setting::get('company_website', 'nunezandson.com'),
            'address' => Setting::get('company_address', 'Calle Principal s/n'),
            'city' => Setting::get('company_city', 'Logroño'),
            'iban' => Setting::get('company_iban', 'ES39 3035 0241 13 2411043571'),
            'bizum' => Setting::get('company_bizum', '622634790'),
            'logo' => $brand['logo_path'] ?: Setting::getLogoPathForPdf(),
        ];

        $data = [
            'invoice' => $invoice,
            'event' => $invoice->event,
            'client' => $invoice->event ? $invoice->event->client : null,
            'quote' => $invoice->quote,
            'company' => $company,
        ];

        $pdf = Pdf::loadView('pdf.invoice', $data);
        $filenamePrefix = $invoice->isReceipt() ? 'recibo_' : 'factura_';
        return $pdf->download($filenamePrefix . \Illuminate\Support\Str::slug($invoice->invoice_number) . '.pdf');
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

    public function downloadDjHistory(Event $event)
    {
        $event->load(['client', 'dj', 'djHistories.matchedRequest']);

        $data = [
            'event' => $event,
            'client' => $event->client,
            'dj' => $event->dj,
            'histories' => $event->djHistories,
        ];

        $pdf = Pdf::loadView('pdf.dj-session-history', $data);
        return $pdf->download('tracklist_sesion_' . \Illuminate\Support\Str::slug($event->name) . '.pdf');
    }
}
