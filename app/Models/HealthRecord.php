<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class HealthRecord extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = [
        'member_id', 'allergies', 'intolerances', 'medication',
        'observations', 'health_card_number', 'drive_file_id',
    ];

    /**
     * Datos médicos de menores (RGPD art. 9, categoría especial):
     * cifrados en reposo con el APP_KEY de la aplicación.
     */
    protected $casts = [
        'allergies' => 'encrypted',
        'intolerances' => 'encrypted',
        'medication' => 'encrypted',
        'observations' => 'encrypted',
        'health_card_number' => 'encrypted',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Trazabilidad RGPD: se registra QUÉ campos cambiaron, nunca su contenido
     * (el contenido médico no debe acabar en claro en activity_log).
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('health_record')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /** Redacta los valores médicos antes de persistir la entrada de actividad. */
    public function tapActivity(Activity $activity, string $eventName): void
    {
        $sensitive = ['allergies', 'intolerances', 'medication', 'observations', 'health_card_number'];

        $properties = $activity->properties->toArray();

        foreach (['attributes', 'old'] as $bag) {
            foreach ($sensitive as $field) {
                if (array_key_exists($field, $properties[$bag] ?? [])) {
                    $properties[$bag][$field] = '[redactado]';
                }
            }
        }

        $activity->properties = collect($properties);
    }
}
