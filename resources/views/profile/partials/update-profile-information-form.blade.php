<section>
    <header class="profile-header">
        <h2 class="profile-title">
            Información del Perfil
        </h2>
        <p class="profile-description">
            Actualiza la información de tu cuenta y dirección de correo electrónico.
        </p>
    </header>

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
            <input id="email" name="email" type="email" class="form-input" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
                <p class="error-message">{{ $message }}</p>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="verification-box">
                    <p class="text-sm">
                        Tu correo electrónico no está verificado.
                        <button form="send-verification" class="verification-link">
                            Haz clic aquí para reenviar el correo de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="success-message mt-2">
                            Un nuevo enlace de verificación ha sido enviado a tu correo.
                        </p>
                    @endif
                </div>
            @endif
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
