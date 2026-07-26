<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeAccount extends Model
{
    use SoftDeletes;
    use TracksActivity;

    public const TYPES = [
        'email' => 'Correo electrónico',
        'domain' => 'Cuenta de dominio',
        'vpn' => 'VPN',
        'system' => 'Sistema interno',
    ];

    public const STATUSES = [
        'active' => 'Activa',
        'suspended' => 'Suspendida',
        'revoked' => 'Revocada',
    ];

    protected $fillable = [
        'employee_id', 'account_type', 'system_name', 'identifier', 'status', 'notes',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
