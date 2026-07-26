<x-admin-layout :title="'Empleado — ' . $employee->name"
    :breadcrumbs="[
        ['name' => 'Gestión'],
        ['name' => 'Empleados', 'href' => route('admin.employees.index')],
        ['name' => $employee->name],
    ]">

    <livewire:admin.employees.employee-detail :employee-id="$employee->id" />
    <livewire:admin.employees.employee-form />
</x-admin-layout>
