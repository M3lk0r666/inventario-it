<div>
    @php($readonly = ! auth()->user()->can('settings.edit'))

    <div class="grid grid-cols-1 lg:grid-cols-[220px,1fr] gap-gutter items-start">
        {{-- Secciones --}}
        <nav class="card p-2 lg:sticky lg:top-20">
            @foreach (['company' => 'Empresa', 'mail' => 'Correo (SMTP)'] as $key => $label)
                <button type="button" wire:click="$set('tab', '{{ $key }}')"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-body-md text-left transition-colors {{ $tab === $key ? 'bg-primary-fixed/40 text-primary font-medium' : 'text-on-surface-variant hover:bg-surface-container-low' }}">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="card p-6 max-w-2xl">
            {{-- EMPRESA --}}
            @if ($tab === 'company')
                <h3 class="text-title-md text-on-surface mb-4">Datos de la empresa</h3>
                <div class="space-y-4">
                    <div>
                        <label class="form-label">Nombre de la empresa</label>
                        <input type="text" wire:model="company_name" class="form-input" @disabled($readonly)>
                        @error('company_name') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Logo (aparece en las cartas)</label>
                        <div class="flex items-center gap-4">
                            @if ($currentLogo)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentLogo) }}"
                                    alt="Logo" class="h-12 bg-white border border-border-soft rounded-lg p-1 object-contain">
                            @endif
                            @unless ($readonly)
                                <input type="file" wire:model="logo" accept="image/*"
                                    class="block text-body-sm text-on-surface-variant file:me-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-primary-fixed file:text-primary file:text-label-md hover:file:opacity-80">
                            @endunless
                        </div>
                        <div wire:loading wire:target="logo" class="form-help">Cargando…</div>
                        @error('logo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    @unless ($readonly)
                        <div class="flex justify-end border-t border-border-soft pt-4">
                            <button type="button" class="btn-primary" wire:click="saveCompany" wire:loading.attr="disabled" wire:target="saveCompany, logo">Guardar</button>
                        </div>
                    @endunless
                </div>

            {{-- CORREO --}}
            @elseif ($tab === 'mail')
                <h3 class="text-title-md text-on-surface mb-1">Correo (SMTP Office 365)</h3>
                <p class="text-body-sm text-on-surface-variant mb-4">Usa la cuenta y contraseña de aplicación de Office 365. La app envía a las direcciones/listas configuradas.</p>
                <div class="space-y-4">
                    <label class="inline-flex items-center gap-2 text-body-md text-on-surface">
                        <input type="checkbox" wire:model="mail_enabled" class="rounded border-border-soft text-primary-container focus:ring-primary-container" @disabled($readonly)>
                        Correo habilitado
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="form-label">Servidor</label>
                            <input type="text" wire:model="mail_host" class="form-input" @disabled($readonly)>
                            @error('mail_host') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Puerto</label>
                            <input type="number" wire:model="mail_port" class="form-input" @disabled($readonly)>
                            @error('mail_port') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="form-label">Cifrado</label>
                            <select wire:model="mail_encryption" class="form-input" @disabled($readonly)>
                                <option value="tls">TLS (STARTTLS)</option>
                                <option value="ssl">SSL</option>
                                <option value="">Ninguno</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="form-label">Usuario (cuenta)</label>
                            <input type="text" wire:model="mail_username" class="form-input" @disabled($readonly)>
                            @error('mail_username') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Contraseña de aplicación</label>
                        <input type="password" wire:model="mail_password" class="form-input" placeholder="{{ $mail_password ? '•••••••• (guardada)' : '' }}" @disabled($readonly)>
                        <p class="form-help">Déjala vacía para conservar la actual.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="form-label">Correo remitente</label>
                            <input type="email" wire:model="mail_from_address" class="form-input" @disabled($readonly)>
                            @error('mail_from_address') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Nombre remitente</label>
                            <input type="text" wire:model="mail_from_name" class="form-input" @disabled($readonly)>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Destinatarios de alertas</label>
                        <textarea wire:model="alert_recipients" rows="2" class="form-input" placeholder="ti@empresa.com, alertas@empresa.com" @disabled($readonly)></textarea>
                        <p class="form-help">Separados por coma. Reciben el digest diario de renovaciones, garantías y stock bajo.</p>
                    </div>

                    @unless ($readonly)
                        <div class="flex flex-wrap items-end justify-between gap-3 border-t border-border-soft pt-4">
                            <div class="flex items-end gap-2">
                                <div>
                                    <label class="form-label">Enviar prueba a</label>
                                    <input type="email" wire:model="testEmail" class="form-input !w-56" placeholder="tu@correo.com">
                                    @error('testEmail') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <button type="button" class="btn-ghost" wire:click="sendTest" wire:loading.attr="disabled" wire:target="sendTest">
                                    <i class="ri-mail-send-line"></i> Probar
                                </button>
                            </div>
                            <button type="button" class="btn-primary" wire:click="saveMail" wire:loading.attr="disabled" wire:target="saveMail">Guardar</button>
                        </div>
                    @endunless
                </div>
            @endif
        </div>
    </div>
</div>
