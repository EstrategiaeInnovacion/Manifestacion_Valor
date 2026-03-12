<section>

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
        </div>
    </form>
</section>

{{-- Modal de contraseña actualizada --}}
@if (session('status') === 'password-updated')
<div
    x-data="{ open: true }"
    x-show="open"
    x-transition.opacity
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
    @keydown.escape.window="open = false"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="bg-white rounded-2xl shadow-2xl max-w-sm w-full mx-4 p-8 text-center"
        @click.stop
    >
        <div class="flex items-center justify-center w-16 h-16 bg-emerald-100 rounded-full mx-auto mb-4">
            <i data-lucide="shield-check" class="w-8 h-8 text-emerald-600"></i>
        </div>
        <h3 class="text-xl font-black text-[#001a4d] mb-2">¡Contraseña Actualizada!</h3>
        <p class="text-slate-500 text-sm mb-6">Tu contraseña ha sido cambiada exitosamente. Tu cuenta está protegida.</p>
        <button
            @click="open = false"
            class="inline-flex items-center justify-center gap-2 bg-[#003399] hover:bg-[#002266] text-white font-bold px-6 py-2.5 rounded-xl transition-colors w-full"
        >
            <i data-lucide="check" class="w-4 h-4"></i>
            Entendido
        </button>
    </div>
</div>
@endif
