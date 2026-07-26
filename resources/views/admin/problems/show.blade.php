<x-admin-layout :title="'Problema — ' . \Illuminate\Support\Str::limit($problem->title, 40)"
    :breadcrumbs="[
        ['name' => 'Soporte'],
        ['name' => 'Problemas', 'href' => route('admin.problems.index')],
        ['name' => \Illuminate\Support\Str::limit($problem->title, 30)],
    ]">

    <livewire:admin.problems.problem-detail :problem-id="$problem->id" />
    <livewire:admin.problems.problem-form />
</x-admin-layout>
