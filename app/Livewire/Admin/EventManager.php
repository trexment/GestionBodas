<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Event;
use App\Models\User;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Livewire\Traits\HandlesDniValidation;

class EventManager extends Component
{
    use HandlesDniValidation;
    public $events;
    
    // Properties for creating a new event
    public $name;
    public $brand = 'nunez_and_son'; // nunez_and_son, javnx, mago_leugim
    public $event_type = 'boda'; // boda, video, empresa, cumpleanos, comunion, otro
    public $agreed_price = 250;
    public $initial_status = 'confirmed';
    public $new_client_name = '';
    public $new_client_phone = '';
    public $event_date;
    public $setup_date;
    public $start_time;
    public $dance_start_time;
    public $dance_duration_hours = 4.0;
    public $max_end_time;
    public $location;
    public $client_id;
    public $partner_name;
    public $partner_phone;
    public $partner_email;
    public $partner_dni;
    public $dj_id;
    public $assistant_id;
    public $assistant_ids = [];
    public $notes;
    
    public $editing_event_id = null;
    public $ceremony_time;
    public $cocktail_time;
    public $banquet_time;
    
    public $clients = [];
    public $djs = [];
    public $assistants = [];

    public $showCreateModal = false;
    public $status_filter = 'all'; // all, no_response, draft, confirmed, completed, cancelled
    public bool $show_completed = false;
    public bool $show_cancelled = false;

    public function setStatusFilter($status)
    {
        $this->status_filter = $status;
        if ($status === 'completed') {
            $this->show_completed = true;
        } elseif (in_array($status, ['cancelled', 'rejected'], true)) {
            $this->show_cancelled = true;
        }
    }

    public function toggleShowCompleted()
    {
        $this->show_completed = !$this->show_completed;
        if (!$this->show_completed && $this->status_filter === 'completed') {
            $this->status_filter = 'all';
        }
    }

    public function toggleShowCancelled()
    {
        $this->show_cancelled = !$this->show_cancelled;
        if (!$this->show_cancelled && in_array($this->status_filter, ['cancelled', 'rejected'], true)) {
            $this->status_filter = 'all';
        }
    }

    protected $rules = [
        'name' => 'required|string|max:255',
        'brand' => 'nullable|string|in:nunez_and_son,javnx,mago_leugim',
        'event_type' => 'required|string|in:boda,video,empresa,cumpleanos,comunion,otro',
        'agreed_price' => 'nullable|numeric|min:0',
        'initial_status' => 'nullable|string|in:draft,confirmed',
        'new_client_name' => 'nullable|string|max:255',
        'new_client_phone' => 'nullable|string|max:50',
        'event_date' => 'nullable|date',
        'setup_date' => 'nullable|date',
        'start_time' => 'nullable|string|max:20',
        'ceremony_time' => 'nullable|string|max:20',
        'cocktail_time' => 'nullable|string|max:20',
        'banquet_time' => 'nullable|string|max:20',
        'dance_start_time' => 'nullable|string|max:20',
        'dance_duration_hours' => 'nullable|numeric|min:0.5|max:24',
        'max_end_time' => 'nullable|string|max:20',
        'location' => 'nullable|string|max:255',
        'client_id' => 'nullable|exists:users,id',
        'partner_name' => 'nullable|string|max:255',
        'partner_phone' => 'nullable|string|max:50',
        'partner_email' => 'nullable|email|max:255',
        'partner_dni' => 'nullable|string|max:50',
        'dj_id' => 'nullable|exists:users,id',
        'assistant_id' => 'nullable|exists:users,id',
        'assistant_ids' => 'nullable|array',
        'assistant_ids.*' => 'exists:users,id',
    ];

    public function mount()
    {
        if (auth()->check() && in_array(auth()->user()->role, ['admin', 'dj'])) {
            $syncRes = (new \App\Services\ImapLeadFetcherService())->autoSyncIfDue(120);
            if ($syncRes && ($syncRes['total_leads_created'] ?? 0) > 0) {
                session()->flash('message', "📩 ¡{$syncRes['total_leads_created']} nueva(s) solicitud(es) de evento importada(s) automáticamente desde el correo!");
            }
        }
        $this->loadEvents();
        $this->loadStaff();
    }

    public function syncEmailLeads()
    {
        try {
            \Illuminate\Support\Facades\Cache::forget('imap_auto_sync_last_run');
            $res = (new \App\Services\ImapLeadFetcherService())->fetchAllActiveMailboxes();
            $created = $res['total_leads_created'] ?? 0;
            $scanned = $res['total_scanned'] ?? 0;

            $this->loadEvents();
            $this->loadStaff();

            if ($created > 0) {
                session()->flash('message', "✅ ¡Sincronización completada! Se han importado {$created} nuevo(s) evento(s) de {$scanned} correos recientes revisados.");
            } else {
                session()->flash('message', "📬 Buzones al día ({$scanned} correos recientes verificados, 0 solicitudes nuevas pendientes).");
            }
        } catch (\Throwable $e) {
            session()->flash('error', 'Error al sincronizar buzones IMAP: ' . $e->getMessage());
        }
    }

    public function loadStaff()
    {
        $this->clients = User::where('role', 'client')->orderBy('name')->get();
        $this->djs = User::whereIn('role', ['dj', 'admin'])->orderBy('name')->get();
        $this->assistants = User::whereIn('role', ['assistant', 'dj', 'admin'])->orderBy('name')->get();
    }

