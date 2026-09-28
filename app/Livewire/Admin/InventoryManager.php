<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Equipment;

class InventoryManager extends Component
{
    public $equipmentList;
    public $search = '';
    public $filter_category = '';

    // Modal state
    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form fields
    public $name = '';
    public $category = 'Sonido';
    public $brand_model = '';
    public $quantity = 1;
    public $status = 'available';
    public $is_dmx = false;
    public $dmx_mode = '';
    public $dmx_address = '';
    public $notes = '';

    public $categories = [
        'Sonido',
        'Iluminación',
        'Estructuras',
        'Cables',
        'DJ',
        'Fotomatón',
        'Otros'
    ];

    public $statuses = [
        'available' => 'Disponible',
        'maintenance' => 'Mantenimiento / Reparación',
        'broken' => 'Roto',
        'lost' => 'Extraviado'
    ];

    public function mount()
    {
        $this->loadEquipment();
    }

    public function updatedSearch()
    {
        $this->loadEquipment();
    }

    public function updatedFilterCategory()
    {
        $this->loadEquipment();
    }

    public function loadEquipment()
    {
        $query = Equipment::query();

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('brand_model', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->filter_category) {
            $query->where('category', $this->filter_category);
        }

        $this->equipmentList = $query->orderBy('category')->orderBy('name')->get();
    }

    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $id;

        $equipment = Equipment::findOrFail($id);
        
        $this->name = $equipment->name;
        $this->category = $equipment->category;
        $this->brand_model = $equipment->brand_model;
        $this->quantity = $equipment->quantity;
        $this->status = $equipment->status;
        $this->is_dmx = $equipment->is_dmx;
        $this->dmx_mode = $equipment->dmx_mode;
        $this->dmx_address = $equipment->dmx_address;
        $this->notes = $equipment->notes;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'brand_model' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:0',
            'status' => 'required|string',
            'is_dmx' => 'boolean',
            'dmx_mode' => 'nullable|string|max:255',
            'dmx_address' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        if ($this->isEditing) {
            $equipment = Equipment::findOrFail($this->editingId);
            $equipment->update([
                'name' => $this->name,
                'category' => $this->category,
                'brand_model' => $this->brand_model,
                'quantity' => $this->quantity,
                'status' => $this->status,
                'is_dmx' => $this->category === 'Iluminación' ? $this->is_dmx : false,
                'dmx_mode' => ($this->category === 'Iluminación' && $this->is_dmx) ? $this->dmx_mode : null,
                'dmx_address' => ($this->category === 'Iluminación' && $this->is_dmx) ? $this->dmx_address : null,
                'notes' => $this->notes,
            ]);
            session()->flash('message', 'Material actualizado correctamente.');
        } else {
            Equipment::create([
                'name' => $this->name,
                'category' => $this->category,
                'brand_model' => $this->brand_model,
                'quantity' => $this->quantity,
                'status' => $this->status,
                'is_dmx' => $this->category === 'Iluminación' ? $this->is_dmx : false,
                'dmx_mode' => ($this->category === 'Iluminación' && $this->is_dmx) ? $this->dmx_mode : null,
                'dmx_address' => ($this->category === 'Iluminación' && $this->is_dmx) ? $this->dmx_address : null,
                'notes' => $this->notes,
            ]);
            session()->flash('message', 'Material añadido correctamente.');
        }

        $this->showModal = false;
        $this->loadEquipment();
    }

    public function delete($id)
    {
        Equipment::findOrFail($id)->delete();
        session()->flash('message', 'Material eliminado.');
        $this->loadEquipment();
    }
    
    public function updateQuantity($id, $amount)
    {
        $equipment = Equipment::findOrFail($id);
        $newQty = $equipment->quantity + $amount;
        if ($newQty >= 0) {
            $equipment->update(['quantity' => $newQty]);
            $this->loadEquipment();
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->category = 'Sonido';
        $this->brand_model = '';
        $this->quantity = 1;
        $this->status = 'available';
        $this->is_dmx = false;
        $this->dmx_mode = '';
        $this->dmx_address = '';
        $this->notes = '';
        $this->editingId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.admin.inventory-manager')
            ->layout('components.layouts.app', [
                'header' => 'Gestión de Inventario'
            ]);
    }
}
