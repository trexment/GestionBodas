<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Services\PostalCodeService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserManager extends Component
{
    // Filtros y búsqueda
    public $search = '';
    public $filterRole = 'all';

    // Formulario de Creación
    public $name;
    public $username;
    public $email;
    public $phone;
    public $password;
    public $role = 'client';
    public $showCreateModal = false;

    // Formulario de Edición
    public $editing_user_id = null;
    public $edit_name;
    public $edit_username;
    public $edit_email;
    public $edit_phone;
    public $edit_role = 'client';
    public $edit_dni;
    public $edit_address;
    public $edit_postal_code;
    public $edit_city;
    public $edit_province;
    public $edit_password;
    public $showEditModal = false;

    // Modal Rápido de Cambio de Contraseña
    public $password_user_id = null;
    public $password_user_name = '';
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
        $this->reset(['name', 'username', 'email', 'phone', 'password', 'role']);
        $this->role = 'assistant';
        $this->showCreateModal = true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
    }

    public function saveUser()
    {
        if ($this->role === 'client') {
            $this->validate([
                'name' => 'required|string|max:255',
                'username' => 'nullable|string|max:255|unique:users,username',
                'email' => 'nullable|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:50',
                'password' => 'nullable|string|min:4',
                'role' => ['required', Rule::in(['admin', 'dj', 'assistant', 'client'])],
            ]);

            $finalEmail = $this->email ?: ('cliente_' . time() . '_' . rand(100, 999) . '@eventosmusicales.local');
            $finalPassword = $this->password ? Hash::make($this->password) : Hash::make(Str::random(16));
        } else {
            $this->validate([
                'name' => 'required|string|max:255',
                'username' => 'nullable|string|max:255|unique:users,username',
                'email' => 'required|email|max:255|unique:users,email',
                'phone' => 'nullable|string|max:50',
                'password' => 'required|string|min:6',
                'role' => ['required', Rule::in(['admin', 'dj', 'assistant', 'client'])],
            ]);

            $finalEmail = $this->email;
            $finalPassword = Hash::make($this->password);
        }

        User::create([
            'name' => $this->name,
            'username' => $this->username ?: null,
            'email' => $finalEmail,
            'phone' => $this->phone ?: null,
            'password' => $finalPassword,
            'role' => $this->role,
        ]);

        $this->closeCreateModal();
        session()->flash('message', $this->role === 'client' ? 'Cliente añadido exitosamente.' : 'Usuario / Empleado creado exitosamente.');
    }

    // ==================== EDICIÓN COMPLETA ====================

    public function openEditModal($userId)
    {
        $this->resetValidation();
        $user = User::find($userId);
        if (!$user) return;

        $this->editing_user_id = $user->id;
        $this->edit_name = $user->name;
        $this->edit_username = $user->username;
        $this->edit_email = str_ends_with($user->email, '@eventosmusicales.local') ? '' : $user->email;
        $this->edit_phone = $user->phone;
        $this->edit_role = $user->role;
        $this->edit_dni = $user->dni ?? $user->nif;
        $this->edit_address = $user->address;
        $this->edit_postal_code = $user->postal_code;
        $this->edit_city = $user->city;
        $this->edit_province = $user->province;
        $this->edit_password = '';

        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editing_user_id = null;
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

    public function updateUser()
    {
        $user = User::find($this->editing_user_id);
        if (!$user) return;

        $rules = [
            'edit_name' => 'required|string|max:255',
            'edit_username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'edit_phone' => 'nullable|string|max:50',
            'edit_role' => ['required', Rule::in(['admin', 'dj', 'assistant', 'client'])],
            'edit_dni' => 'nullable|string|max:50',
            'edit_address' => 'nullable|string|max:255',
            'edit_postal_code' => 'nullable|string|max:10',
            'edit_city' => 'nullable|string|max:100',
            'edit_province' => 'nullable|string|max:100',
            'edit_password' => 'nullable|string|min:4',
        ];

        if ($this->edit_role !== 'client' || !empty($this->edit_email)) {
            $rules['edit_email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)];
        }

        $this->validate($rules, [
            'edit_name.required' => 'El nombre completo es obligatorio.',
            'edit_email.required' => 'El email es obligatorio para usuarios del equipo.',
            'edit_email.unique' => 'Este email ya está en uso por otro usuario.',
            'edit_username.unique' => 'Este nombre de usuario ya está en uso.',
            'edit_password.min' => 'La nueva contraseña debe tener al menos 4 caracteres.',
        ]);

        $finalEmail = $user->email;
        if (!empty($this->edit_email)) {
            $finalEmail = $this->edit_email;
        } elseif ($this->edit_role === 'client' && str_ends_with($user->email, '@eventosmusicales.local')) {
            $finalEmail = $user->email;
        }

        $updateData = [
            'name' => $this->edit_name,
            'username' => $this->edit_username ?: null,
            'email' => $finalEmail,
            'phone' => $this->edit_phone ?: null,
            'role' => $this->edit_role,
            'dni' => $this->edit_dni ?: null,
            'address' => $this->edit_address ?: null,
            'postal_code' => $this->edit_postal_code ?: null,
            'city' => $this->edit_city ?: null,
            'province' => $this->edit_province ?: null,
        ];

        if (!empty($this->edit_password)) {
            $updateData['password'] = Hash::make($this->edit_password);
        }

        $user->update($updateData);

        $this->closeEditModal();
        session()->flash('message', "¡Datos de {$user->name} actualizados correctamente!");
    }

    // ==================== CAMBIO RÁPIDO DE CONTRASEÑA ====================

    public function openPasswordModal($userId)
    {
        $this->resetValidation();
        $user = User::find($userId);
        if (!$user) return;

        $this->password_user_id = $user->id;
        $this->password_user_name = $user->name;
        $this->new_password = '';
        $this->generatedPasswordInfo = null;
        $this->showPasswordModal = true;
    }

    public function closePasswordModal()
    {
        $this->showPasswordModal = false;
        $this->password_user_id = null;
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

        $user = User::find($this->password_user_id);
        if (!$user) return;

        $user->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->generatedPasswordInfo = [
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'password' => $this->new_password,
            'phone' => $user->phone,
        ];

        session()->flash('message', "¡Contraseña de {$user->name} cambiada exitosamente!");
    }

    // ==================== ELIMINACIÓN ====================

    public function deleteUser($userId)
    {
        $user = User::find($userId);
        
        if ($user && $user->id === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta de administrador.');
            return;
        }

        if ($user) {
            $name = $user->name;
            $user->delete();
            session()->flash('message', "Usuario '{$name}' eliminado correctamente.");
        }
    }

    public function render()
    {
        $query = User::orderBy('name', 'asc');

        if ($this->filterRole !== 'all') {
            $query->where('role', $this->filterRole);
        }

        if (!empty(trim($this->search))) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function($q) use ($s) {
                $q->where('name', 'LIKE', $s)
                  ->orWhere('email', 'LIKE', $s)
                  ->orWhere('phone', 'LIKE', $s)
                  ->orWhere('username', 'LIKE', $s)
                  ->orWhere('dni', 'LIKE', $s);
            });
        }

        $users = $query->get();

        return view('livewire.admin.user-manager', [
            'users' => $users,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Usuarios y Personal']);
    }
}
