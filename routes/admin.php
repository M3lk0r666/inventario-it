<?php

use App\Models\Asset;
use App\Models\AssetStatus;
use App\Models\ResponsiveLetter;
use App\Services\ResponsiveLetterService;
use App\Support\CatalogRegistry;
use Illuminate\Support\Facades\Route;

Route::get('/', function (\App\Services\AlertService $alerts) {
    $bajaId = \App\Models\AssetStatus::where('slug', 'baja')->value('id');
    $assignedId = \App\Models\AssetStatus::where('slug', 'asignado')->value('id');
    $repairId = \App\Models\AssetStatus::where('slug', 'reparacion')->value('id');
    $assignableIds = \App\Models\AssetStatus::where('is_assignable', true)->pluck('id');

    return view('admin.dashboard', [
        'cards' => [
            'assets' => \App\Models\Asset::count(),
            'assigned' => $assignedId ? \App\Models\Asset::where('asset_status_id', $assignedId)->count() : 0,
            'available' => \App\Models\Asset::whereIn('asset_status_id', $assignableIds)->count(),
            'repair' => $repairId ? \App\Models\Asset::where('asset_status_id', $repairId)->count() : 0,
            'employees' => \App\Models\Employee::where('status', 'active')->count(),
            'licenses' => \App\Models\License::count(),
            'consumables' => \App\Models\Consumable::count(),
            'open_problems' => \App\Models\Problem::whereIn('status', ['new', 'in_progress'])->count(),
        ],
        'byType' => \App\Models\AssetType::withCount('assets')->orderByDesc('assets_count')->get(),
        'byStatus' => \App\Models\AssetStatus::withCount('assets')->orderByDesc('assets_count')->get(),
        'byLocation' => \App\Models\Location::withCount('assets')->orderByDesc('assets_count')->limit(8)->get(),
        'byManufacturer' => \App\Models\Manufacturer::withCount('assets')->orderByDesc('assets_count')->limit(8)->get(),
        'alerts' => [
            'summary' => $alerts->summary(),
            'license_renewals' => $alerts->licenseRenewals()->take(5),
            'warranties' => $alerts->warrantiesExpiring()->take(5),
            'low_stock' => $alerts->lowStock()->take(5),
        ],
        'recentAssignments' => \App\Models\Assignment::with(['asset', 'employee'])
            ->latest('assigned_at')->limit(6)->get(),
        'recentProblems' => \App\Models\Problem::with(['asset'])
            ->latest('reported_at')->limit(6)->get(),
    ]);
})->name('dashboard');

/*
|--------------------------------------------------------------------------
| Activos
|--------------------------------------------------------------------------
*/
Route::middleware('permission:assets.view')->group(function () {
    Route::get('/activos', function () {
        $assignedId = AssetStatus::where('slug', 'asignado')->value('id');
        $repairId = AssetStatus::where('slug', 'reparacion')->value('id');
        $availableIds = AssetStatus::where('is_assignable', true)->pluck('id');

        return view('admin.assets.index', [
            'stats' => [
                'total' => Asset::count(),
                'assigned' => $assignedId ? Asset::where('asset_status_id', $assignedId)->count() : 0,
                'available' => Asset::whereIn('asset_status_id', $availableIds)->count(),
                'repair' => $repairId ? Asset::where('asset_status_id', $repairId)->count() : 0,
            ],
        ]);
    })->name('assets.index');

    Route::get('/activos/{asset}', function (Asset $asset) {
        return view('admin.assets.show', ['asset' => $asset]);
    })->whereNumber('asset')->name('assets.show');
});

/*
|--------------------------------------------------------------------------
| Asignaciones y cartas responsivas
|--------------------------------------------------------------------------
*/
Route::get('/asignaciones', function () {
    return view('admin.assignments.index');
})->middleware('permission:assignments.view')->name('assignments.index');

