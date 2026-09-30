<?php

namespace App\Livewire\Guest;

use Livewire\Component;
use App\Models\User;
use App\Models\Event;
use App\Models\Setting;
use Illuminate\Support\Str;

class QuoteCalculator extends Component
{
    // Contact Info
    public $client_name = '';
    public $client_email = '';
    public $client_phone = '';
    public $event_date = '';
    public $event_location = '';
    public $client_notes = '';

    // Services selected by the user (No pricing in state to avoid leaking in wire:snapshot)
    public $services = [];
    public $is_submitted = false;

    public function mount()
    {
        $this->services = [
            'ceremony' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '💍',
                'name' => 'Ceremonia Civil / Religiosa', 
                'description' => 'Sonorización profesional, microfonía inalámbrica y música de momentos (entrada, anillos, firmas, salida).'
            ],
            'cocktail' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🍸',
                'name' => 'Cóctel de Bienvenida', 
                'description' => 'Música ambiente seleccionada y equipo de sonido independiente para la zona de bienvenida.'
            ],
            'restaurant' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🍽️',
                'name' => 'Banquete & Momentos Especiales', 
                'description' => 'Música ambiente en comedor, entrada de novios, entrega de regalos, ramo y corte de tarta.'
            ],
            'dj' => [
                'selected' => true,  
                'quantity' => 4, 
                'icon' => '💃',
                'name' => 'DJ Baile & Barra Libre', 
                'description' => 'Sesión de DJ en directo, equipo de sonido de alta fidelidad, puente de luces y efectos.'
            ],
            'karaoke' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '🎤',
                'name' => 'Animación & Karaoke', 
                'description' => 'Catálogo interactivo de canciones y micrófonos para cantar durante la fiesta.'
            ],
            'extra_hours' => [
                'selected' => false, 
                'quantity' => 1, 
                'icon' => '⏰',
                'name' => 'Horas Adicionales de Fiesta', 
                'description' => 'Ampliación de horario para continuar el baile sin interrupciones.'
            ],
        ];
    }

    /**
     * Get internal price catalog from settings
     */
    protected function getPriceCatalog(): array
    {
        return [
            'ceremony' => (float) Setting::get('price_ceremony', 180),
            'cocktail' => (float) Setting::get('price_cocktail', 150),
            'restaurant' => (float) Setting::get('price_restaurant', 150),
            'dj' => (float) Setting::get('price_dj', 150),
            'karaoke' => (float) Setting::get('price_karaoke', 80),
            'extra_hours' => (float) Setting::get('price_extra_hours', 120),
        ];
    }

    /**
     * Check if both Cocktail and Restaurant are selected
     */
    public function getHasCocktailRestaurantPackProperty(): bool
    {
        return !empty($this->services['cocktail']['selected']) && !empty($this->services['restaurant']['selected']);
    }

    /**
     * Get Cocktail + Restaurant combined pack settings and calculations
     */
    public function getCocktailRestaurantPackInfoProperty(): array
    {
        $catalog = $this->getPriceCatalog();
        return Setting::getCocktailRestaurantPackInfo($catalog['cocktail'] ?? 150, $catalog['restaurant'] ?? 150);
    }

    public function submitRequest()
    {
        $this->validate([
            'client_name' => 'required|string|max:255',
            'client_email' => 'required|email|max:255',
            'client_phone' => 'required|string|max:50',
            'event_date' => 'required|date',
            'event_location' => 'required|string|max:255',
            'client_notes' => 'nullable|string|max:1000'
        ]);

        $catalog = $this->getPriceCatalog();
        $totalEstimated = 0;

        // 1. Calculate internal total
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $price = $catalog[$key] ?? 0;
                $totalEstimated += ($price * $qty);
            }
        }

        // Apply Pack Cóctel + Banquete discount if active
        $packInfo = $this->has_cocktail_restaurant_pack ? $this->cocktail_restaurant_pack_info : null;
        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $totalEstimated = max(0, $totalEstimated - $packInfo['discount_amount']);
        }

        // 2. Create or Find Client
        $client = User::firstOrCreate(
            ['email' => $this->client_email],
            [
                'name' => $this->client_name,
                'password' => bcrypt(Str::random(16)),
                'role' => 'client',
                'phone' => $this->client_phone,
            ]
        );

        // 3. Format notes with requested services
        $requestedServicesList = [];
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $qtyText = $key === 'dj' ? " ({$qty} horas)" : ($qty > 1 ? " ({$qty} uds)" : "");
                $requestedServicesList[] = "• " . $service['name'] . $qtyText;
            }
        }

        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $requestedServicesList[] = "• 🎁 Pack Cóctel + Banquete incluido (" . $packInfo['savings_label'] . ")";
        }

        $fullNotes = "SOLICITUD DE PRESUPUESTO ONLINE:\n"
                   . implode("\n", $requestedServicesList)
                   . (!empty($this->client_notes) ? "\n\nObservaciones:\n" . $this->client_notes : "");

        // 4. Create Event Draft
        $event = Event::create([
            'name' => 'Boda ' . $this->client_name,
            'event_date' => $this->event_date,
            'location' => $this->event_location,
            'status' => 'draft',
            'client_id' => $client->id,
            'token' => Str::random(32),
            'notes' => $fullNotes,
        ]);

        // 5. Create Quote Draft for Admin
        $quote = $event->quotes()->create([
            'amount' => $totalEstimated,
            'status' => 'pending',
        ]);

        // 6. Create Quote Items
        foreach ($this->services as $key => $service) {
            if ($service['selected']) {
                $qty = (int)($service['quantity'] ?: 1);
                $price = $catalog[$key] ?? 0;
                $quote->items()->create([
                    'service_name' => $service['name'],
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $price * $qty,
                ]);
            }
        }

        if ($packInfo && !empty($packInfo['enabled']) && $packInfo['discount_amount'] > 0) {
            $quote->items()->create([
                'service_name' => 'Descuento Especial Pack Cóctel + Banquete',
                'description' => 'Tarifa combinada promocional por contratación conjunta de Cóctel y Banquete (' . round($packInfo['discount_percentage']) . '% dto.)',
                'quantity' => 1,
                'price' => -$packInfo['discount_amount'],
                'total' => -$packInfo['discount_amount'],
            ]);
        }

        $this->is_submitted = true;
    }

    public function render()
    {
        return view('livewire.guest.quote-calculator')
            ->layout('components.layouts.wide-guest', ['title' => 'Solicitar Presupuesto - ' . Setting::get('company_name', 'Eventos Musicales')]);
    }
}
