<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\User;
use App\Services\PostalCodeService;
use App\Livewire\Traits\HandlesDniValidation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Componente Livewire para la administración de clientes y parejas, detección/fusión de duplicados y limpieza de contactos spam.
 */
class ClientManager extends Component
{
    use HandlesDniValidation;
    public $search = '';
    public $filterEvents = 'all'; // 'all', 'with_events', 'without_events'

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

    public $duplicateWarnings = [
        'name' => null,
        'phone' => null,
        'dni' => null,
        'edit_name' => null,
        'edit_phone' => null,
        'edit_dni' => null,
    ];

    /**
     * Verifica en cada petición que el usuario autenticado tenga rol de administrador.
     *
     * @return void
     */
    public function boot()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }
    }

    /**
     * Inicializa el componente asegurando el acceso exclusivo para administradores.
     *
     * @return void
     */
    public function mount()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Acceso restringido a administradores.');
        }
    }

    // ==================== CREACIÓN ====================

    /**
     * Limpia el formulario y abre el modal de creación de un nuevo cliente.
     *
     * @return void
     */
    public function openCreateModal()
    {
        $this->resetValidation();
        $this->reset(['name', 'username', 'email', 'phone', 'dni', 'address', 'postal_code', 'city', 'province', 'password', 'duplicateWarnings']);
        $this->showCreateModal = true;
    }

    /**
     * Cierra el modal de creación de cliente y reinicia las advertencias de duplicidad.
     *
     * @return void
     */
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->duplicateWarnings = [
            'name' => null, 'phone' => null, 'dni' => null,
            'edit_name' => null, 'edit_phone' => null, 'edit_dni' => null,
        ];
    }

    /**
     * Comprueba si ya existe un cliente con el mismo nombre al actualizar el campo nombre en creación.
     *
     * @param mixed $value Nombre introducido.
     * @return void
     */
    public function updatedName($value)
    {
        $this->checkDuplicate('name', $value);
    }

    /**
     * Comprueba si ya existe un cliente con el mismo teléfono al actualizar el campo teléfono en creación.
     *
     * @param mixed $value Teléfono introducido.
     * @return void
     */
    public function updatedPhone($value)
    {
        $this->checkDuplicate('phone', $value);
    }

    /**
     * Autocompleta la localidad y provincia a partir del código postal introducido en el formulario de creación.
     *
     * @param mixed $value Código postal introducido.
     * @return void
     */
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

    /**
     * Valida/completa la letra del DNI y verifica si ya está registrado por otro cliente durante la creación.
     *
     * @param mixed $value DNI/NIE introducido.
     * @return void
     */
    public function updatedDni($value)
    {
        $this->validateAndCompleteDni('dni', $value);
        $this->checkDuplicate('dni', $this->dni);
    }

    /**
     * Comprueba posibles duplicados cuando se modifica el nombre en el formulario de edición.
     *
     * @param mixed $value Nombre editado.
     * @return void
     */
    public function updatedEditName($value)
    {
        $this->checkDuplicate('edit_name', $value, $this->editing_client_id);
    }

    /**
     * Comprueba posibles duplicados cuando se modifica el teléfono en el formulario de edición.
     *
     * @param mixed $value Teléfono editado.
     * @return void
     */
    public function updatedEditPhone($value)
    {
        $this->checkDuplicate('edit_phone', $value, $this->editing_client_id);
    }

    /**
     * Valida/completa el DNI y comprueba duplicados al modificarlo en el formulario de edición.
     *
     * @param mixed $value DNI/NIE editado.
     * @return void
     */
    public function updatedEditDni($value)
    {
        $this->validateAndCompleteDni('edit_dni', $value);
        $this->checkDuplicate('edit_dni', $this->edit_dni, $this->editing_client_id);
    }

    /**
     * Verifica si un valor de nombre, teléfono o DNI ya pertenece a otro cliente y registra una advertencia visual.
     *
     * @param string $field Nombre del campo evaluado.
     * @param string|null $value Valor a comprobar.
     * @param int|null $ignoreId ID del cliente actual a excluir de la búsqueda.
     * @return void
     */
    protected function checkDuplicate(string $field, ?string $value, ?int $ignoreId = null): void
    {
        $this->duplicateWarnings[$field] = null;
        if (empty($value)) return;

        if (str_contains($field, 'name')) {
            $nameClean = trim($value);
            if (mb_strlen($nameClean) >= 3) {
                $exists = User::where('role', 'client')
                    ->where('name', $nameClean)
                    ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                    ->first();
                if ($exists) {
                    $hasEvents = $exists->clientEvents()->count();
                    $info = $hasEvents ? "con evento asignado" : "sin eventos";
                    $this->duplicateWarnings[$field] = "⚠️ Ya existe un cliente con este nombre: '{$exists->name}' ({$info}, ID #{$exists->id}).";
                }
            }
        } elseif (str_contains($field, 'phone')) {
            $digits = preg_replace('/\D/', '', $value);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $exists = User::where('role', 'client')
                    ->where(function($q) use ($last9, $digits) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $digits);
                    })
                    ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                    ->first();
                if ($exists) {
                    $this->duplicateWarnings[$field] = "⚠️ Este teléfono ya está asignado a: '{$exists->name}' (ID #{$exists->id}).";
                }
            }
        } elseif (str_contains($field, 'dni')) {
            $dniClean = strtoupper(trim($value));
            if (mb_strlen($dniClean) >= 8) {
                $exists = User::where('role', 'client')
                    ->where('dni', $dniClean)
                    ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
                    ->first();
                if ($exists) {
                    $this->duplicateWarnings[$field] = "⚠️ Este DNI ya está registrado por: '{$exists->name}' (ID #{$exists->id}).";
                }
            }
        }
    }

    /**
     * Valida los datos y guarda un nuevo cliente en la base de datos evitando duplicados de DNI, teléfono o nombre.
     *
     * @return void
     */
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

        // Restricción: Validar DNI duplicado
        if (!empty($this->dni)) {
            $cleanDni = strtoupper(trim($this->dni));
            $existsDni = User::where('role', 'client')->where('dni', $cleanDni)->first();
            if ($existsDni) {
                $this->addError('dni', "El DNI {$cleanDni} ya está registrado a nombre del cliente '{$existsDni->name}'.");
                return;
            }
        }

        // Restricción: Validar Teléfono duplicado
        if (!empty($this->phone)) {
            $digits = preg_replace('/\D/', '', $this->phone);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $existsPhone = User::where('role', 'client')
                    ->where(function($q) use ($last9, $digits) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $digits);
                    })->first();
                if ($existsPhone) {
                    $this->addError('phone', "El teléfono ya pertenece al cliente '{$existsPhone->name}'.");
                    return;
                }
            }
        }

        // Restricción: Validar Nombre exacto duplicado
        if (!empty($this->name)) {
            $cleanName = trim($this->name);
            $existsName = User::where('role', 'client')->where('name', $cleanName)->first();
            if ($existsName) {
                $this->addError('name', "Ya existe un cliente registrado con el nombre '{$existsName->name}'. Si es otra pareja, añade un apellido o distintivo.");
                return;
            }
        }

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

    /**
     * Carga los datos de un cliente existente y abre el modal de edición.
     *
     * @param int|string $clientId Identificador del cliente a editar.
     * @return void
     */
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

        if (!empty($this->edit_dni)) {
            $this->validateAndCompleteDni('edit_dni', $this->edit_dni);
        } else {
            $this->dniValidations['edit_dni'] = null;
        }

        $this->showEditModal = true;
    }

    /**
     * Cierra el modal de edición de cliente y limpia el estado de edición.
     *
     * @return void
     */
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editing_client_id = null;
    }

    /**
     * Autocompleta la localidad y provincia al modificar el código postal en el formulario de edición.
     *
     * @param mixed $value Código postal introducido.
     * @return void
     */
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

    /**
     * Valida y actualiza la ficha completa del cliente seleccionado.
     *
     * @return void
     */
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

        // Restricción: Validar DNI duplicado
        if (!empty($this->edit_dni)) {
            $cleanDni = strtoupper(trim($this->edit_dni));
            $existsDni = User::where('role', 'client')->where('dni', $cleanDni)->where('id', '!=', $client->id)->first();
            if ($existsDni) {
                $this->addError('edit_dni', "El DNI {$cleanDni} ya está asignado al cliente '{$existsDni->name}'.");
                return;
            }
        }

        // Restricción: Validar Teléfono duplicado
        if (!empty($this->edit_phone)) {
            $digits = preg_replace('/\D/', '', $this->edit_phone);
            if (strlen($digits) >= 9) {
                $last9 = substr($digits, -9);
                $existsPhone = User::where('role', 'client')
                    ->where('id', '!=', $client->id)
                    ->where(function($q) use ($last9, $digits) {
                        $q->where('phone', 'LIKE', "%{$last9}")
                          ->orWhere('phone', $digits);
                    })->first();
                if ($existsPhone) {
                    $this->addError('edit_phone', "El teléfono ya pertenece al cliente '{$existsPhone->name}'.");
                    return;
                }
            }
        }

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

    /**
     * Abre el modal para cambiar o generar rápidamente una contraseña para el cliente indicado.
     *
     * @param int|string $clientId Identificador del cliente.
     * @return void
     */
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

    /**
     * Cierra el modal de cambio rápido de contraseña y limpia los datos temporales.
     *
     * @return void
     */
    public function closePasswordModal()
    {
        $this->showPasswordModal = false;
        $this->password_client_id = null;
        $this->new_password = '';
        $this->generatedPasswordInfo = null;
    }

    /**
     * Genera una cadena aleatoria de 8 caracteres para asignarla como nueva contraseña.
     *
     * @return void
     */
    public function generateRandomPassword()
    {
        $this->new_password = Str::random(8);
    }

    /**
     * Valida y guarda la nueva contraseña del cliente, preparando el resumen de credenciales generadas.
     *
     * @return void
     */
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

    /**
     * Elimina un cliente de la base de datos a partir de su identificador.
     *
     * @param int|string $clientId Identificador del cliente a eliminar.
     * @return void
     */
    public function deleteClient($clientId)
    {
        $client = User::where('role', 'client')->find($clientId);
        if ($client) {
            $name = $client->name;
            $client->delete();
            session()->flash('message', "Cliente '{$name}' eliminado correctamente.");
        }
    }

    // ==================== LIMPIEZA DE CONTACTOS SPAM / SIN EVENTOS ====================

    /**
     * Elimina eventos huérfanos y cuentas de clientes detectadas como spam o importadas por error sin eventos asociados.
     *
     * @return void
     */
    public function cleanSpamContacts()
    {
        // 1. Eliminar eventos de spam huérfanos si se hubiesen generado
        $spamPatterns = ['RobertNar%', 'Bodas.net%', 'Madrigal Marketing%', 'no-reply%', '%UTF-8%', 'Javnx DJ%'];
        foreach ($spamPatterns as $pattern) {
            \App\Models\Event::where('name', 'LIKE', $pattern)
                ->where('status', 'no_response')
                ->where('notes', 'LIKE', '%LEAD IMPORTADO%')
                ->delete();
        }

        // 2. Eliminar contactos de clientes spam / importados por error sin eventos
        $query = User::where('role', 'client')
            ->doesntHave('clientEvents')
            ->where(function($q) {
                $q->where('email', 'LIKE', '%@microsoft.com')
                  ->orWhere('email', 'LIKE', '%@bodas.net')
                  ->orWhere('email', 'LIKE', '%email.bodas.net')
                  ->orWhere('email', 'LIKE', '%@madrigalmarketing.es')
                  ->orWhere('email', 'LIKE', '%mail.madrigalmarketing.es')
                  ->orWhere('email', 'LIKE', '%@ptd.net')
                  ->orWhere('email', 'LIKE', '%@ganoexcel.us')
                  ->orWhere('email', 'LIKE', '%@nunezandson.com')
                  ->orWhere('email', '=', 'muralikhalifa@gmail.com')
                  ->orWhere('name', 'LIKE', 'RobertNar%')
                  ->orWhere('name', 'LIKE', 'Bodas.net%')
                  ->orWhere('name', 'LIKE', 'Madrigal Marketing%')
                  ->orWhere('name', 'LIKE', 'no-reply%')
                  ->orWhere('name', 'LIKE', '%UTF-8%')
                  ->orWhere('name', 'LIKE', 'Javnx DJ%');
            });

        $count = $query->count();
        if ($count > 0) {
            $query->delete();
            session()->flash('message', "Se han eliminado {$count} contactos spam / importados por error.");
        } else {
            session()->flash('message', "No se encontraron contactos spam para limpiar.");
        }
    }

    /**
     * Elimina todos los usuarios con rol de cliente que no tienen ningún evento asignado.
     *
     * @return void
     */
    public function cleanAllWithoutEvents()
    {
        $deleted = User::where('role', 'client')
            ->doesntHave('clientEvents')
            ->delete();

        session()->flash('message', "Se han eliminado {$deleted} contactos sin eventos asignados.");
    }

    // ==================== FUSIÓN Y RESOLUCIÓN DE DUPLICADOS ====================

    /**
     * Detecta y fusiona automáticamente fichas de clientes duplicadas por teléfono, DNI o nombre, reasignando sus eventos y contratos.
     *
     * @return void
     */
    public function mergeDuplicates()
    {
        $allClients = User::where('role', 'client')
            ->withCount('clientEvents')
            ->orderBy('client_events_count', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        $mergedCount = 0;
        $processedIds = [];

        foreach ($allClients as $primary) {
            if (in_array($primary->id, $processedIds)) {
                continue;
            }

            $phoneDigits = !empty($primary->phone) ? preg_replace('/\D/', '', $primary->phone) : null;
            $phoneLast9 = ($phoneDigits && strlen($phoneDigits) >= 9) ? substr($phoneDigits, -9) : null;
            $dniClean = !empty($primary->dni) ? strtoupper(trim($primary->dni)) : null;
            $nameClean = trim($primary->name);

            $duplicates = User::where('role', 'client')
                ->where('id', '!=', $primary->id)
                ->whereNotIn('id', $processedIds)
                ->where(function($q) use ($phoneLast9, $dniClean, $nameClean) {
                    $hasCondition = false;
                    if ($phoneLast9) {
                        $q->orWhere('phone', 'LIKE', "%{$phoneLast9}");
                        $hasCondition = true;
                    }
                    if ($dniClean) {
                        $q->orWhere('dni', $dniClean);
                        $hasCondition = true;
                    }
                    if (mb_strlen($nameClean) >= 3 && !in_array(mb_strtolower($nameClean), ['cliente', 'cliente potencial', 'contacto web'])) {
                        $q->orWhere('name', $nameClean);
                        $hasCondition = true;
                    }
                    if (!$hasCondition) {
                        $q->whereRaw('0 = 1');
                    }
                })
                ->get();

            foreach ($duplicates as $dup) {
                // 1. Reasignar eventos al cliente principal
                \App\Models\Event::where('client_id', $dup->id)->update(['client_id' => $primary->id]);

                // 2. Reasignar posibles contratos al cliente principal
                if (\Illuminate\Support\Facades\Schema::hasTable('contracts') && \Illuminate\Support\Facades\Schema::hasColumn('contracts', 'client_id')) {
                    \Illuminate\Support\Facades\DB::table('contracts')->where('client_id', $dup->id)->update(['client_id' => $primary->id]);
                }

                // 3. Completar datos faltantes en el cliente principal
                $updates = [];
                if (empty($primary->phone) && !empty($dup->phone)) $updates['phone'] = $dup->phone;
                if (empty($primary->dni) && !empty($dup->dni)) $updates['dni'] = $dup->dni;
                if (empty($primary->address) && !empty($dup->address)) $updates['address'] = $dup->address;
                if (empty($primary->city) && !empty($dup->city)) $updates['city'] = $dup->city;
                if (empty($primary->province) && !empty($dup->province)) $updates['province'] = $dup->province;
                if (empty($primary->postal_code) && !empty($dup->postal_code)) $updates['postal_code'] = $dup->postal_code;
                if (str_ends_with($primary->email, '@eventosmusicales.local') && !str_ends_with($dup->email, '@eventosmusicales.local')) {
                    $updates['email'] = $dup->email;
                }

                if (!empty($updates)) {
                    $primary->update($updates);
                }

                $processedIds[] = $dup->id;
                $dup->delete();
                $mergedCount++;
            }
        }

        if ($mergedCount > 0) {
            session()->flash('message', "Se han fusionado {$mergedCount} contactos duplicados correctamente.");
        } else {
            session()->flash('message', "No se han detectado contactos duplicados en la agenda.");
        }
    }

    /**
     * Filtra la lista de clientes según criterios de búsqueda y pertenencia a eventos, y renderiza la vista.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function render()
    {
        // Estadísticas para las pestañas de filtro
        $totalCount = User::where('role', 'client')->count();
        $withEventsCount = User::where('role', 'client')->has('clientEvents')->count();
        $withoutEventsCount = User::where('role', 'client')->doesntHave('clientEvents')->count();

        $query = User::where('role', 'client')
            ->withCount('clientEvents')
            ->with(['clientEvents' => function($q) {
                $q->latest('event_date')->take(1);
            }])
            ->orderBy('name', 'asc');

        if ($this->filterEvents === 'with_events') {
            $query->has('clientEvents');
        } elseif ($this->filterEvents === 'without_events') {
            $query->doesntHave('clientEvents');
        }

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
            'totalCount' => $totalCount,
            'withEventsCount' => $withEventsCount,
            'withoutEventsCount' => $withoutEventsCount,
        ])->layout('components.layouts.app', ['header' => 'Gestión de Clientes y Novios']);
    }
}
