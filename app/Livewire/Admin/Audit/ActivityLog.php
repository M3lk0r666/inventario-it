<?php

namespace App\Livewire\Admin\Audit;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

/**
 * Bitácora global de actividad (spatie/activitylog): quién, qué, cuándo.
 */
class ActivityLog extends Component
{
    use AuthorizesRequests;
    use WithPagination;

    public string $logName = '';

    public string $event = '';

    public function mount(): void
    {
        $this->authorize('activity.view');
    }

    public function render()
    {
        $activities = Activity::with('causer')
            ->when($this->logName, fn ($q) => $q->where('log_name', $this->logName))
            ->when($this->event, fn ($q) => $q->where('event', $this->event))
            ->latest()
            ->paginate(25);

        return view('livewire.admin.audit.activity-log', [
            'activities' => $activities,
            'logNames' => Activity::query()->distinct()->orderBy('log_name')->pluck('log_name')->filter(),
        ]);
    }
}