    public function loadEvents()
    {
        $user = auth()->user();
        $relations = ['client', 'dj', 'assistant'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }

        if ($user->role === 'admin') {
            $this->events = Event::with($relations)->orderBy('event_date', 'asc')->get();
        } else {
            $this->events = Event::with($relations)
                ->where(function ($q) use ($user) {
                    $q->where('dj_id', $user->id)
                      ->orWhere('assistant_id', $user->id);
                    if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                        $q->orWhereHas('assistants', fn($sq) => $sq->where('users.id', $user->id));
                    }
                })
                ->orderBy('event_date', 'asc')
                ->get();
        }
    }

    public function openCreateModal()
    {
        if (!in_array(auth()->user()->role, ['admin', 'dj'])) {
            session()->flash('error', 'Solo los administradores y DJs autorizados pueden crear nuevos eventos.');
            return;
        }
        $this->resetValidation();
        $this->editing_event_id = null;
        $this->reset(['name', 'brand', 'event_type', 'event_date', 'setup_date', 'start_time', 'ceremony_time', 'cocktail_time', 'banquet_time', 'dance_start_time', 'dance_duration_hours', 'max_end_time', 'location', 'client_id', 'partner_name', 'partner_phone', 'partner_email', 'partner_dni', 'dj_id', 'assistant_id', 'assistant_ids', 'notes', 'new_client_name', 'new_client_phone']);
        $this->brand = \App\Models\Setting::getDetectedBrand();
        $this->event_type = 'boda';
        $this->agreed_price = 250;
        $this->initial_status = 'confirmed';
        $this->dance_duration_hours = 4.0;
        $this->dj_id = auth()->id(); // default current logged admin/dj
        $this->assistant_ids = [];
        $this->loadStaff();
        $this->showCreateModal = true;
    }

    public function updatedBrand($value)
    {
        if ($this->event_type === 'video' && !\App\Models\Setting::isVideoServiceEnabled($value)) {
            $this->event_type = 'boda';
        }
    }

    public function openEditModal($eventId)
    {
        if (!in_array(auth()->user()->role, ['admin', 'dj'])) {
            session()->flash('error', 'Solo los administradores y DJs autorizados pueden editar eventos.');
            return;
        }
        $this->resetValidation();
        $event = Event::findOrFail($eventId);
        $this->editing_event_id = $event->id;
        $this->name = $event->name;
        $this->brand = $event->brand ?: 'nunez_and_son';
        $this->event_type = $event->event_type ?: 'boda';
        $this->event_date = $event->event_date ? $event->event_date->format('Y-m-d') : null;
        $this->setup_date = $event->setup_date ? \Carbon\Carbon::parse($event->setup_date)->format('Y-m-d') : null;
        $this->start_time = $event->start_time;
        $this->ceremony_time = $event->ceremony_time;
        $this->cocktail_time = $event->cocktail_time;
        $this->banquet_time = $event->banquet_time;
        $this->dance_start_time = $event->dance_start_time;
        $this->dance_duration_hours = $event->dance_duration_hours ?: 4.0;
        $this->max_end_time = $event->max_end_time;
        $this->location = $event->location;
        $this->client_id = $event->client_id;
        $this->partner_name = $event->partner_name;
        $this->partner_phone = $event->partner_phone;
        $this->partner_email = $event->partner_email;
        $this->partner_dni = $event->partner_dni;
        if (!empty($this->partner_dni)) {
            $this->validateAndCompleteDni('partner_dni', $this->partner_dni);
        } else {
            $this->dniValidations['partner_dni'] = null;
        }
        $this->dj_id = $event->dj_id;
        $this->assistant_id = $event->assistant_id;
        $this->assistant_ids = $event->all_assistants->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $this->notes = $event->notes;
        $this->loadStaff();
        $this->showCreateModal = true;
    }

    public function updatedPartnerDni($value)
    {
        $this->validateAndCompleteDni('partner_dni', $value);
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->editing_event_id = null;
    }

    public function saveEvent()
    {
        $this->validate();

        try {
            // Si las columnas o tablas aún no existen, intentamos aplicar migraciones automáticamente
            if (!\Illuminate\Support\Facades\Schema::hasColumn('events', 'partner_name') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'setup_date') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'dance_start_time') || !\Illuminate\Support\Facades\Schema::hasColumn('events', 'max_end_time') || !\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                try {
                    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
                } catch (\Throwable $migEx) {
                    // Ignorar si no se puede ejecutar artisan en este contexto
                }
            }

            // Si se introdujo un nuevo contacto directo y no se seleccionó cliente previo
            if (empty($this->client_id) && !empty(trim((string)$this->new_client_name))) {
                $cleanName = trim((string)$this->new_client_name);
                $cleanPhone = trim((string)$this->new_client_phone);
                $existingClient = null;
                if (!empty($cleanPhone)) {
                    $existingClient = User::where('role', 'client')->where('phone', $cleanPhone)->first();
                }
                if (!$existingClient) {
                    $existingClient = User::where('role', 'client')->where('name', $cleanName)->first();
                }

                if (!$existingClient) {
                    $slug = \Illuminate\Support\Str::slug($cleanName, '_');
                    $email = 'contacto_' . ($slug ?: 'colaborador') . '_' . time() . '@colaborador.local';
                    $existingClient = User::create([
                        'name' => $cleanName,
                        'email' => $email,
                        'phone' => $cleanPhone ?: null,
                        'role' => 'client',
                        'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                    ]);
                }
                $this->client_id = $existingClient->id;
            }

            $firstAssistantId = !empty($this->assistant_ids) ? (int)$this->assistant_ids[0] : ($this->assistant_id ?: null);

            $effectiveDate = !empty($this->event_date)
                ? $this->event_date
                : \Carbon\Carbon::createFromDate(2027, 6, 1)->format('Y-m-d');
            $effectiveLocation = !empty(trim((string)$this->location))
                ? trim((string)$this->location)
                : 'Por definir';
            $effectiveNotes = $this->notes;
            if (empty($this->event_date) && !str_contains((string)$effectiveNotes, 'Fecha exacta pendiente')) {
                $effectiveNotes = Event::prependNote($effectiveNotes, '⏳ Fecha exacta pendiente de definir (fecha orientativa temporal).');
            }

            $eventData = [
                'name' => $this->name,
                'brand' => $this->brand ?: 'nunez_and_son',
                'event_type' => $this->event_type ?: 'boda',
                'event_date' => $effectiveDate,
                'location' => $effectiveLocation,
                'client_id' => $this->client_id ?: null,
                'partner_name' => $this->partner_name ?: null,
                'partner_phone' => $this->partner_phone ?: null,
                'partner_email' => $this->partner_email ?: null,
                'partner_dni' => $this->partner_dni ?: null,
                'dj_id' => ($this->event_type === 'video') ? auth()->id() : ($this->dj_id ?: auth()->id()),
                'assistant_id' => ($this->event_type === 'video') ? null : $firstAssistantId,
                'notes' => $effectiveNotes,
            ];

            $scheduleFields = [
                'setup_date' => $this->setup_date ?: null,
                'start_time' => $this->start_time ?: null,
                'ceremony_time' => $this->ceremony_time ?: null,
                'cocktail_time' => $this->cocktail_time ?: null,
                'banquet_time' => $this->banquet_time ?: null,
                'dance_start_time' => $this->dance_start_time ?: null,
                'dance_duration_hours' => $this->dance_duration_hours ? (float)$this->dance_duration_hours : null,
                'max_end_time' => $this->max_end_time ?: null,
            ];

            foreach ($scheduleFields as $col => $val) {
                if (\Illuminate\Support\Facades\Schema::hasColumn('events', $col)) {
                    $eventData[$col] = $val;
                }
            }

            if ($this->editing_event_id) {
                $event = Event::findOrFail($this->editing_event_id);
                $event->update($eventData);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $event->assistants()->sync($this->assistant_ids ?: []);
                }
                try {
                    \App\Services\VenueLookupService::autoPopulateEventVenuePhone($event);
                } catch (\Throwable $e) {}
                $msg = '¡Evento "' . $this->name . '" actualizado exitosamente!';
            } else {
                $eventStatus = ($this->event_type === 'video') ? ($this->initial_status ?: 'confirmed') : 'draft';
                $eventData['status'] = $eventStatus;
                $event = Event::create($eventData);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $event->assistants()->sync(($this->event_type === 'video') ? [] : ($this->assistant_ids ?: []));
                }
                try {
                    \App\Services\VenueLookupService::autoPopulateEventVenuePhone($event);
                } catch (\Throwable $e) {}

                // Generar automáticamente presupuesto de videógrafo si es tipo video y tiene precio acordado
                if ($this->event_type === 'video' && (float)$this->agreed_price > 0) {
                    $quote = Quote::create([
                        'event_id' => $event->id,
                        'brand' => $event->brand ?: 'nunez_and_son',
                        'amount' => (float)$this->agreed_price,
                        'status' => 'accepted',
                        'tax_type' => 'included',
                        'tax_rate' => 21.00,
                    ]);

                    QuoteItem::create([
                        'quote_id' => $quote->id,
                        'service_name' => 'Servicio de Videógrafo / Cobertura de Vídeo',
                        'description' => 'Servicio audiovisual de grabación/vídeo para evento/boda acordado con colaborador.',
                        'price' => (float)$this->agreed_price,
                        'quantity' => 1,
                    ]);
                }

                $msg = ($this->event_type === 'video')
                    ? '¡Evento de Videógrafo registrado y fecha bloqueada (' . number_format((float)$this->agreed_price, 2, ',', '.') . ' €) exitosamente!'
                    : '¡Evento creado exitosamente con personal y equipo asignados!';
            }

            $this->closeCreateModal();
            $this->loadEvents();
            
            session()->flash('message', $msg);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Error saving event: ' . $e->getMessage());
            session()->flash('error', 'Error al guardar el evento: ' . $e->getMessage());
        }
    }

    public function deleteEvent($eventId)
    {
        if (auth()->user()->role !== 'admin') {
            session()->flash('error', 'Solo los administradores pueden eliminar eventos.');
            return;
        }

        $event = Event::with('client')->findOrFail($eventId);
        $name = $event->name;

        // Registrar huella del evento eliminado para que el sincronizador IMAP jamás vuelva a importarlo
        try {
            \App\Services\ImapLeadFetcherService::markEventAsDeleted($event);
            \App\Services\AppNotificationService::removeByEventId((int) $event->id);
        } catch (\Throwable $e) {}

        // Cascade delete relations
        foreach ($event->quotes as $quote) {
            $quote->items()->delete();
            $quote->delete();
        }
        $event->invoices()->delete();
        $event->contracts()->delete();
        $event->dossiers()->delete();
        $event->playlists()->delete();
        $event->musicRequests()->delete();
        try {
            $event->whatsappMessages()->delete();
        } catch (\Throwable $e) {}
        $event->equipment()->detach();
        $event->delete();

        $this->loadEvents();
        session()->flash('message', "🗑️ El evento '{$name}' ha sido eliminado permanentemente (y bloqueado para no reimportarse por email).");
    }

    public $confirmingFollowupEventId = null;
    public $confirmingFollowupEventName = '';
    public $confirmingFollowupClientName = '';
    public $confirmingFollowupDays = 0;
    public $confirmingFollowupStatus = 'no_response';
    public $confirmingFollowupSelectedKey = 'dia3_dudas';
    public $confirmingFollowupOptions = [];
    public $confirmingFollowupUrl = '';
    public $confirmingFollowupText = '';
    public $confirmingFollowupEmail = '';
    public $confirmingFollowupSubject = '';
    public $confirmingFollowupMailtoUrl = '';
    public $confirmingFollowupHasPhone = false;

    public function promptFollowup(int $eventId, ?string $templateKey = null)
    {
        $event = Event::with('client')->find($eventId);
        if (!$event) return;

        $this->confirmingFollowupEventId = $event->id;
        $this->confirmingFollowupEventName = $event->name;
        $this->confirmingFollowupClientName = $event->client ? $event->client->name : ($event->partner_name ?: 'Cliente');
        $this->confirmingFollowupStatus = $event->status ?: 'no_response';
        $this->confirmingFollowupDays = $event->status === 'on_hold' ? $event->days_on_hold : $event->days_without_response;
        $this->confirmingFollowupEmail = $event->client_contact_email ?: '';
        $this->confirmingFollowupHasPhone = !empty($event->client_contact_phone);

        $this->confirmingFollowupOptions = \App\Services\WhatsAppTemplateService::getFollowupOptions($event);
        $key = $templateKey ?: \App\Services\WhatsAppTemplateService::getRecommendedFollowupKey($event);
        $this->selectFollowupTemplate($key);
    }

    public function selectFollowupTemplate(string $key)
    {
        $this->confirmingFollowupSelectedKey = $key;
        if (isset($this->confirmingFollowupOptions[$key])) {
            $this->confirmingFollowupUrl = $this->confirmingFollowupOptions[$key]['url'] ?? '';
            $this->confirmingFollowupText = $this->confirmingFollowupOptions[$key]['text'] ?? '';
            $this->confirmingFollowupSubject = $this->confirmingFollowupOptions[$key]['subject'] ?? ('Seguimiento · ' . $this->confirmingFollowupEventName);
            $this->confirmingFollowupMailtoUrl = $this->confirmingFollowupOptions[$key]['mailto_url'] ?? '';
        }
    }

    public function sendDirectFollowupEmail()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::with('client')->find($this->confirmingFollowupEventId);
        if (!$event) return;

        $toEmail = trim((string) ($this->confirmingFollowupEmail ?: $event->client_contact_email));
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            session()->flash('error', 'Introduce un email válido del cliente para enviar el seguimiento por correo.');
            return;
        }

        $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
        $subject = trim((string) $this->confirmingFollowupSubject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($event, $this->confirmingFollowupSelectedKey);

        $res = \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
            $event,
            $toEmail,
            $subject,
            (string) $this->confirmingFollowupText,
            $this->confirmingFollowupSelectedKey,
            $templateName
        );

        $this->confirmingFollowupEventId = null;
        $this->loadEvents();

        if ($res['success']) {
            session()->flash('message', "📧 ¡Seguimiento enviado por Email a {$toEmail} para {$event->name}! Registrado en la conversación del evento y contador a 0.");
        } else {
            $this->dispatch('open-whatsapp-link', url: $res['mailto_url']);
            session()->flash('message', "📧 Seguimiento registrado en el evento ({$templateName}) y abierto en tu gestor de correo para {$toEmail}.");
        }
    }

    public function confirmFollowupSent()
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $templateName = $this->confirmingFollowupOptions[$this->confirmingFollowupSelectedKey]['title'] ?? 'Seguimiento Madrigal';
            $newEntry = "[" . now()->format('d/m/Y H:i') . "] 💬 Seguimiento enviado por " . auth()->user()->name . " ({$templateName}). Contador reseteado a 0.";
            $event->update([
                'no_response_at' => now(),
                'notes' => Event::prependNote($event->notes, $newEntry),
            ]);
            session()->flash('message', "¡Seguimiento registrado para {$event->name}! ({$templateName}). Contador reseteado.");
        }

        $this->confirmingFollowupEventId = null;
        $this->loadEvents();
    }

    public function markAsResponded(string $newStatus = 'draft')
    {
        if (!$this->confirmingFollowupEventId) return;

        $event = Event::find($this->confirmingFollowupEventId);
        if ($event) {
            $validStatus = in_array($newStatus, ['draft', 'confirmed', 'on_hold'], true) ? $newStatus : 'draft';
            if ($validStatus === 'on_hold') {
                $newEntry = "[" . now()->format('d/m/Y H:i') . "] ⏸️ El cliente indicó que se pondrá en contacto más adelante. Estado cambiado a En Espera por " . auth()->user()->name . ".";
                $updateData = [
                    'status' => 'on_hold',
                    'no_response_at' => now(),
                    'notes' => Event::prependNote($event->notes, $newEntry),
                ];
            } else {
                $newEntry = "[" . now()->format('d/m/Y H:i') . "] 🎉 El cliente ha respondido al seguimiento. Estado cambiado a " . ucfirst($validStatus) . " por " . auth()->user()->name . ".";
                $updateData = [
                    'status' => $validStatus,
                    'no_response_at' => null,
                    'notes' => Event::prependNote($event->notes, $newEntry),
                ];
            }

            try {
                $event->update($updateData);
            } catch (\Illuminate\Database\QueryException $e) {
                if (str_contains($e->getMessage(), 'Data truncated') || str_contains($e->getMessage(), '1265')) {
                    \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
                    $event->update($updateData);
                } else {
                    throw $e;
                }
            }

            $statusMsg = match($validStatus) {
                'confirmed' => 'Confirmado',
                'on_hold' => 'En Espera (En Pausa, sin alerta de sin respuesta)',
                default => 'Borrador',
            };
            session()->flash('message', "¡Actualizado! {$event->name} se ha marcado como {$statusMsg}.");
        }

        $this->confirmingFollowupEventId = null;
        $this->loadEvents();
    }

    public function cancelFollowup()
    {
        $this->confirmingFollowupEventId = null;
    }

    public function putEventOnHold(int $eventId)
    {
        $event = Event::find($eventId);
        if (!$event) return;

        $userName = auth()->user()?->name ?? 'Sistema';
        $newEntry = "[" . now()->format('d/m/Y H:i') . "] ⏸️ Evento puesto En Espera por {$userName} (el cliente indicó que se pondrá en contacto). Contador de días en pausa iniciado.";
        $updateData = [
            'status' => 'on_hold',
            'no_response_at' => now(),
            'notes' => Event::prependNote($event->notes, $newEntry),
        ];

        try {
            $event->update($updateData);
        } catch (\Illuminate\Database\QueryException $e) {
            if (str_contains($e->getMessage(), 'Data truncated') || str_contains($e->getMessage(), '1265')) {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE `events` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'draft'");
                $event->update($updateData);
            } else {
                throw $e;
            }
        }

        $this->loadEvents();
        session()->flash('message', "⏸️ «{$event->name}» puesto En Espera (En Pausa). Ya no saldrá como sin respuesta; contará los días en pausa y avisará a los 30 días.");
    }

    // =========================================================================
    // 🎪 FERIA DE NOVIOS: IMPORTACIÓN Y DIFUSIÓN MASIVA (LA RIOJA BRIDAL MARKET)
    // =========================================================================
    public bool $showFairModal = false;
    public bool $fairShowCsvEditor = false;
    public string $fairName = 'III Edición La Rioja Bridal Market';
    public string $fairBrand = 'nunez_and_son';
    public string $fairSenderBrand = 'miguel';
    public string $fairInitialStatus = 'no_response';
    public string $fairRawCsv = '';
    public array $fairRows = [];
    public string $fairFilter = 'all'; // all, with_date, with_phone, only_email, music_interest, pending
    public string $fairMessageTemplate = '';
    public string $fairEmailSubject = '';

    public function openFairModal(): void
    {
        if (!in_array(auth()->user()->role, ['admin', 'dj'])) {
            session()->flash('error', 'Solo los administradores y DJs autorizados pueden usar el importador masivo de feria.');
            return;
        }

        $this->fairBrand = \App\Models\Setting::getDetectedBrand();
        $uLower = mb_strtolower((auth()->user()->name ?? '') . ' ' . (auth()->user()->email ?? ''));
        if (str_contains($uLower, 'fran') || str_contains($uLower, 'javnx')) {
            $this->fairSenderBrand = 'fran';
        } elseif (str_contains($uLower, 'ezequiel')) {
            $this->fairSenderBrand = 'ezequiel';
        } else {
            $this->fairSenderBrand = 'miguel';
        }

        if (empty(trim($this->fairRawCsv))) {
            $this->fairRawCsv = \App\Services\BridalFairLeadService::getDefaultRiojaBridalMarketCsv();
        }
        if (empty(trim($this->fairMessageTemplate))) {
            $this->fairMessageTemplate = \App\Services\BridalFairLeadService::getDefaultFairTemplate();
        }
        if (empty(trim($this->fairEmailSubject))) {
            $companyName = $this->fairBrand === 'javnx' ? 'JAVNX DJ' : 'Núñez and Son DJ';
            $this->fairEmailSubject = "💍 Promoción Especial La Rioja Bridal Market + Dossier Tarifas 2027 - {$companyName}";
        }

        $this->parseFairCsvInput();
        $this->showFairModal = true;
    }

    public function closeFairModal(): void
    {
        $this->showFairModal = false;
    }

    public function toggleFairCsvEditor(): void
    {
        $this->fairShowCsvEditor = !$this->fairShowCsvEditor;
    }

    public function setFairSenderBrand(string $sender): void
    {
        if (in_array($sender, ['miguel', 'fran', 'ezequiel'], true)) {
            $this->fairSenderBrand = $sender;
        }
    }

    public function resetFairTemplate(): void
    {
        $this->fairMessageTemplate = \App\Services\BridalFairLeadService::getDefaultFairTemplate();
        $companyName = $this->fairBrand === 'javnx' ? 'JAVNX DJ' : 'Núñez and Son DJ';
        $this->fairEmailSubject = "💍 Promoción Especial La Rioja Bridal Market + Dossier Tarifas 2027 - {$companyName}";
    }

    public function setFairFilter(string $filter): void
    {
        $this->fairFilter = $filter;
    }

    public function parseFairCsvInput(): void
    {
        $parsed = \App\Services\BridalFairLeadService::parseFairCsv($this->fairRawCsv, $this->fairName);

        // Enriquecer con estado real de envíos previos en WhatsAppMessage si el evento ya existe
        $eventIds = array_values(array_filter(array_column($parsed, 'existing_event_id')));
        $sentWaMap = [];
        $sentEmailMap = [];

        if (!empty($eventIds)) {
            try {
                $msgs = \App\Models\WhatsAppMessage::whereIn('event_id', $eventIds)
                    ->where('direction', 'outbound')
                    ->get(['event_id', 'payload', 'message']);
                foreach ($msgs as $m) {
                    $eid = (int) $m->event_id;
                    $channel = is_array($m->payload) ? ($m->payload['channel'] ?? 'whatsapp') : 'whatsapp';
                    if ($channel === 'email' || str_starts_with((string) $m->message, '📧')) {
                        $sentEmailMap[$eid] = true;
                    } else {
                        $sentWaMap[$eid] = true;
                    }
                }
            } catch (\Throwable $e) {}
        }

        foreach ($parsed as &$row) {
            $eid = (int) ($row['existing_event_id'] ?? 0);
            if ($eid > 0) {
                $row['wa_sent'] = !empty($sentWaMap[$eid]);
                $row['email_sent'] = !empty($sentEmailMap[$eid]);
            }
        }
        unset($row);

        $this->fairRows = $parsed;
    }

    public function toggleFairRow(int $index): void
    {
        if (isset($this->fairRows[$index])) {
            $this->fairRows[$index]['selected'] = !($this->fairRows[$index]['selected'] ?? false);
        }
    }

    public function selectAllFairRows(bool $select): void
    {
        foreach ($this->fairRows as $idx => $row) {
            if ($this->doesFairRowMatchFilter($row)) {
                $this->fairRows[$idx]['selected'] = $select;
            }
        }
    }

    protected function doesFairRowMatchFilter(array $row): bool
    {
        return match ($this->fairFilter) {
            'with_date' => !empty($row['has_exact_date']),
            'with_phone' => !empty($row['has_phone']),
            'only_email' => empty($row['has_phone']) && !empty($row['has_email']),
            'music_interest' => !empty($row['mentions_music']),
            'pending' => empty($row['wa_sent']) && empty($row['email_sent']),
            default => true,
        };
    }

    public function importSelectedFairLeads(): void
    {
        $createdEvents = 0;
        $alreadyExisted = 0;
        $djId = auth()->id();

        foreach ($this->fairRows as $idx => $row) {
            if (empty($row['selected'])) {
                continue;
            }

            $res = \App\Services\BridalFairLeadService::createOrGetFairEvent(
                $row,
                $this->fairBrand ?: 'nunez_and_son',
                $djId,
                $this->fairInitialStatus ?: 'no_response'
            );

            $ev = $res['event'];
            $this->fairRows[$idx]['existing_event_id'] = $ev->id;
            $this->fairRows[$idx]['existing_event_name'] = $ev->name;
            $this->fairRows[$idx]['existing_event_token'] = $ev->token;

            if (!empty($res['created_event'])) {
                $createdEvents++;
            } else {
                $alreadyExisted++;
            }
        }

        $this->loadEvents();
        $this->loadStaff();

        session()->flash(
            'message',
            "🎪 ¡Importación de Feria completada! {$createdEvents} nuevo(s) evento(s) y cliente(s) creados"
            . ($alreadyExisted > 0 ? " ({$alreadyExisted} ya existían y se han vinculado sin duplicar)." : ".")
        );
    }

    public function sendFairSingleWhatsApp(int $index): void
    {
        if (!isset($this->fairRows[$index])) {
            return;
        }

        $row = $this->fairRows[$index];
        if (empty($row['has_phone']) || empty($row['phone'])) {
            session()->flash('error', "El contacto {$row['name']} no tiene teléfono móvil anotado. Envíale la información por Email.");
            return;
        }

        $res = \App\Services\BridalFairLeadService::createOrGetFairEvent(
            $row,
            $this->fairBrand ?: 'nunez_and_son',
            auth()->id(),
            $this->fairInitialStatus ?: 'no_response'
        );
        /** @var Event $event */
        $event = $res['event'];

        $this->fairRows[$index]['existing_event_id'] = $event->id;
        $this->fairRows[$index]['existing_event_name'] = $event->name;
        $this->fairRows[$index]['existing_event_token'] = $event->token;

        $msgText = \App\Services\BridalFairLeadService::renderFairMessageForEvent(
            $event,
            $row,
            $this->fairMessageTemplate,
            $this->fairSenderBrand
        );

        $evo = new \App\Services\EvolutionApiService($this->fairBrand ?: 'nunez_and_son');
        $sentByApi = false;

        if ($evo->isConfigured()) {
            $apiRes = $evo->sendText($row['phone'], $msgText);
            if (!empty($apiRes['success'])) {
                $sentByApi = true;
            }
        }

        try {
            \App\Models\WhatsAppMessage::create([
                'event_id' => $event->id,
                'user_id' => auth()->id(),
                'phone' => \App\Services\WhatsAppTemplateService::cleanPhone($row['phone']),
                'sender_name' => auth()->user()?->name ?? 'Sistema',
                'direction' => 'outbound',
                'message' => $msgText,
                'status' => $sentByApi ? 'sent' : 'manual',
                'brand' => $this->fairBrand ?: 'nunez_and_son',
                'payload' => [
                    'channel' => 'whatsapp',
                    'campaign' => 'bridal_fair',
                    'fair_name' => $this->fairName,
                    'sent_by_api' => $sentByApi,
                ],
            ]);

            $noteLine = "[" . now()->format('d/m/Y H:i') . "] 🎪 WhatsApp Promo Feria enviado a {$row['phone']} por " . (auth()->user()?->name ?? 'Sistema') . ".";
            $event->update([
                'no_response_at' => now(),
                'notes' => Event::prependNote($event->notes, $noteLine),
            ]);
        } catch (\Throwable $e) {}

        $this->fairRows[$index]['wa_sent'] = true;
        $this->loadEvents();

        if ($sentByApi) {
            session()->flash('message', "💬 ¡WhatsApp enviado directamente por API a {$row['name']} ({$row['phone']})!");
        } else {
            $waUrl = \App\Services\WhatsAppTemplateService::buildUrl($row['phone'], $msgText);
            $this->dispatch('open-whatsapp-link', url: $waUrl);
            session()->flash('message', "💬 Abriendo WhatsApp para {$row['name']} ({$row['phone']}) y registrado en su ficha.");
        }
    }

    public function sendFairSingleEmail(int $index): void
    {
        if (!isset($this->fairRows[$index])) {
            return;
        }

        $row = $this->fairRows[$index];
        if (empty($row['has_email']) || empty($row['email'])) {
            session()->flash('error', "El contacto {$row['name']} no tiene email válido.");
            return;
        }

        $res = \App\Services\BridalFairLeadService::createOrGetFairEvent(
            $row,
            $this->fairBrand ?: 'nunez_and_son',
            auth()->id(),
            $this->fairInitialStatus ?: 'no_response'
        );
        /** @var Event $event */
        $event = $res['event'];

        $this->fairRows[$index]['existing_event_id'] = $event->id;
        $this->fairRows[$index]['existing_event_name'] = $event->name;
        $this->fairRows[$index]['existing_event_token'] = $event->token;

        $msgText = \App\Services\BridalFairLeadService::renderFairMessageForEvent(
            $event,
            $row,
            $this->fairMessageTemplate,
            $this->fairSenderBrand
        );

        $subject = trim($this->fairEmailSubject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($event, 'feria_novios');

        $emailRes = \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
            $event,
            $row['email'],
            $subject,
            $msgText,
            $this->fairBrand ?: 'nunez_and_son',
            auth()->id(),
            'Feria de Novios (' . $this->fairName . ')'
        );

        $this->fairRows[$index]['email_sent'] = true;
        $this->loadEvents();

        if (($emailRes['mode'] ?? '') === 'smtp') {
            session()->flash('message', "📧 ¡Email enviado por SMTP a {$row['name']} ({$row['email']}) y guardado en su ficha!");
        } else {
            $this->dispatch('open-whatsapp-link', url: $emailRes['mailto_url']);
            session()->flash('message', "📧 Email registrado para {$row['name']} ({$row['email']}). Abriendo gestor de correo...");
        }
    }

    public function sendFairBulkEmails(): void
    {
        $sentCount = 0;
        $skippedAlreadySent = 0;
        $djId = auth()->id();

        foreach ($this->fairRows as $idx => $row) {
            if (empty($row['selected']) || empty($row['has_email']) || empty($row['email'])) {
                continue;
            }
            if (!empty($row['email_sent'])) {
                $skippedAlreadySent++;
                continue;
            }

            $res = \App\Services\BridalFairLeadService::createOrGetFairEvent(
                $row,
                $this->fairBrand ?: 'nunez_and_son',
                $djId,
                $this->fairInitialStatus ?: 'no_response'
            );
            /** @var Event $event */
            $event = $res['event'];

            $this->fairRows[$idx]['existing_event_id'] = $event->id;
            $this->fairRows[$idx]['existing_event_name'] = $event->name;
            $this->fairRows[$idx]['existing_event_token'] = $event->token;

            $msgText = \App\Services\BridalFairLeadService::renderFairMessageForEvent(
                $event,
                $row,
                $this->fairMessageTemplate,
                $this->fairSenderBrand
            );

            $subject = trim($this->fairEmailSubject) ?: \App\Services\WhatsAppTemplateService::getEmailSubjectForKey($event, 'feria_novios');

            \App\Services\WhatsAppTemplateService::sendAndLogClientEmail(
                $event,
                $row['email'],
                $subject,
                $msgText,
                $this->fairBrand ?: 'nunez_and_son',
                $djId,
                'Feria de Novios (' . $this->fairName . ')'
            );

            $this->fairRows[$idx]['email_sent'] = true;
            $sentCount++;
        }

        $this->loadEvents();
        $this->loadStaff();

        if ($sentCount > 0) {
            session()->flash(
                'message',
                "📧 ¡Envío Masivo por Email completado! Se han enviado {$sentCount} correos personalizados con el Dossier de Tarifas y enlace a /presupuesto (y creado sus fichas de cliente/evento)."
                . ($skippedAlreadySent > 0 ? " ({$skippedAlreadySent} omitidos porque ya se les había enviado)." : "")
            );
        } else {
            session()->flash('message', "ℹ️ No había contactos seleccionados pendientes de envío por Email ({$skippedAlreadySent} ya enviados previamente).");
        }
    }

    public function sendFairBulkWhatsAppBatch(int $batchSize = 10): void
    {
        $evo = new \App\Services\EvolutionApiService($this->fairBrand ?: 'nunez_and_son');
        $isApiConfigured = $evo->isConfigured();

        if (!$isApiConfigured) {
            // Si no hay API automática configurada, abrimos el siguiente pendiente en WhatsApp Web
            foreach ($this->fairRows as $idx => $row) {
                if (!empty($row['selected']) && !empty($row['has_phone']) && empty($row['wa_sent'])) {
                    $this->sendFairSingleWhatsApp($idx);
                    return;
                }
            }
            session()->flash('message', 'ℹ️ Todos los contactos con teléfono seleccionados ya tienen su WhatsApp marcado como enviado.');
            return;
        }

        $sentCount = 0;
        $failedCount = 0;
        $djId = auth()->id();

        foreach ($this->fairRows as $idx => $row) {
            if ($sentCount >= $batchSize) {
                break;
            }
            if (empty($row['selected']) || empty($row['has_phone']) || empty($row['phone']) || !empty($row['wa_sent'])) {
                continue;
            }

            $res = \App\Services\BridalFairLeadService::createOrGetFairEvent(
                $row,
                $this->fairBrand ?: 'nunez_and_son',
                $djId,
                $this->fairInitialStatus ?: 'no_response'
            );
            /** @var Event $event */
            $event = $res['event'];

            $this->fairRows[$idx]['existing_event_id'] = $event->id;
            $this->fairRows[$idx]['existing_event_name'] = $event->name;
            $this->fairRows[$idx]['existing_event_token'] = $event->token;

            $msgText = \App\Services\BridalFairLeadService::renderFairMessageForEvent(
                $event,
                $row,
                $this->fairMessageTemplate,
                $this->fairSenderBrand
            );

            $apiRes = $evo->sendText($row['phone'], $msgText);
            if (!empty($apiRes['success'])) {
                try {
                    \App\Models\WhatsAppMessage::create([
                        'event_id' => $event->id,
                        'user_id' => $djId,
                        'phone' => \App\Services\WhatsAppTemplateService::cleanPhone($row['phone']),
                        'sender_name' => auth()->user()?->name ?? 'Sistema',
                        'direction' => 'outbound',
                        'message' => $msgText,
                        'status' => 'sent',
                        'brand' => $this->fairBrand ?: 'nunez_and_son',
                        'payload' => [
                            'channel' => 'whatsapp',
                            'campaign' => 'bridal_fair_batch',
                            'fair_name' => $this->fairName,
                        ],
                    ]);
                    $noteLine = "[" . now()->format('d/m/Y H:i') . "] 🎪 WhatsApp Promo Feria (Lote Masivo) enviado a {$row['phone']} por " . (auth()->user()?->name ?? 'Sistema') . ".";
                    $event->update([
                        'no_response_at' => now(),
                        'notes' => Event::prependNote($event->notes, $noteLine),
                    ]);
                } catch (\Throwable $e) {}

                $this->fairRows[$idx]['wa_sent'] = true;
                $sentCount++;
                // Pequeña pausa anti-bloqueo entre envíos consecutivos
                usleep(500000);
            } else {
                $failedCount++;
            }
        }

        $this->loadEvents();
        $this->loadStaff();

        $remainingWa = collect($this->fairRows)->filter(fn($r) => !empty($r['selected']) && !empty($r['has_phone']) && empty($r['wa_sent']))->count();

        if ($sentCount > 0) {
            session()->flash(
                'message',
                "💬 ¡Lote de {$sentCount} WhatsApps enviado con éxito!"
                . ($remainingWa > 0 ? " Quedan {$remainingWa} contactos con teléfono pendientes (vuelve a pulsar el botón para enviar el siguiente lote de {$batchSize})." : " ¡Todos los WhatsApps seleccionados han sido enviados!")
            );
        } elseif ($failedCount > 0) {
            session()->flash('error', "⚠️ No se pudo enviar por API automática ({$failedCount} fallos). Puedes pulsar el botón 💬 WA individual de cada fila para abrir WhatsApp directamente.");
        } else {
            session()->flash('message', "ℹ️ No quedan contactos con teléfono pendientes de envío por WhatsApp en la selección actual.");
        }
    }

    public function render()
    {
        $user = auth()->user();
        $relations = ['client', 'dj', 'assistant'];
        if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
            $relations[] = 'assistants';
        }

        $query = Event::with($relations);

        if ($user->role !== 'admin') {
            $query->where(function ($q) use ($user) {
                $q->where('dj_id', $user->id)
                  ->orWhere('assistant_id', $user->id);
                if (\Illuminate\Support\Facades\Schema::hasTable('event_assistants')) {
                    $q->orWhereHas('assistants', fn($sq) => $sq->where('users.id', $user->id));
                }
            });
        }

        $allEvents = $query->orderBy('event_date', 'asc')->get();

        $visiblePool = $allEvents->filter(function ($ev) {
            if (!$this->show_completed && $ev->status === 'completed') {
                return false;
            }
            if (!$this->show_cancelled && in_array($ev->status, ['cancelled', 'rejected'], true)) {
                return false;
            }
            return true;
        });

        $statusCounts = [
            'all' => $visiblePool->count(),
            'total_all' => $allEvents->count(),
            'no_response' => $allEvents->where('status', 'no_response')->count(),
            'on_hold' => $allEvents->where('status', 'on_hold')->count(),
            'draft' => $allEvents->where('status', 'draft')->count(),
            'confirmed' => $allEvents->where('status', 'confirmed')->count(),
            'completed' => $allEvents->where('status', 'completed')->count(),
            'cancelled' => $allEvents->whereIn('status', ['cancelled', 'rejected'])->count(),
            'rejected' => $allEvents->where('status', 'rejected')->count(),
        ];

        $urgentNoResponseCount = $allEvents->where('status', 'no_response')->filter(function($ev) {
            return $ev->days_without_response >= 2;
        })->count();

        $expiredOnHoldCount = $allEvents->where('status', 'on_hold')->filter(function($ev) {
            return $ev->days_on_hold >= 30;
        })->count();

        if ($this->status_filter === 'all') {
            $filteredEvents = $visiblePool->values();
        } elseif ($this->status_filter === 'cancelled') {
            $filteredEvents = $allEvents->whereIn('status', ['cancelled', 'rejected'])->values();
        } else {
            $filteredEvents = $allEvents->where('status', $this->status_filter)->values();
        }

        $this->events = $filteredEvents;

        return view('livewire.admin.event-manager', [
            'events' => $filteredEvents,
            'statusCounts' => $statusCounts,
            'urgentNoResponseCount' => $urgentNoResponseCount,
            'expiredOnHoldCount' => $expiredOnHoldCount,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Eventos']);
    }
}
