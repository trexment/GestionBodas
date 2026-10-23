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
    /**
     * Verify that the requester is an authorized staff member or possesses a valid event/contract token.
     */
    protected function authorizePdfAccess(?Event $event = null, ?string $contractToken = null): void
    {
        // 1. Authenticated staff (admin, dj, assistant) or client owner
        if (auth()->check()) {
            $user = auth()->user();
            if ($user->role === 'admin') {
                return;
            }
            if ($event) {
                $isAssignedAssistant = false;
                try {
                    if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                        $isAssignedAssistant = $event->assistants()->where('users.id', $user->id)->exists();
                    }
                } catch (\Throwable $e) {}

                if ($event->dj_id == $user->id || $event->assistant_id == $user->id || $isAssignedAssistant || $event->client_id == $user->id) {
                    return;
                }
            }
        }

        // 2. Secret Token Authentication (client portal token, contract token or Live DJ Mode staff token).
        // The guest QR token (guest_token) is intentionally NOT accepted here.
        $providedToken = (string) (request()->query('token') ?: request()->header('X-Live-Token') ?: session('live_event_token'));
        if ($providedToken !== '') {
            if ($contractToken && hash_equals((string) $contractToken, $providedToken)) {
                return;
            }
            if ($event && $event->matchesClientOrStaffToken($providedToken)) {
                return;
            }
            if ($event && $event->contracts()->where('token', $providedToken)->exists()) {
                return;
            }
        }

        abort(403, 'Acceso denegado: Se requiere autenticación o token de acceso válido.');
    }

    public function downloadInvoice(Invoice $invoice)
    {
        $this->authorizePdfAccess($invoice->event);

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
        $this->authorizePdfAccess($quote->event);

        $quote->load(['event.client', 'items']);

        $isSinglePage = request()->boolean('single') || request('mode') === 'single';

        $data = [
            'quote' => $quote,
            'event' => $quote->event,
            'client' => $quote->event ? $quote->event->client : null,
            'isSinglePage' => $isSinglePage,
        ];

        $eventName = $quote->event ? $quote->event->name : 'Evento';
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $eventName);
        $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
        $prefix = $isSinglePage ? 'Presupuesto_1Pag_' : 'Dossier_Presupuesto_';
        $filename = $prefix . ($safeName ?: 'Evento_' . $quote->id) . '.pdf';

        $pdf = Pdf::loadView('pdf.quote', $data);
        return $pdf->download($filename);
    }
    
    public function downloadContract(Contract $contract)
    {
        $this->authorizePdfAccess($contract->event, $contract->token);

        $contract->load('event.client', 'event.quotes');
        
        $renderedData = ContractTemplateService::renderContract($contract);
        $renderedData['company_logo'] = Setting::get('company_logo');

        $eventName = $contract->event ? $contract->event->name : 'Evento';
        $safeName = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $eventName);
        $safeName = trim(preg_replace('/\s+/', ' ', $safeName));
        $filename = 'Contrato ' . ($safeName ?: 'Evento_' . $contract->id) . '.pdf';

        $pdf = Pdf::loadView('pdf.contract', $renderedData);
        return $pdf->download($filename);
    }

    public function downloadPackingList(Event $event)
    {
        $this->authorizePdfAccess($event);

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
        $this->authorizePdfAccess($event);

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
        $this->authorizePdfAccess($event);

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
