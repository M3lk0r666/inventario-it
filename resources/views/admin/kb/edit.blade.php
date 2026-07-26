<x-admin-layout :title="isset($article) ? 'Editar artículo' : 'Nuevo artículo'"
    :breadcrumbs="[
        ['name' => 'Herramientas'],
        ['name' => 'Base de conocimientos', 'href' => route('admin.kb.index')],
        ['name' => isset($article) ? 'Editar' : 'Nuevo'],
    ]">

    @isset($article)
        <livewire:admin.kb.kb-article-edit :article="$article" />
    @else
        <livewire:admin.kb.kb-article-edit />
    @endisset
</x-admin-layout>
