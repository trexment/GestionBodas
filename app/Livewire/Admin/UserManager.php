<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManager extends Component
{
    public $name;
    public $username;
    public $email;
    public $phone;
    public $password;
    public $role = 'client';
    public $filterRole = 'all';

    public $showCreateModal = false;

    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'username', 'email', 'phone', 'password', 'role']);
        $this->role = 'assistant'; // default to assistant / staff
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
            $finalPassword = $this->password ? Hash::make($this->password) : Hash::make(\Illuminate\Support\Str::random(16));
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

    public function deleteUser($userId)
    {
        $user = User::find($userId);
        
        if ($user && $user->id === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta de administrador.');
            return;
        }

        if ($user) {
            $user->delete();
            session()->flash('message', 'Usuario eliminado correctamente.');
        }
    }

    public function render()
    {
        $query = User::orderBy('name', 'asc');
        if ($this->filterRole !== 'all') {
            $query->where('role', $this->filterRole);
        }
        $users = $query->get();

        return view('livewire.admin.user-manager', [
            'users' => $users,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Usuarios y Personal']);
    }
}
