@php
    $companyName = \App\Models\Setting::get('company_name', config('app.name'));
@endphp

<x-guest-layout>
    <div class="min-h-screen flex bg-canvas">
        {{-- Panel izquierdo (marca) --}}
        <div class="hidden lg:flex lg:w-1/2 relative bg-gradient-to-br from-primary to-primary-container overflow-hidden">
            <div class="absolute inset-0 opacity-10"
                 style="background-image:radial-gradient(circle at 20% 30%, white 1px, transparent 1px);background-size:32px 32px;"></div>
            <div class="relative z-10 flex flex-col justify-between p-12 text-white">
                <div class="flex items-center gap-3">
                    <x-company-logo class="h-12 bg-white/90 rounded-lg p-1.5" />
                    <span class="text-2xl font-bold">{{ $companyName }}</span>
                </div>
                <div>
                    <h1 class="text-4xl font-bold leading-tight">Inventario TI</h1>
                    <p class="mt-4 text-lg text-white/80 max-w-md">
                        Control y trazabilidad de los bienes informáticos de la organización.
                    </p>
                </div>
                <p class="text-sm text-white/60">&copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.</p>
            </div>
        </div>

        {{-- Panel derecho (formulario) --}}
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <div class="lg:hidden flex flex-col items-center mb-8">
                    <x-company-logo class="h-14 mb-3" />
                    <span class="text-xl font-bold text-primary">{{ $companyName }}</span>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-on-surface">Iniciar sesión</h2>
                    <p class="mt-1 text-body-md text-on-surface-variant">Accede al portal de Inventario TI.</p>
                </div>

                <x-validation-errors class="mb-4" />

                @session('status')
                    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-2 text-sm text-green-700">
                        {{ $value }}
                    </div>
                @endsession

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-label for="email" value="{{ __('Correo electrónico') }}" />
                        <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="usuario@empresa.com" />
                    </div>

                    <div>
                        <x-label for="password" value="{{ __('Contraseña') }}" />
                        <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label for="remember_me" class="flex items-center">
                            <x-checkbox id="remember_me" name="remember" />
                            <span class="ms-2 text-sm text-on-surface-variant">{{ __('Recordarme') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm text-primary hover:underline rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary" href="{{ route('password.request') }}">
                                {{ __('¿Olvidaste tu contraseña?') }}
                            </a>
                        @endif
                    </div>

                    <button type="submit"
                        class="w-full inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2.5 text-white font-medium hover:bg-primary-container focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition">
                        {{ __('Ingresar') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
