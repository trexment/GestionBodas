<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Equipment;

/**
 * Componente Livewire para la gestión del inventario de equipos de sonido, iluminación, estructuras y material técnico.
 */
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

    /**
     * Inicializa el componente cargando el listado de material del inventario.
     *
     * @return void
     */
    public function mount()
    {
        $this->loadEquipment();
    }

    /**
     * Actualiza el listado de equipos cuando cambia el término de búsqueda.
     *
     * @return void
     */
    public function updatedSearch()
    {
        $this->loadEquipment();
    }

    /**
     * Actualiza el listado de equipos cuando cambia el filtro de categoría.
     *
     * @return void
     */
    public function updatedFilterCategory()
    {
        $this->loadEquipment();
    }

    /**
     * Consulta y filtra los equipos del inventario por búsqueda y categoría, ordenándolos alfabéticamente.
     *
     * @return void
     */
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

    /**
     * Reinicia el formulario y abre el modal para registrar un nuevo equipo en el inventario.
     *
     * @return void
     */
    public function create()
    {
        $this->resetForm();
        $this->isEditing = false;
        $this->showModal = true;
    }

    /**
     * Carga los datos de un equipo existente en el formulario y abre el modal en modo edición.
     *
     * @param int|string $id Identificador del equipo.
     * @return void
     */
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

    /**
     * Valida los campos del formulario y crea o actualiza el registro del equipo en la base de datos.
     *
     * @return void
     */
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

        $allowDmx = in_array($this->category, ['Iluminación', 'Otros']);
        $isDmx = $allowDmx ? (bool)$this->is_dmx : false;

        if ($this->isEditing) {
            $equipment = Equipment::findOrFail($this->editingId);
            $equipment->update([
                'name' => $this->name,
                'category' => $this->category,
                'brand_model' => $this->brand_model,
                'quantity' => $this->quantity,
                'status' => $this->status,
                'is_dmx' => $isDmx,
                'dmx_mode' => ($allowDmx && $isDmx) ? $this->dmx_mode : null,
                'dmx_address' => ($allowDmx && $isDmx) ? $this->dmx_address : null,
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
                'is_dmx' => $isDmx,
                'dmx_mode' => ($allowDmx && $isDmx) ? $this->dmx_mode : null,
                'dmx_address' => ($allowDmx && $isDmx) ? $this->dmx_address : null,
                'notes' => $this->notes,
            ]);
            session()->flash('message', 'Material añadido correctamente.');
        }

        $this->showModal = false;
        $this->loadEquipment();
    }

    /**
     * Elimina un equipo del inventario a partir de su identificador.
     *
     * @param int|string $id Identificador del equipo a eliminar.
     * @return void
     */
    public function delete($id)
    {
        Equipment::findOrFail($id)->delete();
        session()->flash('message', 'Material eliminado.');
        $this->loadEquipment();
    }
    
    /**
     * Incrementa o decrementa la cantidad disponible de un equipo en el inventario sin bajar de cero.
     *
     * @param int|string $id Identificador del equipo.
     * @param int $amount Cantidad a sumar o restar.
     * @return void
     */
    public function updateQuantity($id, $amount)
    {
        $equipment = Equipment::findOrFail($id);
        $newQty = $equipment->quantity + $amount;
        if ($newQty >= 0) {
            $equipment->update(['quantity' => $newQty]);
            $this->loadEquipment();
        }
    }

    /**
     * Restablece todos los campos del formulario de equipo y limpia los errores de validación.
     *
     * @return void
     */
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

    /**
     * Renderiza la vista de gestión de inventario.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.admin.inventory-manager')
            ->layout('components.layouts.app', [
                'header' => 'Gestión de Inventario'
            ]);
    }
}