Route::middleware('permission:responsive_letters.view')->group(function () {
    Route::get('/cartas', function () {
        return view('admin.letters.index');
    })->name('letters.index');

    Route::get('/cartas/{letter}/pdf', function (ResponsiveLetter $letter, ResponsiveLetterService $service) {
        // Regenera siempre para reflejar la plantilla y datos vigentes.
        // (En producción se puede congelar el PDF al firmar la carta.)
        $service->generatePdf($letter);

        return response()->download($service->ensurePdf($letter), "{$letter->folio}.pdf");
    })->whereNumber('letter')->name('letters.pdf');

    Route::get('/cartas/{letter}/reimprimir', function (ResponsiveLetter $letter, ResponsiveLetterService $service) {
        $service->generatePdf($letter);

        return response()->download($service->ensurePdf($letter), "{$letter->folio}.pdf");
    })->whereNumber('letter')->middleware('permission:responsive_letters.reprint')->name('letters.reprint');

    // Evidencia: carta físicamente firmada
    Route::get('/cartas/{letter}/firmada', function (ResponsiveLetter $letter) {
        abort_unless($letter->signed_document_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($letter->signed_document_path), 404);

        return response()->file(\Illuminate\Support\Facades\Storage::disk('public')->path($letter->signed_document_path));
    })->whereNumber('letter')->name('letters.signed');
});

/*
|--------------------------------------------------------------------------
| Consumibles
|--------------------------------------------------------------------------
*/
Route::middleware('permission:consumables.view')->group(function () {
    Route::get('/consumibles', function () {
        return view('admin.consumables.index', [
            'stats' => [
                'total' => \App\Models\Consumable::count(),
                'low' => \App\Models\Consumable::whereColumn('stock', '<=', 'min_stock')->count(),
                'units' => (int) \App\Models\Consumable::sum('stock'),
            ],
        ]);
    })->name('consumables.index');

    Route::get('/consumibles/{consumable}', function (\App\Models\Consumable $consumable) {
        return view('admin.consumables.show', ['consumable' => $consumable]);
    })->whereNumber('consumable')->name('consumables.show');
});

/*
|--------------------------------------------------------------------------
| Gestión: Proveedores
|--------------------------------------------------------------------------
*/
Route::middleware('permission:suppliers.view')->group(function () {
    Route::get('/proveedores', function () {
        return view('admin.suppliers.index');
    })->name('suppliers.index');

    Route::get('/proveedores/{supplier}', function (\App\Models\Supplier $supplier) {
        $supplier->loadCount(['assets', 'licenses'])->load(['assets.type', 'assets.status']);

        return view('admin.suppliers.show', ['supplier' => $supplier]);
    })->whereNumber('supplier')->name('suppliers.show');
});

/*
|--------------------------------------------------------------------------
| Herramientas: Recordatorios y Base de conocimientos
|--------------------------------------------------------------------------
*/
Route::get('/recordatorios', function () {
    return view('admin.reminders.index');
})->middleware('permission:reminders.view')->name('reminders.index');

Route::middleware('permission:kb.view')->group(function () {
    Route::get('/base-conocimientos', function () {
        return view('admin.kb.index');
    })->name('kb.index');

    Route::get('/base-conocimientos/nuevo', function () {
        return view('admin.kb.edit');
    })->middleware('permission:kb.create')->name('kb.create');

    Route::get('/base-conocimientos/{article}/editar', function (\App\Models\KbArticle $article) {
        return view('admin.kb.edit', ['article' => $article]);
    })->whereNumber('article')->middleware('permission:kb.edit')->name('kb.edit');
});

/*
|--------------------------------------------------------------------------
| Soporte: Problemas
|--------------------------------------------------------------------------
*/
Route::middleware('permission:problems.view')->group(function () {
    Route::get('/problemas', function () {
        return view('admin.problems.index', [
            'stats' => [
                'total' => \App\Models\Problem::count(),
                'open' => \App\Models\Problem::whereIn('status', ['new', 'in_progress'])->count(),
                'critical' => \App\Models\Problem::where('priority', 'critical')->whereIn('status', ['new', 'in_progress'])->count(),
                'cost' => (float) \App\Models\Problem::sum('cost'),
            ],
        ]);
    })->name('problems.index');

    Route::get('/problemas/{problem}', function (\App\Models\Problem $problem) {
        return view('admin.problems.show', ['problem' => $problem]);
    })->whereNumber('problem')->name('problems.show');
});

