<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Services\PostalCodeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ClientManager extends Component
{
    public $search = '';

    // Formulario de Creación de Cliente
    public $name;
    public $username;
    public $email;
    public $phone;
    public $dni;
    public $address;
    public $postal_code;
    public $city;
    public $province;
    public $password;
    public $showCreateModal = false;

    // Formulario de Edición de Cliente
    public $editing_client_id = null;
    public $edit_name;
    public $edit_username;
    public $edit_email;
    public $edit_phone;
    public $edit_dni;
    public $edit_address;
    public $edit_postal_code;
    public $edit_city;
    public $edit_province;
    public $edit_password;
    public $showEditModal = false;

    // Modal Rápido de Contraseña
    public $password_client_id = null;
    public $password_client_name = '';
    public $new_password = '';
    public $showPasswordModal = false;
    public $generatedPasswordInfo = null;

    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }
    }

    // ==================== CREACIÓN ====================

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'username', 'email', 'phone', 'dni', 'address', 'postal_code', 'city', 'province', 'password']);
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function updatedPostalCode($value)
    {
        $lookup = PostalCodeService::lookup($value);
        if (!empty($lookup['city']) && empty($this->city)) {
            $this->city = $lookup['city'];
        }
        if (!empty($lookup['province'])) {
            $this->province = $lookup['province'];
        }
    }

    public function saveClient()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username',
            'email' => 'nullable|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'dni' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'password' => 'nullable|string|min:4',
        ], [
            'name.required' => 'El nombre del cliente o pareja es obligatorio.',
            'username.unique' => 'Este nombre de usuario ya existe.',
            'email.unique' => 'Este email ya está en uso.',
        ]);

        $finalEmail = $this->email ?: ('cliente_' . time() . '_' . rand(100, 999) . '@eventosmusicales.local');
        $finalPassword = $this->password ? Hash::make($this->password) : Hash::make(Str::random(16));

        User::create([
            'name' => $this->name,
            'username' => $this->username ?: null,
            'email' => $finalEmail,
            'phone' => $this->phone ?: null,
            'dni' => $this->dni ?: null,
            'address' => $this->address ?: null,
            'postal_code' => $this->postal_code ?: null,
            'city' => $this->city ?: null,
            'province' => $this->province ?: null,
            'password' => $finalPassword,
            'role' => 'client',
        ]);

        $this->closeCreateModal();
        session()->flash('message', '¡Cliente registrado correctamente!');
    }

    // ==================== EDICIÓN COMPLETA ====================

    public function openEditModal($clientId)
    {
        $this->resetValidation();
        $client = User::find($clientId);
        if (!$client) return;

        $this->editing_client_id = $client->id;
        $this->edit_name = $client->name;
        $this->edit_username = $client->username;
        $this->edit_email = str_ends_with($client->email, '@eventosmusicales.local') ? '' : $client->email;
        $this->edit_phone = $client->phone;
        $this->edit_dni = $client->dni ?? $client->nif;
        $this->edit_address = $client->address;
        $this->edit_postal_code = $client->postal_code;
        $this->edit_city = $client->city;
        $this->edit_province = $client->province;
        $this->edit_password = '';

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editing_client_id = null;
    }

    public function updatedEditPostalCode($value)
    {
        $lookup = PostalCodeService::lookup($value);
        if (!empty($lookup['city']) && empty($this->edit_city)) {
            $this->edit_city = $lookup['city'];
        }
        if (!empty($lookup['province'])) {
            $this->edit_province = $lookup['province'];
        }
    }

    public function updateClient()
    {
        $client = User::find($this->editing_client_id);
        if (!$client) return;

        $rules = [
            'edit_name' => 'required|string|max:255',
            'edit_username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($client->id)],
            'edit_phone' => 'nullable|string|max:50',
            'edit_dni' => 'nullable|string|max:50',
            'edit_address' => 'nullable|string|max:255',
            'edit_postal_code' => 'nullable|string|max:10',
            'edit_city' => 'nullable|string|max:100',
            'edit_province' => 'nullable|string|max:100',
            'edit_password' => 'nullable|string|min:4',
        ];

        if (!empty($this->edit_email)) {
            $rules['edit_email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($client->id)];
        }

        $this->validate($rules, [
            'edit_name.required' => 'El nombre del cliente es obligatorio.',
            'edit_email.unique' => 'Este email ya está registrado.',
            'edit_username.unique' => 'Este nombre de usuario ya está registrado.',
            'edit_password.min' => 'La contraseña debe tener al menos 4 caracteres.',
        ]);

        $finalEmail = $client->email;
        if (!empty($this->edit_email)) {
            $finalEmail = $this->edit_email;
        }

        $updateData = [
            'name' => $this->edit_name,
            'username' => $this->edit_username ?: null,
            'email' => $finalEmail,
            'phone' => $this->edit_phone ?: null,
            'dni' => $this->edit_dni ?: null,
            'address' => $this->edit_address ?: null,
            'postal_code' => $this->edit_postal_code ?: null,
            'city' => $this->edit_city ?: null,
            'province' => $this->edit_province ?: null,
        ];

        if (!empty($this->edit_password)) {
            $updateData['password'] = Hash::make($this->edit_password);
        }

        $client->update($updateData);

        $this->closeEditModal();
        session()->flash('message', "¡Ficha de {$client->name} actualizada correctamente!");
    }

    // ==================== CAMBIO RÁPIDO DE CONTRASEÑA ====================

    public function openPasswordModal($clientId)
    {
        $this->resetValidation();
        $client = User::find($clientId);
        if (!$client) return;

        $this->password_client_id = $client->id;
        $this->password_client_name = $client->name;
        $this->new_password = '';
        $this->generatedPasswordInfo = null;
        $this->showPasswordModal = true;
    }

    public function closePasswordModal()
    {
        $this->showPasswordModal = false;
        $this->password_client_id = null;
        $this->new_password = '';
        $this->generatedPasswordInfo = null;
    }

    public function generateRandomPassword()
    {
        $this->new_password = Str::random(8);
    }

    public function saveNewPassword()
    {
        $this->validate([
            'new_password' => 'required|string|min:4',
        ], [
            'new_password.required' => 'Debes escribir o generar una contraseña.',
            'new_password.min' => 'La contraseña debe tener al menos 4 caracteres.',
        ]);

        $client = User::find($this->password_client_id);
        if (!$client) return;

        $client->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->generatedPasswordInfo = [
            'name' => $client->name,
            'email' => $client->email,
            'username' => $client->username,
            'password' => $this->new_password,
            'phone' => $client->phone,
        ];

        session()->flash('message', "¡Contraseña de {$client->name} asignada exitosamente!");
    }

    // ==================== ELIMINAR CLIENTE ====================

    public function deleteClient($clientId)
    {
        $client = User::where('role', 'client')->find($clientId);
        if ($client) {
            $name = $client->name;
            $client->delete();
            session()->flash('message', "Cliente '{$name}' eliminado correctamente.");
        }
    }

    public function render()
    {
        $query = User::where('role', 'client')
            ->withCount('clientEvents')
            ->with(['clientEvents' => function($q) {
                $q->latest('event_date')->take(1);
            }])
            ->orderBy('name', 'asc');

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('name', 'LIKE', $s)
                  ->orWhere('email', 'LIKE', $s)
                  ->orWhere('phone', 'LIKE', $s)
                  ->orWhere('username', 'LIKE', $s)
                  ->orWhere('dni', 'LIKE', $s)
                  ->orWhere('city', 'LIKE', $s)
                  ->orWhere('province', 'LIKE', $s);
            });
        }

        $clients = $query->get();

        return view('livewire.admin.client-manager', [
            'clients' => $clients,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Clientes y Novios']);
    }
}
