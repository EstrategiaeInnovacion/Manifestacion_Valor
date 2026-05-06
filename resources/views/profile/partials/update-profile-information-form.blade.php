<section>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('patch')

        <div class="form-group">
            <label for="full_name" class="form-label">Nombre Completo</label>
            <input id="full_name" name="full_name" type="text" class="form-input" value="{{ old('full_name', $user->full_name) }}" required autofocus autocomplete="name">
            @error('full_name')
                <p class="error-message">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email" class="form-label">Correo Electrónico</label>
            <input id="email" type="email" class="form-input" value="{{ $user->email }}" disabled>
            <p class="text-xs text-slate-400 mt-1">El correo electrónico no puede modificarse.</p>
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">
                <i data-lucide="save" class="w-4 h-4 inline-block mr-2"></i>
                Guardar Cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="success-message">
                    ¡Guardado!
                </p>
            @endif
        </div>
    </form>
</section>
