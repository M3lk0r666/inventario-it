<?php

namespace App\Models;

use App\Models\Concerns\TracksActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;
    use TracksActivity;

    protected $fillable = [
        'employee_number', 'name', 'position', 'department_id', 'location_id',
        'email', 'phone', 'status', 'user_id', 'notes',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /** Cuenta de acceso al sistema (opcional). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Cuentas corporativas (correo, dominio, VPN, sistemas). */
    public function accounts(): HasMany
    {
        return $this->hasMany(EmployeeAccount::class);
    }

    /** Histórico completo de asignaciones. */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /** Asignaciones vigentes (sin devolución). */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->whereNull('returned_at');
    }

    public function responsiveLetters(): HasMany
    {
        return $this->hasMany(ResponsiveLetter::class);
    }

    /** Licencias asignadas directamente al empleado. */
    public function licenseAssignments(): MorphMany
    {
        return $this->morphMany(LicenseAssignment::class, 'assignable');
    }

    public function consumableMovements(): HasMany
    {
        return $this->hasMany(ConsumableMovement::class);
    }
}
