<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Nombres de los días
    public static function dayName(int $day): string
    {
        return [
            0 => 'Domingo',
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
        ][$day] ?? 'Desconocido';
    }

    // Accessor para obtener el nombre del día
    public function getDayNameAttribute(): string
    {
        return self::dayName($this->day_of_week);
    }

    protected $appends = ['day_name'];
}