/*
|--------------------------------------------------------------------------
| Licencias
|--------------------------------------------------------------------------
*/
Route::middleware('permission:licenses.view')->group(function () {
    Route::get('/licencias', function () {
        return view('admin.licenses.index', [
            'stats' => [
                'total' => \App\Models\License::count(),
                'expiring' => \App\Models\License::whereNotNull('expires_at')
                    ->whereBetween('expires_at', [now(), now()->addDays(60)])->count(),
                'expired' => \App\Models\License::whereNotNull('expires_at')
                    ->where('expires_at', '<', now())->count(),
                'total_seats' => (int) \App\Models\License::sum('seats'),
                'used_seats' => \App\Models\LicenseAssignment::whereNull('released_at')->count(),
                'renewal_alerts' => \App\Models\License::needingRenewal()->count(),
            ],
        ]);
    })->name('licenses.index');

    Route::get('/licencias/{license}', function (\App\Models\License $license) {
        return view('admin.licenses.show', ['license' => $license]);
    })->whereNumber('license')->name('licenses.show');
});

/*
|--------------------------------------------------------------------------
| Administración: Usuarios, Empleados, Configuración
|--------------------------------------------------------------------------
*/
Route::middleware('permission:users.view')->group(function () {
    Route::get('/usuarios', function () {
        return view('admin.users.index');
    })->name('users.index');

    Route::get('/roles', function () {
        return view('admin.roles.index');
    })->middleware('permission:users.edit')->name('roles.index');
});

Route::middleware('permission:employees.view')->group(function () {
    Route::get('/empleados', function () {
        return view('admin.employees.index');
    })->name('employees.index');

    Route::get('/empleados/{employee}', function (\App\Models\Employee $employee) {
        return view('admin.employees.show', ['employee' => $employee]);
    })->whereNumber('employee')->name('employees.show');
});

Route::middleware('permission:settings.view')->group(function () {
    Route::get('/configuracion', function () {
        return view('admin.settings.index');
    })->name('settings.index');
});

Route::middleware('permission:activity.view')->group(function () {
    Route::get('/auditoria', function () {
        return view('admin.audit.index');
    })->name('audit.index');
});

/*
|--------------------------------------------------------------------------
| Reportes
|--------------------------------------------------------------------------
*/
Route::middleware('permission:reports.view')->group(function () {
    Route::get('/reportes', function () {
        return view('admin.reports.index');
    })->name('reports.index');

    Route::get('/reportes/exportar', function (\Illuminate\Http\Request $request, \App\Services\ReportService $service) {
        abort_unless(auth()->user()->can('reports.export'), 403);

        $key = $request->string('report')->toString();
        abort_unless($service->has($key), 404);

        $def = $service->get($key);
        $filters = $request->only(['type', 'status', 'location', 'employee', 'problem_status', 'date_from', 'date_to']);
        $rows = $service->rows($key, $filters);
        $format = $request->string('format')->toString();
        $filename = 'reporte_'.$key.'_'.now()->format('Ymd_His');

        if ($format === 'pdf') {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.report', [
                'def' => $def,
                'rows' => $rows,
                'companyName' => \App\Models\Setting::get('company_name', config('app.name')),
            ])->setPaper('letter', 'landscape');

            return $pdf->download($filename.'.pdf');
        }

        return response()->streamDownload(function () use ($def, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $def['columns']);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    })->name('reports.export');
});

/*
|--------------------------------------------------------------------------
| Catálogos
|--------------------------------------------------------------------------
*/
Route::middleware('permission:catalogs.view')->group(function () {
    Route::get('/catalogos/{catalog?}', function (?string $catalog = null) {
        $catalog ??= array_key_first(CatalogRegistry::menuItems());
        // Solo catálogos visibles en menú son navegables por página (proveedores tiene módulo propio).
        abort_unless(array_key_exists($catalog, CatalogRegistry::menuItems()), 404);

        return view('admin.catalogs.index', ['catalog' => $catalog]);
    })->name('catalogs.index');
});
