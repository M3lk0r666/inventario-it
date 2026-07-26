<?php

namespace App\Livewire\Admin\Reports;

use App\Models\AssetStatus;
use App\Models\AssetType;
use App\Models\Employee;
use App\Models\Location;
use App\Models\Problem;
use App\Services\ReportService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Url;
use Livewire\Component;

class ReportViewer extends Component
{
    use AuthorizesRequests;

    #[Url]
    public string $report = 'inventory';

    #[Url]
    public ?int $type = null;

    #[Url]
    public ?int $status = null;

    #[Url]
    public ?int $location = null;

    #[Url]
    public ?int $employee = null;

    #[Url]
    public ?string $problemStatus = null;

    #[Url]
    public ?string $date_from = null;

    #[Url]
    public ?string $date_to = null;

    public function mount(ReportService $service): void
    {
        $this->authorize('reports.view');
        if (! $service->has($this->report)) {
            $this->report = 'inventory';
        }
    }

    public function selectReport(string $key): void
    {
        $this->report = $key;
        $this->reset('type', 'status', 'location', 'employee', 'problemStatus', 'date_from', 'date_to');
    }

    protected function filters(): array
    {
        return [
            'type' => $this->type,
            'status' => $this->status,
            'location' => $this->location,
            'employee' => $this->employee,
            'problem_status' => $this->problemStatus,
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
        ];
    }

    public function render(ReportService $service)
    {
        $def = $service->get($this->report);
        $rows = $service->rows($this->report, $this->filters());

        return view('livewire.admin.reports.report-viewer', [
            'reports' => $service->reports(),
            'def' => $def,
            'rows' => $rows,
            'exportParams' => array_merge(['report' => $this->report], array_filter($this->filters(), fn ($v) => $v !== null && $v !== '')),
            'typeOptions' => AssetType::orderBy('name')->pluck('name', 'id'),
            'statusOptions' => AssetStatus::orderBy('name')->pluck('name', 'id'),
            'locationOptions' => Location::orderBy('name')->pluck('name', 'id'),
            'employeeOptions' => Employee::orderBy('name')->pluck('name', 'id'),
            'problemStatuses' => Problem::STATUSES,
        ]);
    }
}
