<section>
    <header class="profile-header">
        <h2 class="profile-title">
            Actualizar Contraseña
        </h2>
        <p class="profile-description">
            Asegúrate de usar una contraseña larga y segura para mantener tu cuenta protegida.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        @method('put')

        <div class="form-group">
            <label for="update_password_current_password" class="form-label">Contraseña Actual</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-input" autocomplete="current-password">
            @if ($errors->updatePassword->has('current_password'))
                <p class="error-message">{{ $errors->updatePassword->first('current_password') }}</p>
            @endif
        </div>

        <div class="form-group">
            <label for="update_password_password" class="form-label">Nueva Contraseña</label>
            <input id="update_password_password" name="password" type="password" class="form-input" autocomplete="new-password">
            @if ($errors->updatePassword->has('password'))
                <p class="error-message">{{ $errors->updatePassword->first('password') }}</p>
            @endif
        </div>

        <div class="form-group">
            <label for="update_password_password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-input" autocomplete="new-password">
            @if ($errors->updatePassword->has('password_confirmation'))
                <p class="error-message">{{ $errors->updatePassword->first('password_confirmation') }}</p>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary">
                <i data-lucide="shield-check" class="w-4 h-4 inline-block mr-2"></i>
                Actualizar Contraseña
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="success-message">
                    ¡Guardado!
                </p>
            @endif
        </div>
    </form>
</section>
