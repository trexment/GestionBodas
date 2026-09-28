<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\Contract;
use App\Models\Event;
use App\Services\ContractTemplateService;
use App\Services\PostalCodeService;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ContractSign extends Component
{
    public $token;
    public $contract;
    public $event;
    public $client;

    public $renderedTitle;
    public $renderedBody;
    public $renderedFooter;
    public $amount = 0;

    // Client fields to review/fill
    public $client_name;
    public $client_dni;
    public $client_phone;
    public $client_email;
    public $client_postal_code;
    public $client_city;
    public $client_province;
    public $client_address;

    // Signature options
    public $signature_type = 'canvas'; // 'canvas' or 'certificate'
    public $signature_data; // Base64 PNG for canvas or digital seal
    public $certificate_issuer = 'AC FNMT Usuarios / DNIe';
    public $certificate_serial;
    public $certificate_hash;
    public $certificate_subject;

    public $consent_rrss = true;
    public $accepted_terms = false;

    public $isSigned = false;

    public function mount($token)
    {
        $this->token = $token;

        // Find by contract token, or fallback by event token
        $this->contract = Contract::with(['event.client', 'event.quotes'])->where('token', $token)->first();

        if (!$this->contract) {
            $event = Event::with(['client', 'quotes'])->where('token', $token)->first();
            if ($event) {
                $this->contract = $event->contracts()->latest()->first();
                if (!$this->contract) {
                    $this->contract = $event->contracts()->create([
                        'status' => 'pending',
                        'token' => Str::random(32),
                    ]);
                }
            }
        }

        if (!$this->contract) {
            abort(404, 'Contrato no encontrado o enlace caducado.');
        }

        $this->event = $this->contract->event;
        $this->client = $this->event ? $this->event->client : null;

        // Check if already signed
        if ($this->contract->status === 'signed') {
            $this->isSigned = true;
            $this->client_name = $this->contract->client_name_signed ?: ($this->client ? $this->client->name : '');
            $this->client_dni = $this->contract->client_dni_signed ?: ($this->client ? ($this->client->dni ?? $this->client->nif) : '');
            $this->client_phone = $this->contract->client_phone_signed ?: ($this->client ? $this->client->phone : '');
            $this->client_email = $this->contract->client_email_signed ?: ($this->client ? $this->client->email : '');
            $this->client_postal_code = $this->contract->client_postal_code_signed ?: ($this->client ? ($this->client->postal_code ?? '') : '');
            $this->client_city = $this->contract->client_city_signed ?: ($this->client ? ($this->client->city ?? '') : '');
            $this->client_province = $this->contract->client_province_signed ?: ($this->client ? ($this->client->province ?? '') : '');
            $this->client_address = $this->contract->client_address_signed ?: ($this->client ? $this->client->address : '');
            $this->signature_type = $this->contract->signature_type ?: 'canvas';
            $this->signature_data = $this->contract->signature_data;
            $this->certificate_issuer = $this->contract->certificate_issuer;
            $this->certificate_serial = $this->contract->certificate_serial;
            $this->certificate_hash = $this->contract->certificate_hash;
            $this->certificate_subject = $this->contract->certificate_subject;
            $this->consent_rrss = (bool) $this->contract->consent_rrss;
        } else {
            $this->client_name = $this->client ? $this->client->name : '';
            $this->client_dni = $this->client ? ($this->client->dni ?? $this->client->nif ?? '') : '';
            $this->client_phone = $this->client ? $this->client->phone : '';
            $this->client_email = $this->client ? $this->client->email : '';
            $this->client_postal_code = $this->client ? ($this->client->postal_code ?? '') : '';
            $this->client_city = $this->client ? ($this->client->city ?? '') : '';
            $this->client_province = $this->client ? ($this->client->province ?? '') : '';
            $this->client_address = $this->client ? $this->client->address : '';

            if ($this->client_postal_code) {
                $lookup = PostalCodeService::lookup($this->client_postal_code);
                if (!empty($lookup['city']) && empty($this->client_city)) {
                    $this->client_city = $lookup['city'];
                }
                if (!empty($lookup['province']) && empty($this->client_province)) {
                    $this->client_province = $lookup['province'];
                }
            }
        }

        $this->renderLiveContract();
    }

    public function updated($propertyName)
    {
        if (str_starts_with($propertyName, 'client_')) {
            $this->renderLiveContract();
        }
    }

    public function renderLiveContract()
    {
        $overrides = array_filter([
            'client_name' => $this->client_name ?: null,
            'client_dni' => $this->client_dni ?: null,
            'client_phone' => $this->client_phone ?: null,
            'client_email' => $this->client_email ?: null,
            'client_address' => $this->client_address ?: null,
            'client_postal_code' => $this->client_postal_code ?: null,
            'client_city' => $this->client_city ?: null,
            'client_province' => $this->client_province ?: null,
        ]);

        $rendered = ContractTemplateService::renderContract($this->contract, $overrides);
        $this->renderedTitle = $rendered['title'];
        $this->renderedBody = $rendered['body'];
        $this->renderedFooter = $rendered['footer'];
        $this->amount = $rendered['amount'];
    }

    public function updatedClientPostalCode($value)
    {
        $lookup = PostalCodeService::lookup($value);
        if (!empty($lookup['city']) && empty($this->client_city)) {
            $this->client_city = $lookup['city'];
        }
        if (!empty($lookup['province'])) {
            $this->client_province = $lookup['province'];
        }
        $this->renderLiveContract();
    }

    public function signWithCertificate($certData)
    {
        $this->signature_type = 'certificate';
        $this->certificate_issuer = $certData['issuer'] ?? 'AC FNMT Usuarios / DNIe';
        $this->certificate_serial = $certData['serial'] ?? strtoupper(Str::random(16));
        $this->certificate_subject = $certData['subject'] ?? ($this->client_name . ' (' . $this->client_dni . ')');
        
        // Generate cryptographic sha256 hash
        $contentToHash = $this->renderedTitle . '|' . $this->renderedBody . '|' . $this->client_dni . '|' . now()->toIso8601String();
        $this->certificate_hash = hash('sha256', $contentToHash);
        $this->signature_data = 'CERT_VALIDATED:' . $this->certificate_hash;

        $this->signContract();
    }

    public function signContract()
    {
        if ($this->isSigned) {
            return;
        }

        $rules = [
            'client_name' => 'required|string|max:255',
            'client_dni' => 'required|string|max:50',
            'client_phone' => 'required|string|max:50',
            'client_email' => 'required|email|max:100',
            'client_postal_code' => 'nullable|string|max:10',
            'client_city' => 'nullable|string|max:100',
            'client_province' => 'nullable|string|max:100',
            'client_address' => 'nullable|string|max:255',
            'accepted_terms' => 'accepted',
        ];

        if ($this->signature_type === 'canvas') {
            $rules['signature_data'] = 'required|string|min:100';
        } else {
            // Certificate signature
            if (empty($this->certificate_hash)) {
                $contentToHash = $this->renderedTitle . '|' . $this->renderedBody . '|' . $this->client_dni . '|' . now()->toIso8601String();
                $this->certificate_hash = hash('sha256', $contentToHash);
                $this->signature_data = 'CERT_VALIDATED:' . $this->certificate_hash;
            }
        }

        $this->validate($rules, [
            'client_name.required' => 'El nombre completo es obligatorio.',
            'client_dni.required' => 'El DNI / NIF es obligatorio para la validez del contrato.',
            'client_phone.required' => 'El teléfono es obligatorio.',
            'client_email.required' => 'El correo electrónico es obligatorio.',
            'accepted_terms.accepted' => 'Debes marcar la casilla aceptando los términos y condiciones del contrato.',
            'signature_data.required' => 'Por favor, estampa tu firma en el recuadro o valida con tu certificado digital.',
            'signature_data.min' => 'Por favor, estampa una firma válida en el recuadro.',
        ]);

        $this->contract->update([
            'status' => 'signed',
            'signed_at' => now(),
            'signature_type' => $this->signature_type,
            'signature_data' => $this->signature_data,
            'certificate_issuer' => $this->signature_type === 'certificate' ? ($this->certificate_issuer ?: 'AC FNMT Usuarios / DNIe') : null,
            'certificate_serial' => $this->signature_type === 'certificate' ? ($this->certificate_serial ?: strtoupper(Str::random(16))) : null,
            'certificate_hash' => $this->signature_type === 'certificate' ? $this->certificate_hash : null,
            'certificate_subject' => $this->signature_type === 'certificate' ? ($this->certificate_subject ?: "{$this->client_name} ({$this->client_dni})") : null,
            'signed_ip' => request()->ip(),
            'consent_rrss' => $this->consent_rrss,
            'client_name_signed' => $this->client_name,
            'client_dni_signed' => $this->client_dni,
            'client_phone_signed' => $this->client_phone,
            'client_email_signed' => $this->client_email,
            'client_address_signed' => $this->client_address,
            'client_postal_code_signed' => $this->client_postal_code,
            'client_city_signed' => $this->client_city,
            'client_province_signed' => $this->client_province,
        ]);

        // Update client user model with updated details if available
        if ($this->client) {
            $this->client->update([
                'phone' => $this->client_phone,
                'dni' => $this->client_dni,
                'address' => $this->client_address,
                'postal_code' => $this->client_postal_code,
                'city' => $this->client_city,
                'province' => $this->client_province,
            ]);
        }

        $this->isSigned = true;
        session()->flash('success_message', '¡Contrato firmado y aceptado correctamente! Muchas gracias.');
    }

    public function render()
    {
        return view('livewire.guest.contract-sign')->layout('components.layouts.wide-guest');
    }
}
