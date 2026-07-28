<div>
    @php($readonly = ! auth()->user()->can('settings.edit'))

    <div class="grid grid-cols-1 lg:grid-cols-[220px,1fr] gap-gutter items-start">
        {{-- Secciones --}}
        <nav class="card p-2 lg:sticky lg:top-20">
            @foreach (['company' => 'Empresa', 'letters' => 'Cartas responsivas', 'mail' => 'Correo (SMTP)'] as $key => $label)
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

            {{-- CARTAS --}}
            @elseif ($tab === 'letters')
                <h3 class="text-title-md text-on-surface mb-1">Cartas responsivas</h3>
                <p class="text-body-sm text-on-surface-variant mb-3">Cada tipo lleva su propio prefijo de folio, consecutivo y texto, para no mezclarse.</p>

                <div class="mb-5 rounded-lg border border-border-soft bg-surface-container-low/40 p-3">
                    <p class="text-body-sm text-on-surface-variant mb-2">
                        <i class="ri-magic-line text-primary"></i> Puedes usar estos marcadores en los textos; se reemplazan con los datos reales al generar la carta:
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Services\ResponsiveLetterService::PLACEHOLDERS as $tag => $desc)
                            <span class="inline-flex items-center gap-1 text-body-sm">
                                <code class="px-1.5 py-0.5 rounded bg-white border border-border-soft text-primary font-mono">{{ $tag }}</code>
                                <span class="text-on-surface-variant">{{ $desc }}</span>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="space-y-6">
                    {{-- CAB --}}
                    <div class="border border-border-soft rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="chip-info">CAB</span>
                            <h4 class="text-title-md text-on-surface">Cartas de Aceptación de Bienes</h4>
                        </div>
                        <p class="text-body-sm text-on-surface-variant mb-3">Se genera cuando el empleado <strong>recibe</strong> los bienes (asignación).</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Prefijo de folio</label>
                                <input type="text" wire:model="cab_prefix" class="form-input" @disabled($readonly)>
                                @error('cab_prefix') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Folio inicial</label>
                                <input type="number" min="1" wire:model="cab_start" class="form-input" @disabled($readonly)>
                                @error('cab_start') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="form-help">Formato: {{ $cab_prefix }}-{{ now()->year }}-{{ str_pad($cab_start ?: '1', 4, '0', STR_PAD_LEFT) }} · el número avanza automáticamente y de forma consecutiva.</p>
                        <div class="mt-3">
                            <label class="form-label">Texto de la carta</label>
                            <textarea wire:model="cab_text" rows="5" class="form-input" @disabled($readonly)></textarea>
                            @error('cab_text') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- CEB --}}
                    <div class="border border-border-soft rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="chip-warning">CEB</span>
                            <h4 class="text-title-md text-on-surface">Cartas de Entrega de Bienes</h4>
                        </div>
                        <p class="text-body-sm text-on-surface-variant mb-3">Se genera cuando el empleado <strong>devuelve/egresa</strong> los bienes (recepción).</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">Prefijo de folio</label>
                                <input type="text" wire:model="ceb_prefix" class="form-input" @disabled($readonly)>
                                @error('ceb_prefix') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">Folio inicial</label>
                                <input type="number" min="1" wire:model="ceb_start" class="form-input" @disabled($readonly)>
                                @error('ceb_start') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="form-help">Formato: {{ $ceb_prefix }}-{{ now()->year }}-{{ str_pad($ceb_start ?: '1', 4, '0', STR_PAD_LEFT) }} · el número avanza automáticamente y de forma consecutiva.</p>
                        <div class="mt-3">
                            <label class="form-label">Texto de la carta</label>
                            <textarea wire:model="ceb_text" rows="5" class="form-input" @disabled($readonly)></textarea>
                            @error('ceb_text') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    @unless ($readonly)
                        <div class="flex justify-end border-t border-border-soft pt-4">
                            <button type="button" class="btn-primary" wire:click="saveLetters" wire:loading.attr="disabled">Guardar</button>
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
