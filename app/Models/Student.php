<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Student extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logAll()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('student');
    }

    protected $fillable = [
        'institution_id',
        'course_id',
        'legajo',
        'rut_dni',
        'first_name',
        'last_name',
        'email',
        'birth_date',
        'guardian_name',
        'guardian_phone',
        'status',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    // Porcentaje de asistencia calculado
    public function getAttendancePercentageAttribute(): float
    {
        $total = $this->attendances()->count();
        if ($total === 0) {
            return 100.0;
        }
        $present = $this->attendances()->whereIn('status', ['present', 'justified', 'late'])->count();

        return round(($present / $total) * 100, 1);
    }

    // Promedio general acumulado de notas
    public function getAverageGradeAttribute(): float
    {
        $grades = $this->grades;
        if ($grades->isEmpty()) {
            return 0.0;
        }

        return round($grades->avg('score'), 1);
    }
}
