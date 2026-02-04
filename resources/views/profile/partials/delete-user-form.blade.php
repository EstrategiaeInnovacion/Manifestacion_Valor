<section class="space-y-6">
    <header class="profile-header">
        <h2 class="profile-title text-red-600">
            Eliminar Cuenta
        </h2>
        <p class="profile-description">
            Una vez que tu cuenta sea eliminada, todos sus recursos y datos serán borrados permanentemente. Antes de eliminar tu cuenta, descarga cualquier dato o información que desees conservar.
        </p>
    </header>

    <button type="button" class="btn-danger" x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        <i data-lucide="trash-2" class="w-4 h-4 inline-block mr-2"></i>
        Eliminar Cuenta
    </button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8">
            @csrf
            @method('delete')

            <h2 class="text-2xl font-bold text-[#001a4d] mb-4">
                ¿Estás seguro de que deseas eliminar tu cuenta?
            </h2>

            <p class="text-slate-600 mb-6">
                Una vez eliminada tu cuenta, todos sus recursos y datos serán borrados permanentemente. Por favor ingresa tu contraseña para confirmar que deseas eliminar tu cuenta de forma permanente.
            </p>

            <div class="form-group">
                <label for="password" class="form-label">Contraseña</label>
                <input id="password" name="password" type="password" class="form-input" placeholder="Tu contraseña">
                @if ($errors->userDeletion->has('password'))
                    <p class="error-message">{{ $errors->userDeletion->first('password') }}</p>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" class="btn-secondary" x-on:click="$dispatch('close')">
                    Cancelar
                </button>
                <button type="submit" class="btn-danger">
                    Eliminar Cuenta
                </button>
            </div>
        </form>
    </x-modal>
</section>
