@props(['model'])

@php($uid = 'trix_'.\Illuminate\Support\Str::random(8))

{{--
  Editor de texto enriquecido (Trix) enlazado a una propiedad Livewire.
  Trix se empaqueta localmente vía Vite (resources/js/app.js).
  Las imágenes pegadas/soltadas se suben al servidor y se incrustan como <img>
  (no como data-URI base64), para que persistan y se muestren correctamente.
--}}
<div wire:ignore
    x-data="{
        value: @entangle($model).live,
        bound: false,
        uploadUrl: '{{ route('admin.kb.attachment') }}',
        csrf: '{{ csrf_token() }}',
        init() {
            const el = this.$refs.trix;
            const setup = () => {
                if (this.bound) return;
                this.bound = true;
                el.editor.loadHTML(this.value || '');
                el.addEventListener('trix-change', () => { this.value = el.value; });
            };
            if (el.editor) setup();
            else el.addEventListener('trix-initialize', setup, { once: true });

            // Subida de adjuntos (imágenes) al servidor.
            el.addEventListener('trix-attachment-add', (event) => {
                const attachment = event.attachment;
                if (!attachment.file) return; // ya tiene URL (contenido existente)
                this.uploadAttachment(attachment);
            });

            this.$watch('value', (v) => {
                if (el.editor && (v || '') !== el.value) el.editor.loadHTML(v || '');
            });
        },
        uploadAttachment(attachment) {
            const form = new FormData();
            form.append('file', attachment.file);

            const xhr = new XMLHttpRequest();
            xhr.open('POST', this.uploadUrl, true);
            xhr.setRequestHeader('X-CSRF-TOKEN', this.csrf);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    attachment.setUploadProgress((e.loaded / e.total) * 100);
                }
            });

            xhr.addEventListener('load', () => {
                if (xhr.status >= 200 && xhr.status < 300) {
                    const res = JSON.parse(xhr.responseText);
                    attachment.setAttributes({ url: res.url, href: res.url });
                } else {
                    attachment.remove();
                    window.dispatchEvent(new CustomEvent('trix-upload-error'));
                }
            });

            xhr.addEventListener('error', () => attachment.remove());
            xhr.send(form);
        }
    }">
    <input id="{{ $uid }}" type="hidden">
    <trix-editor x-ref="trix" input="{{ $uid }}"
        class="trix-content bg-white border border-border-soft rounded-lg"></trix-editor>
</div>
