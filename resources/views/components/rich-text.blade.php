@props(['model'])

@php($uid = 'trix_'.\Illuminate\Support\Str::random(8))

{{--
  Editor de texto enriquecido (Trix) enlazado a una propiedad Livewire.
  Trix se empaqueta localmente vía Vite (resources/js/app.js).
--}}
<div wire:ignore
    x-data="{
        value: @entangle($model).live,
        bound: false,
        init() {
            const el = this.$refs.trix;
            const setup = () => {
                if (this.bound) return;
                this.bound = true;
                el.editor.loadHTML(this.value || '');
                el.addEventListener('trix-change', () => { this.value = el.value; });
            };
            // Trix ya inicializado o esperar su evento
            if (el.editor) setup();
            else el.addEventListener('trix-initialize', setup, { once: true });
            // Reflejar cambios externos (reset del formulario)
            this.$watch('value', (v) => {
                if (el.editor && (v || '') !== el.value) el.editor.loadHTML(v || '');
            });
        }
    }">
    <input id="{{ $uid }}" type="hidden">
    <trix-editor x-ref="trix" input="{{ $uid }}"
        class="trix-content bg-white border border-border-soft rounded-lg"></trix-editor>
</div>
