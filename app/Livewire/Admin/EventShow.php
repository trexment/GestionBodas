<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Event;
use App\Models\User;
use App\Models\Contract;
use App\Models\Equipment;
use App\Models\EventMusicRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventShow extends Component
{
    use WithFileUploads;

    public Event $event;
    public $activeTab = 'dossier'; // dossier, music, equipment, quotes, contracts, invoices

    // Personal asignado (DJ y Asistente)
    public $assigned_dj_id;
    public $assigned_assistant_id;
    public $showStaffModal = false;

    // Facturas
    public $invoice_number;
    public $invoice_amount;
    public $invoice_tax;
    public $invoice_issue_date;

    // Notas de Evento / Acuerdos WhatsApp
    public $event_notes;
    public $whatsapp_chat_input = '';
    public $selected_pack_type = 'custom'; // basic, medium, premium, wedding_dj, wedding_full, custom

    // Configuración de señal específica para este presupuesto
    public $quote_deposit_type = 'percentage'; // 'percentage' | 'fixed'
    public $quote_deposit_percentage = 40;
    public $quote_deposit_fixed_amount = 200;
    public $editing_quote_id = null; // ID de la propuesta actualmente en edición

    // Configuración de IVA y Métodos de Pago
    public $quote_tax_type = 'included'; // 'included' (IVA incluido), 'excluded' (Base + 21% IVA), 'none' (Sin IVA / Exento)
    public $quote_tax_rate = 21.00;
    public $quote_payment_methods = ['transfer', 'bizum', 'cash']; // transfer, bizum, cash, card

    // Presupuestos & Packs
    public $quote_services = [
        'pack_basic' => ['selected' => false, 'name' => 'Pack Básico', 'price' => 400, 'hours' => 4, 'desc' => '4 Horas de servicio DJ, Equipo de sonido profesional e iluminación de pista básica.'],
        'pack_medium' => ['selected' => false, 'name' => 'Pack Medio (Recomendado)', 'price' => 700, 'hours' => 5, 'desc' => 'Hasta 5 Horas de servicio DJ, Sonido alta gama, Iluminación avanzada de pista + Máquina de humo.'],
        'pack_premium' => ['selected' => false, 'name' => 'Pack Premium', 'price' => 1000, 'hours' => 6, 'desc' => 'Hasta 6 Horas de servicio DJ, Iluminación profesional gran potencia + Efectos especiales + Fuego frío.'],
        'ceremony' => ['selected' => false, 'quantity' => 1, 'price' => 180, 'desc' => 'Sonorización + Música ambiente + Micrófonos inalámbricos.'],
        'cocktail' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Música ambiente durante el cóctel.'],
        'restaurant' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Música ambiente + Sonorización de regalos y detalles en el banquete.'],
        'dj_custom' => ['selected' => false, 'quantity' => 2, 'price' => 150, 'desc' => 'Horas de DJ en directo.'],
        'extra_hours' => ['quantity' => 0, 'price' => 120, 'desc' => 'Horas adicionales de baile.'],
        'photo_ceremony' => ['selected' => false, 'quantity' => 1, 'price' => 250, 'desc' => 'Fotografía: Cobertura completa de la Ceremonia.'],
        'photo_restaurant' => ['selected' => false, 'quantity' => 1, 'price' => 250, 'desc' => 'Fotografía: Banquete y momentos emotivos con invitados.'],
        'photo_party' => ['selected' => false, 'quantity' => 1, 'price' => 300, 'desc' => 'Fotografía: Baile nupcial y fiesta.'],
        'photo_album' => ['selected' => false, 'quantity' => 1, 'price' => 150, 'desc' => 'Maquetación álbum digital profesional (sin imprimir).'],
        'photo_full_pack' => ['selected' => false, 'quantity' => 1, 'price' => 800, 'desc' => 'Fotografía Completa: Ceremonia, Restaurante, Fiesta, Álbum digital maquetado, fotos en alta calidad sin marcas de agua.'],
        'karaoke' => ['selected' => false, 'quantity' => 1, 'price' => 80, 'desc' => 'Karaoke interactivo para invitados.'],
        'custom_extra' => ['name' => '', 'price' => 0, 'desc' => ''],
    ];

    // Dossier form
    public $dossier_content;
    public $is_dossier_completed;

    // Material / Equipment form
    public $selected_equipment_id = '';
    public $equipment_quantity = 1;
    public $equipment_notes = '';

    // Music & Moments form
    public $music_active_category = 'all'; // all, ceremonia, coctel, banquete, baile, lista_negra
    public $showSongModal = false;
    public $editingSongId = null;
    public $req_category = 'banquete';
    public $req_moment = 'Entrada Comedor';
    public $req_title = '';
    public $req_artist = '';
    public $req_requested_by = '';
    public $req_notes = '';
    public $req_youtube_url = '';
    public $req_spotify_url = '';
    public $req_apple_music_url = '';
    public $req_audio_file = null;
    public $existing_audio_file = null;
    public $req_cue_time = '';
    public $req_status = 'pending';

    public function mount(Event $event)
    {
        $user = auth()->user();
        if ($user->role !== 'admin' && $event->dj_id !== $user->id && $event->assistant_id !== $user->id) {
            abort(403, 'Acceso denegado: No estás asignado a este evento.');
        }

        $this->event = $event->load(['client', 'dj', 'assistant', 'invoices', 'quotes.items', 'contracts', 'dossiers', 'equipment', 'musicRequests']);
        $this->assigned_dj_id = $this->event->dj_id;
        $this->assigned_assistant_id = $this->event->assistant_id;
        $this->is_dossier_completed = $this->event->is_dossier_completed;
        $this->event_notes = $this->event->notes;
        
        $dossier = $this->event->dossiers()->first();
        if ($dossier) {
            $this->dossier_content = $dossier->content;
        }

        $this->loadSettingsPrices();
    }

    public function loadSettingsPrices()
    {
        // Packs
        $this->quote_services['pack_basic']['name'] = \App\Models\Setting::get('pack_basic_name', 'Pack Básico');
        $this->quote_services['pack_basic']['price'] = (float)\App\Models\Setting::get('pack_basic_price', 400);
        $this->quote_services['pack_basic']['hours'] = (int)\App\Models\Setting::get('pack_basic_hours', 4);
        $this->quote_services['pack_basic']['desc'] = \App\Models\Setting::get('pack_basic_desc', '4 Horas de servicio DJ, Equipo de sonido profesional e iluminación de pista básica.');

        $this->quote_services['pack_medium']['name'] = \App\Models\Setting::get('pack_medium_name', 'Pack Medio (Recomendado)');
        $this->quote_services['pack_medium']['price'] = (float)\App\Models\Setting::get('pack_medium_price', 700);
        $this->quote_services['pack_medium']['hours'] = (int)\App\Models\Setting::get('pack_medium_hours', 5);
        $this->quote_services['pack_medium']['desc'] = \App\Models\Setting::get('pack_medium_desc', 'Hasta 5 Horas de servicio DJ, Sonido alta gama, Iluminación avanzada de pista + Máquina de humo.');

        $this->quote_services['pack_premium']['name'] = \App\Models\Setting::get('pack_premium_name', 'Pack Premium');
        $this->quote_services['pack_premium']['price'] = (float)\App\Models\Setting::get('pack_premium_price', 1000);
        $this->quote_services['pack_premium']['hours'] = (int)\App\Models\Setting::get('pack_premium_hours', 6);
        $this->quote_services['pack_premium']['desc'] = \App\Models\Setting::get('pack_premium_desc', 'Hasta 6 Horas de servicio DJ, Iluminación profesional gran potencia + Efectos especiales + Fuego frío.');

        // Servicios individuales
        $this->quote_services['ceremony']['price'] = (float)\App\Models\Setting::get('price_ceremony', 180);
        $this->quote_services['cocktail']['price'] = (float)\App\Models\Setting::get('price_cocktail', 150);
        $this->quote_services['restaurant']['price'] = (float)\App\Models\Setting::get('price_restaurant', 150);
        $this->quote_services['dj_custom']['price'] = (float)\App\Models\Setting::get('price_dj', 150);
        $this->quote_services['karaoke']['price'] = (float)\App\Models\Setting::get('price_karaoke', 80);
        $this->quote_services['extra_hours']['price'] = (float)\App\Models\Setting::get('price_extra_hours', 120);

        // Fotografía
        $this->quote_services['photo_ceremony']['price'] = (float)\App\Models\Setting::get('price_photo_ceremony', 250);
        $this->quote_services['photo_restaurant']['price'] = (float)\App\Models\Setting::get('price_photo_restaurant', 250);
        $this->quote_services['photo_party']['price'] = (float)\App\Models\Setting::get('price_photo_party', 300);
        $this->quote_services['photo_album']['price'] = (float)\App\Models\Setting::get('price_photo_album', 150);
        $this->quote_services['photo_full_pack']['price'] = (float)\App\Models\Setting::get('price_photo_full_pack', 800);

        // Condiciones de Señal
        $this->quote_deposit_type = \App\Models\Setting::get('deposit_type', 'percentage');
        $this->quote_deposit_percentage = (float)\App\Models\Setting::get('deposit_percentage', 40);
        $this->quote_deposit_fixed_amount = (float)\App\Models\Setting::get('deposit_fixed_amount', 200);

        // Condiciones de IVA y Métodos de Pago
        $this->quote_tax_type = \App\Models\Setting::get('default_tax_type', 'included');
        $this->quote_tax_rate = (float)\App\Models\Setting::get('default_tax_rate', 21.00);
        $savedPm = \App\Models\Setting::get('default_payment_methods');
        if ($savedPm) {
            $decoded = json_decode($savedPm, true);
            if (is_array($decoded) && !empty($decoded)) {
                $this->quote_payment_methods = $decoded;
            }
        }
    }

    public function changeStatus($status)
    {
        $this->event->update(['status' => $status]);
        session()->flash('message', 'Estado del evento actualizado a: ' . $status);
    }

    public function openStaffModal()
    {
        $this->assigned_dj_id = $this->event->dj_id;
        $this->assigned_assistant_id = $this->event->assistant_id;
        $this->showStaffModal = true;
    }

    public function saveStaff()
    {
        $this->validate([
            'assigned_dj_id' => 'nullable|exists:users,id',
            'assigned_assistant_id' => 'nullable|exists:users,id',
        ]);

        $this->event->update([
            'dj_id' => $this->assigned_dj_id ?: null,
            'assistant_id' => $this->assigned_assistant_id ?: null,
        ]);

        $this->event->load(['dj', 'assistant']);
        $this->showStaffModal = false;
        session()->flash('message', 'Personal asignado (DJ y Asistente) actualizado correctamente.');
    }

    public function saveDossier()
    {
        $this->event->update(['is_dossier_completed' => $this->is_dossier_completed]);

        $dossier = $this->event->dossiers()->first();
        if ($dossier) {
            $dossier->update(['content' => $this->dossier_content]);
        } else {
            $this->event->dossiers()->create(['content' => $this->dossier_content]);
        }
        session()->flash('dossier_message', 'Dossier guardado correctamente.');
    }

    // Material / Hoja de Carga Methods
    public function addEquipment()
    {
        $this->validate([
            'selected_equipment_id' => 'required|exists:equipment,id',
            'equipment_quantity' => 'required|integer|min:1',
            'equipment_notes' => 'nullable|string|max:255',
        ]);

        $existing = DB::table('event_equipment')
            ->where('event_id', $this->event->id)
            ->where('equipment_id', $this->selected_equipment_id)
            ->first();

        if ($existing) {
            DB::table('event_equipment')
                ->where('id', $existing->id)
                ->update([
                    'quantity' => $existing->quantity + $this->equipment_quantity,
                    'notes' => $this->equipment_notes ?: $existing->notes,
                    'updated_at' => now(),
                ]);
        } else {
            $this->event->equipment()->attach($this->selected_equipment_id, [
                'quantity' => $this->equipment_quantity,
                'notes' => $this->equipment_notes,
            ]);
        }

        $this->reset(['selected_equipment_id', 'equipment_quantity', 'equipment_notes']);
        $this->equipment_quantity = 1;
        $this->event->load('equipment');
        session()->flash('equipment_message', 'Material añadido al evento correctamente.');
    }

    public function updateEquipmentQuantity($pivotId, $amount)
    {
        $item = DB::table('event_equipment')->where('id', $pivotId)->first();
        if ($item) {
            $newQty = $item->quantity + $amount;
            if ($newQty > 0) {
                DB::table('event_equipment')->where('id', $pivotId)->update([
                    'quantity' => $newQty,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('event_equipment')->where('id', $pivotId)->delete();
            }
            $this->event->load('equipment');
        }
    }

    public function removeEquipment($pivotId)
    {
        DB::table('event_equipment')->where('id', $pivotId)->delete();
        $this->event->load('equipment');
        session()->flash('equipment_message', 'Material eliminado del evento.');
    }

    // ==================== MÚSICA Y MOMENTOS ====================

    public function openSongModal($songId = null)
    {
        $this->resetValidation();
        $this->reset([
            'req_title', 'req_artist', 'req_requested_by', 'req_notes',
            'req_youtube_url', 'req_spotify_url', 'req_apple_music_url', 'req_audio_file', 'existing_audio_file', 'req_cue_time'
        ]);

        if ($songId) {
            $song = EventMusicRequest::findOrFail($songId);
            $this->editingSongId = $song->id;
            $this->req_category = $song->category;
            $this->req_moment = $song->moment;
            $this->req_title = $song->title;
            $this->req_artist = $song->artist;
            $this->req_requested_by = $song->requested_by;
            $this->req_notes = $song->notes;
            $this->req_youtube_url = $song->youtube_url;
            $this->req_spotify_url = $song->spotify_url;
            $this->req_apple_music_url = $song->apple_music_url;
            $this->existing_audio_file = $song->audio_file;
            $this->req_cue_time = $song->cue_time;
            $this->req_status = $song->status;
        } else {
            $this->editingSongId = null;
            $this->req_category = $this->music_active_category !== 'all' ? $this->music_active_category : 'banquete';
            $this->req_moment = match($this->req_category) {
                'ceremonia' => 'Entrada Novios',
                'coctel' => 'Música Ambiente',
                'banquete' => 'Entrada Comedor',
                'baile' => 'Baile Nupcial',
                'lista_negra' => 'Prohibida',
                default => 'General',
            };
            $this->req_status = 'pending';
        }

        $this->showSongModal = true;
    }

    public function saveSongRequest()
    {
        $this->validate([
            'req_title' => 'required|string|max:255',
            'req_category' => 'required|string|in:ceremonia,coctel,banquete,baile,lista_negra',
            'req_moment' => 'required|string|max:255',
            'req_artist' => 'nullable|string|max:255',
            'req_requested_by' => 'nullable|string|max:255',
            'req_notes' => 'nullable|string',
            'req_youtube_url' => 'nullable|string|max:255',
            'req_spotify_url' => 'nullable|string|max:255',
            'req_apple_music_url' => 'nullable|string|max:255',
            'req_audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a|max:25600', // 25MB max
            'req_cue_time' => 'nullable|string|max:50',
            'req_status' => 'required|string|in:pending,ready,played',
        ]);

        $audioPath = $this->existing_audio_file;
        if ($this->req_audio_file) {
            $audioPath = $this->req_audio_file->store('music_audios', 'public');
        }

        if ($this->editingSongId) {
            $song = EventMusicRequest::findOrFail($this->editingSongId);
            $song->update([
                'category' => $this->req_category,
                'moment' => $this->req_moment,
                'title' => $this->req_title,
                'artist' => $this->req_artist,
                'requested_by' => $this->req_requested_by,
                'notes' => $this->req_notes,
                'youtube_url' => $this->req_youtube_url,
                'spotify_url' => $this->req_spotify_url,
                'apple_music_url' => $this->req_apple_music_url,
                'audio_file' => $audioPath,
                'cue_time' => $this->req_cue_time,
                'status' => $this->req_status,
            ]);
            session()->flash('music_message', 'Canción/Momento actualizado correctamente.');
        } else {
            $maxOrder = EventMusicRequest::where('event_id', $this->event->id)->max('order') ?: 0;
            $this->event->musicRequests()->create([
                'category' => $this->req_category,
                'moment' => $this->req_moment,
                'title' => $this->req_title,
                'artist' => $this->req_artist,
                'requested_by' => $this->req_requested_by,
                'notes' => $this->req_notes,
                'youtube_url' => $this->req_youtube_url,
                'spotify_url' => $this->req_spotify_url,
                'apple_music_url' => $this->req_apple_music_url,
                'audio_file' => $audioPath,
                'cue_time' => $this->req_cue_time,
                'status' => $this->req_status,
                'order' => $maxOrder + 1,
            ]);
            session()->flash('music_message', 'Canción/Momento añadido a la escaleta.');
        }

        $this->showSongModal = false;
        $this->event->load('musicRequests');
    }

    public function toggleSongStatus($songId)
    {
        $song = EventMusicRequest::findOrFail($songId);
        $nextStatus = match($song->status) {
            'pending' => 'ready',
            'ready' => 'played',
            'played' => 'pending',
            default => 'pending',
        };
        $song->update(['status' => $nextStatus]);
        $this->event->load('musicRequests');
    }

    public function deleteSongRequest($songId)
    {
        $song = EventMusicRequest::findOrFail($songId);
        if ($song->audio_file) {
            Storage::disk('public')->delete($song->audio_file);
        }
        $song->delete();
        $this->event->load('musicRequests');
        session()->flash('music_message', 'Canción eliminada de la escaleta.');
    }

    public function moveSongOrder($songId, $direction)
    {
        $song = EventMusicRequest::findOrFail($songId);
        if ($direction === 'up') {
            $prev = EventMusicRequest::where('event_id', $this->event->id)
                ->where('order', '<', $song->order)
                ->orderBy('order', 'desc')
                ->first();
            if ($prev) {
                $temp = $song->order;
                $song->update(['order' => $prev->order]);
                $prev->update(['order' => $temp]);
            }
        } elseif ($direction === 'down') {
            $next = EventMusicRequest::where('event_id', $this->event->id)
                ->where('order', '>', $song->order)
                ->orderBy('order', 'asc')
                ->first();
            if ($next) {
                $temp = $song->order;
                $song->update(['order' => $next->order]);
                $next->update(['order' => $temp]);
            }
        }
        $this->event->load('musicRequests');
    }

    // ==================== FACTURAS Y PRESUPUESTOS ====================

    public function createInvoice()
    {
        $this->validate([
            'invoice_number' => 'required|string|unique:invoices,invoice_number',
            'invoice_amount' => 'required|numeric',
            'invoice_tax' => 'required|numeric',
            'invoice_issue_date' => 'required|date',
        ]);

        $total = $this->invoice_amount + $this->invoice_tax;

        $this->event->invoices()->create([
            'invoice_number' => $this->invoice_number,
            'amount' => $this->invoice_amount,
            'tax' => $this->invoice_tax,
            'total' => $total,
            'issue_date' => $this->invoice_issue_date,
            'status' => 'unpaid',
        ]);

        $this->reset(['invoice_number', 'invoice_amount', 'invoice_tax', 'invoice_issue_date']);
        $this->event->load('invoices');
        session()->flash('invoice_message', 'Factura creada exitosamente.');
    }

    public function saveEventNotes()
    {
        $this->event->update(['notes' => $this->event_notes]);
        $this->event->refresh();
        session()->flash('notes_message', 'Notas del evento y acuerdos actualizados correctamente.');
    }

    public function applyWhatsappTextToNotes()
    {
        if (empty(trim($this->whatsapp_chat_input))) {
            return;
        }

        $timestamp = now()->format('d/m/Y H:i');
        $newEntry = "--- Acuerdos WhatsApp ({$timestamp}) ---\n" . trim($this->whatsapp_chat_input);
        
        $this->event_notes = $this->event_notes ? ($this->event_notes . "\n\n" . $newEntry) : $newEntry;
        $this->event->update(['notes' => $this->event_notes]);
        $this->whatsapp_chat_input = '';
        $this->event->refresh();
        
        session()->flash('notes_message', 'Conversación / Acuerdos de WhatsApp añadidos a las notas del evento.');
    }

    public function selectPack($packKey)
    {
        $this->selected_pack_type = $packKey;

        // Reset packs
        $this->quote_services['pack_basic']['selected'] = false;
        $this->quote_services['pack_medium']['selected'] = false;
        $this->quote_services['pack_premium']['selected'] = false;
        $this->quote_services['dj_custom']['selected'] = false;

        if ($packKey === 'basic') {
            $this->quote_services['pack_basic']['selected'] = true;
        } elseif ($packKey === 'medium') {
            $this->quote_services['pack_medium']['selected'] = true;
        } elseif ($packKey === 'premium') {
            $this->quote_services['pack_premium']['selected'] = true;
        } elseif ($packKey === 'wedding_dj') {
            // Pack Boda Típico DJ: Cóctel + Banquete/Regalos + Baile 2h o Pack Medio
            $this->quote_services['cocktail']['selected'] = true;
            $this->quote_services['restaurant']['selected'] = true;
            $this->quote_services['dj_custom']['selected'] = true;
            $this->quote_services['dj_custom']['quantity'] = 2;
        } elseif ($packKey === 'wedding_full') {
            // Pack Boda Completo: Cóctel + Banquete + Baile 2h + Pack Completo Fotos
            $this->quote_services['cocktail']['selected'] = true;
            $this->quote_services['restaurant']['selected'] = true;
            $this->quote_services['dj_custom']['selected'] = true;
            $this->quote_services['dj_custom']['quantity'] = 2;
            $this->quote_services['photo_full_pack']['selected'] = true;
        } elseif ($packKey === 'clear') {
            foreach ($this->quote_services as $k => $v) {
                if (isset($this->quote_services[$k]['selected'])) {
                    $this->quote_services[$k]['selected'] = false;
                }
            }
            $this->quote_services['extra_hours']['quantity'] = 0;
            $this->quote_services['custom_extra']['name'] = '';
            $this->quote_services['custom_extra']['price'] = 0;
            $this->selected_pack_type = 'custom';
        }
    }

    public function getCalculatedGrossTotalProperty()
    {
        $total = 0;

        // Packs
        if (!empty($this->quote_services['pack_basic']['selected'])) {
            $total += (float)$this->quote_services['pack_basic']['price'];
        }
        if (!empty($this->quote_services['pack_medium']['selected'])) {
            $total += (float)$this->quote_services['pack_medium']['price'];
        }
        if (!empty($this->quote_services['pack_premium']['selected'])) {
            $total += (float)$this->quote_services['pack_premium']['price'];
        }

        // Servicios musicales
        if (!empty($this->quote_services['ceremony']['selected'])) {
            $total += (float)$this->quote_services['ceremony']['price'] * (int)($this->quote_services['ceremony']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['cocktail']['selected'])) {
            $total += (float)$this->quote_services['cocktail']['price'] * (int)($this->quote_services['cocktail']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['restaurant']['selected'])) {
            $total += (float)$this->quote_services['restaurant']['price'] * (int)($this->quote_services['restaurant']['quantity'] ?: 1);
        }
        if (!empty($this->quote_services['dj_custom']['selected'])) {
            $total += (float)$this->quote_services['dj_custom']['price'] * (int)($this->quote_services['dj_custom']['quantity'] ?: 1);
        }
        $extraH = (int)($this->quote_services['extra_hours']['quantity'] ?: 0);
        if ($extraH > 0) {
            $total += (float)$this->quote_services['extra_hours']['price'] * $extraH;
        }

        // Fotografía
        if (!empty($this->quote_services['photo_full_pack']['selected'])) {
            $total += (float)$this->quote_services['photo_full_pack']['price'] * (int)($this->quote_services['photo_full_pack']['quantity'] ?: 1);
        } else {
            if (!empty($this->quote_services['photo_ceremony']['selected'])) {
                $total += (float)$this->quote_services['photo_ceremony']['price'] * (int)($this->quote_services['photo_ceremony']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_restaurant']['selected'])) {
                $total += (float)$this->quote_services['photo_restaurant']['price'] * (int)($this->quote_services['photo_restaurant']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_party']['selected'])) {
                $total += (float)$this->quote_services['photo_party']['price'] * (int)($this->quote_services['photo_party']['quantity'] ?: 1);
            }
            if (!empty($this->quote_services['photo_album']['selected'])) {
                $total += (float)$this->quote_services['photo_album']['price'] * (int)($this->quote_services['photo_album']['quantity'] ?: 1);
            }
        }

        // Karaoke & Extra
        if (!empty($this->quote_services['karaoke']['selected'])) {
            $total += (float)$this->quote_services['karaoke']['price'] * (int)($this->quote_services['karaoke']['quantity'] ?: 1);
        }
        if (!empty(trim($this->quote_services['custom_extra']['name'] ?? ''))) {
            $total += (float)($this->quote_services['custom_extra']['price'] ?: 0);
        }

        return $total;
    }

    public function getCalculatedSubtotalProperty()
    {
        $gross = (float)$this->calculated_gross_total;
        $rate = (float)($this->quote_tax_rate ?: 21.00);

        if ($this->quote_tax_type === 'included') {
            return round($gross / (1 + ($rate / 100)), 2);
        }

        return $gross;
    }

    public function getCalculatedTaxAmountProperty()
    {
        $gross = (float)$this->calculated_gross_total;
        $rate = (float)($this->quote_tax_rate ?: 21.00);

        if ($this->quote_tax_type === 'included') {
            return round($gross - $this->calculated_subtotal, 2);
        }

        if ($this->quote_tax_type === 'excluded') {
            return round(($gross * $rate) / 100, 2);
        }

        return 0.00;
    }

    public function getCalculatedTotalProperty()
    {
        $gross = (float)$this->calculated_gross_total;

        if ($this->quote_tax_type === 'excluded') {
            return round($gross + $this->calculated_tax_amount, 2);
        }

        return $gross;
    }

    public function getCalculatedSignalProperty()
    {
        $total = (float)$this->calculated_total;
        if ($this->quote_deposit_type === 'fixed') {
            return min((float)$this->quote_deposit_fixed_amount, $total);
        }
        $pct = (float)($this->quote_deposit_percentage ?: 0);
        return round(($total * $pct) / 100, 2);
    }

    public function getCalculatedRemainingProperty()
    {
        return max(0, (float)$this->calculated_total - (float)$this->calculated_signal);
    }

    private function buildQuoteItemsList(&$total)
    {
        $total = 0;
        $items = [];

        // 1. Packs
        if (!empty($this->quote_services['pack_basic']['selected'])) {
            $p = (float)$this->quote_services['pack_basic']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_basic']['name'] . ' (' . $this->quote_services['pack_basic']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_basic']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }
        if (!empty($this->quote_services['pack_medium']['selected'])) {
            $p = (float)$this->quote_services['pack_medium']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_medium']['name'] . ' (' . $this->quote_services['pack_medium']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_medium']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }
        if (!empty($this->quote_services['pack_premium']['selected'])) {
            $p = (float)$this->quote_services['pack_premium']['price'];
            $items[] = [
                'service_name' => $this->quote_services['pack_premium']['name'] . ' (' . $this->quote_services['pack_premium']['hours'] . 'h DJ)',
                'description' => $this->quote_services['pack_premium']['desc'],
                'price' => $p,
                'quantity' => 1
            ];
            $total += $p;
        }

        // 2. Servicios Musicales Individuales
        if (!empty($this->quote_services['ceremony']['selected'])) {
            $qty = (int)($this->quote_services['ceremony']['quantity'] ?: 1);
            $p = (float)($this->quote_services['ceremony']['price'] ?: 0);
            $items[] = ['service_name' => 'Ceremonia', 'description' => $this->quote_services['ceremony']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        if (!empty($this->quote_services['cocktail']['selected'])) {
            $qty = (int)($this->quote_services['cocktail']['quantity'] ?: 1);
            $p = (float)($this->quote_services['cocktail']['price'] ?: 0);
            $items[] = ['service_name' => 'Música en el Cóctel', 'description' => $this->quote_services['cocktail']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        if (!empty($this->quote_services['restaurant']['selected'])) {
            $qty = (int)($this->quote_services['restaurant']['quantity'] ?: 1);
            $p = (float)($this->quote_services['restaurant']['price'] ?: 0);
            $items[] = ['service_name' => 'Sonorización Banquete y Regalos', 'description' => $this->quote_services['restaurant']['desc'], 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        if (!empty($this->quote_services['dj_custom']['selected'])) {
            $qty = (int)($this->quote_services['dj_custom']['quantity'] ?: 1);
            $p = (float)($this->quote_services['dj_custom']['price'] ?: 0);
            $items[] = ['service_name' => 'Baile DJ (' . $qty . ' Horas)', 'description' => 'Actuación DJ en directo con repertorio a medida (ampliable durante el evento)', 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        
        $eh_qty = (int)($this->quote_services['extra_hours']['quantity'] ?: 0);
        if ($eh_qty > 0) {
            $p = (float)($this->quote_services['extra_hours']['price'] ?: 0);
            $items[] = ['service_name' => 'Horas Extra de Baile (' . $eh_qty . 'h)', 'description' => 'Horas adicionales sobre el horario pactado', 'price' => $p, 'quantity' => $eh_qty];
            $total += ($p * $eh_qty);
        }

        // 3. Fotografía
        if (!empty($this->quote_services['photo_full_pack']['selected'])) {
            $p = (float)($this->quote_services['photo_full_pack']['price'] ?: 0);
            $items[] = ['service_name' => 'Pack Completo Fotografía', 'description' => 'Cobertura completa: Ceremonia, Restaurante, Fiesta, Maquetación álbum digital, todas las fotos en alta calidad y sin marcas de agua.', 'price' => $p, 'quantity' => 1];
            $total += $p;
        } else {
            if (!empty($this->quote_services['photo_ceremony']['selected'])) {
                $p = (float)($this->quote_services['photo_ceremony']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Ceremonia', 'description' => 'Cobertura fotográfica de la ceremonia en alta resolución.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_restaurant']['selected'])) {
                $p = (float)($this->quote_services['photo_restaurant']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Restaurante / Banquete', 'description' => 'Cobertura de momentos emotivos y regalos durante el banquete.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_party']['selected'])) {
                $p = (float)($this->quote_services['photo_party']['price'] ?: 0);
                $items[] = ['service_name' => 'Fotografía: Fiesta y Baile', 'description' => 'Fotografías del baile nupcial y fiesta de invitados.', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
            if (!empty($this->quote_services['photo_album']['selected'])) {
                $p = (float)($this->quote_services['photo_album']['price'] ?: 0);
                $items[] = ['service_name' => 'Maquetación Álbum Digital', 'description' => 'Maquetación profesional de álbum digital (sin imprimir, opciones de impresión a parte).', 'price' => $p, 'quantity' => 1];
                $total += $p;
            }
        }
        
        // 4. Karaoke & Extra personalizado
        if (!empty($this->quote_services['karaoke']['selected'])) {
            $qty = (int)($this->quote_services['karaoke']['quantity'] ?: 1);
            $p = (float)($this->quote_services['karaoke']['price'] ?: 0);
            $items[] = ['service_name' => 'Karaoke & Animación', 'description' => 'Micrófonos inalámbricos y catálogo de canciones para invitados', 'price' => $p, 'quantity' => $qty];
            $total += ($p * $qty);
        }
        
        $custom_name = trim($this->quote_services['custom_extra']['name'] ?? '');
        if (!empty($custom_name)) {
            $p = (float)($this->quote_services['custom_extra']['price'] ?: 0);
            $items[] = ['service_name' => $custom_name, 'description' => 'Servicio extra personalizado acordado', 'price' => $p, 'quantity' => 1];
            $total += $p;
        }

        return $items;
    }

    public function createQuote()
    {
        $grossTotal = 0;
        $items = $this->buildQuoteItemsList($grossTotal);

        if (empty($items)) {
            session()->flash('quote_error', 'Debes seleccionar al menos un Pack o Servicio para generar la propuesta comercial.');
            return;
        }

        $signal = $this->calculated_signal;
        $subtotal = $this->calculated_subtotal;
        $taxAmount = $this->calculated_tax_amount;
        $finalTotal = $this->calculated_total;

        $quote = $this->event->quotes()->create([
            'amount' => $finalTotal,
            'status' => 'pending',
            'deposit_type' => $this->quote_deposit_type,
            'deposit_percentage' => $this->quote_deposit_type === 'percentage' ? (float)$this->quote_deposit_percentage : null,
            'deposit_amount' => $signal,
            'tax_type' => $this->quote_tax_type,
            'tax_rate' => (float)$this->quote_tax_rate,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'payment_methods' => $this->quote_payment_methods,
        ]);

        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        $this->editing_quote_id = null;
        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Nueva Propuesta Comercial #' . $quote->id . ' generada exitosamente (Total: ' . number_format($finalTotal, 2, ',', '.') . ' € | Señal de reserva: ' . number_format($signal, 2, ',', '.') . ' €).');
    }

    public function updateQuote()
    {
        if (!$this->editing_quote_id) {
            $this->createQuote();
            return;
        }

        $quote = \App\Models\Quote::findOrFail($this->editing_quote_id);
        
        $grossTotal = 0;
        $items = $this->buildQuoteItemsList($grossTotal);

        if (empty($items)) {
            session()->flash('quote_error', 'Debes seleccionar al menos un Pack o Servicio para la propuesta comercial.');
            return;
        }

        $signal = $this->calculated_signal;
        $subtotal = $this->calculated_subtotal;
        $taxAmount = $this->calculated_tax_amount;
        $finalTotal = $this->calculated_total;

        $quote->update([
            'amount' => $finalTotal,
            'deposit_type' => $this->quote_deposit_type,
            'deposit_percentage' => $this->quote_deposit_type === 'percentage' ? (float)$this->quote_deposit_percentage : null,
            'deposit_amount' => $signal,
            'tax_type' => $this->quote_tax_type,
            'tax_rate' => (float)$this->quote_tax_rate,
            'subtotal_amount' => $subtotal,
            'tax_amount' => $taxAmount,
            'payment_methods' => $this->quote_payment_methods,
        ]);

        $quote->items()->delete();
        foreach ($items as $item) {
            $quote->items()->create($item);
        }

        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Propuesta Comercial #' . $quote->id . ' actualizada correctamente (Total: ' . number_format($finalTotal, 2, ',', '.') . ' € | Señal: ' . number_format($signal, 2, ',', '.') . ' €).');
    }

    public function loadQuoteIntoCalculator($quoteId)
    {
        $quote = \App\Models\Quote::with('items')->findOrFail($quoteId);
        $this->editing_quote_id = $quote->id;
        
        // Cargar configuración de señal
        $this->quote_deposit_type = $quote->deposit_type ?: 'percentage';
        if ($this->quote_deposit_type === 'percentage') {
            $this->quote_deposit_percentage = (float)($quote->deposit_percentage ?: 40);
        } else {
            $this->quote_deposit_fixed_amount = (float)($quote->deposit_amount ?: 200);
        }

        // Cargar configuración de IVA y Métodos de Pago
        $this->quote_tax_type = $quote->tax_type ?: 'included';
        $this->quote_tax_rate = (float)($quote->tax_rate ?: 21.00);
        $this->quote_payment_methods = $quote->active_payment_methods;

        // Limpiar selección previa
        $this->selectPack('clear');
        $this->selected_pack_type = 'custom';

        // Mapear cada línea
        foreach ($quote->items as $item) {
            $name = mb_strtolower($item->service_name ?: $item->concept ?: '');
            
            if (str_contains($name, 'pack básico') || str_contains($name, 'pack basico')) {
                $this->quote_services['pack_basic']['selected'] = true;
                $this->quote_services['pack_basic']['price'] = (float)$item->price;
                $this->selected_pack_type = 'basic';
            } elseif (str_contains($name, 'pack medio')) {
                $this->quote_services['pack_medium']['selected'] = true;
                $this->quote_services['pack_medium']['price'] = (float)$item->price;
                $this->selected_pack_type = 'medium';
            } elseif (str_contains($name, 'pack premium')) {
                $this->quote_services['pack_premium']['selected'] = true;
                $this->quote_services['pack_premium']['price'] = (float)$item->price;
                $this->selected_pack_type = 'premium';
            } elseif (str_contains($name, 'ceremonia') && !str_contains($name, 'foto')) {
                $this->quote_services['ceremony']['selected'] = true;
                $this->quote_services['ceremony']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['ceremony']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'cóctel') || str_contains($name, 'coctel')) {
                $this->quote_services['cocktail']['selected'] = true;
                $this->quote_services['cocktail']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['cocktail']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'banquete') || str_contains($name, 'regalos') || (str_contains($name, 'restaurante') && !str_contains($name, 'foto'))) {
                $this->quote_services['restaurant']['selected'] = true;
                $this->quote_services['restaurant']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['restaurant']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'baile dj') || str_contains($name, 'dj baile') || (str_contains($name, 'dj') && !str_contains($name, 'pack'))) {
                $this->quote_services['dj_custom']['selected'] = true;
                $this->quote_services['dj_custom']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['dj_custom']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'hora extra') || str_contains($name, 'horas extra')) {
                $this->quote_services['extra_hours']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['extra_hours']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'pack completo foto') || str_contains($name, 'completo fotografía') || str_contains($name, 'completo fotografia')) {
                $this->quote_services['photo_full_pack']['selected'] = true;
                $this->quote_services['photo_full_pack']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: ceremonia') || str_contains($name, 'fotografia: ceremonia')) {
                $this->quote_services['photo_ceremony']['selected'] = true;
                $this->quote_services['photo_ceremony']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: restaurante') || str_contains($name, 'fotografia: restaurante') || str_contains($name, 'fotografía: banquete')) {
                $this->quote_services['photo_restaurant']['selected'] = true;
                $this->quote_services['photo_restaurant']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'fotografía: fiesta') || str_contains($name, 'fotografia: fiesta') || str_contains($name, 'fotografía: baile')) {
                $this->quote_services['photo_party']['selected'] = true;
                $this->quote_services['photo_party']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'álbum digital') || str_contains($name, 'album digital')) {
                $this->quote_services['photo_album']['selected'] = true;
                $this->quote_services['photo_album']['price'] = (float)$item->price;
            } elseif (str_contains($name, 'karaoke')) {
                $this->quote_services['karaoke']['selected'] = true;
                $this->quote_services['karaoke']['quantity'] = (int)($item->quantity ?: 1);
                $this->quote_services['karaoke']['price'] = (float)$item->price;
            } else {
                $this->quote_services['custom_extra']['name'] = $item->service_name ?: $item->concept ?: '';
                $this->quote_services['custom_extra']['price'] = (float)$item->price;
            }
        }

        session()->flash('quote_message', 'Propuesta #' . $quote->id . ' cargada en el configurador. Puedes realizar cambios y pulsar "Guardar Cambios" o "Guardar como Nueva Versión".');
    }

    public function cancelQuoteEditing()
    {
        $this->editing_quote_id = null;
        $this->selectPack('clear');
        session()->flash('quote_message', 'Edición cancelada.');
    }

    public function deleteQuote($quoteId)
    {
        $quote = \App\Models\Quote::findOrFail($quoteId);
        $quote->items()->delete();
        $quote->delete();

        if ($this->editing_quote_id === $quoteId) {
            $this->editing_quote_id = null;
        }

        $this->event->load('quotes.items');
        session()->flash('quote_message', 'Propuesta comercial eliminada correctamente.');
    }

    public function createContract()
    {
        $token = \Illuminate\Support\Str::random(32);
        $this->event->contracts()->create([
            'status' => 'pending',
            'token' => $token,
        ]);
        $this->event->load('contracts');
        session()->flash('contract_message', 'Contrato borrador generado con enlace para firma online.');
    }

    public function signContract($contractId)
    {
        $contract = Contract::find($contractId);
        if ($contract) {
            $contract->update([
                'status' => 'signed',
                'signed_at' => now(),
            ]);
            $this->event->load('contracts');
        }
    }

    public function render()
    {
        $allEquipment = Equipment::orderBy('category')->orderBy('name')->get();
        $allDjs = User::whereIn('role', ['dj', 'admin'])->orderBy('name')->get();
        $allAssistants = User::whereIn('role', ['assistant', 'dj', 'admin'])->orderBy('name')->get();

        $filteredMusicRequests = $this->event->musicRequests;
        if ($this->music_active_category !== 'all') {
            $filteredMusicRequests = $filteredMusicRequests->where('category', $this->music_active_category);
        }

        return view('livewire.admin.event-show', [
            'allEquipment' => $allEquipment,
            'allDjs' => $allDjs,
            'allAssistants' => $allAssistants,
            'filteredMusicRequests' => $filteredMusicRequests,
        ])->layout('components.layouts.app', [
            'header' => 'Gestionar Evento: ' . $this->event->name
        ]);
    }
}
