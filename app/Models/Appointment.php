<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'case_manager_id',
        'client_id',
        'title',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'notes',
    ];

    protected $casts = [
        'appointment_date' => 'date',
    ];

    // ─── Relaciones ───────────────────────────────────────────

    public function caseManager()
    {
        return $this->belongsTo(User::class, 'case_manager_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    // ─── Scopes ───────────────────────────────────────────────

    public function scopeForCaseManager($query, $id)
    {
        return $query->where('case_manager_id', $id);
    }

    public function scopeForClient($query, $id)
    {
        return $query->where('client_id', $id);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // ─── Helpers ──────────────────────────────────────────────

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'   => 'Pendiente',
            'confirmed' => 'Confirmada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
            'no_show'   => 'No se presentó',
            default     => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending'   => '#f59e0b',  // amarillo
            'confirmed' => '#3b82f6',  // azul
            'cancelled' => '#ef4444',  // rojo
            'completed' => '#10b981',  // verde
            'no_show'   => '#6b7280',  // gris
            default     => '#6b7280',
        };
    }
}