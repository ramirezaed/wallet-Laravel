<?php

namespace App\Models;

use Database\Factories\MovimientoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    /** @use HasFactory<MovimientoFactory> */
    use HasFactory;

    protected $table = 'movimientos';

    protected $fillable = [
        'cuenta_id',
        'tipo',
        'monto',
        'cbu_contraparte',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
        ];
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }
}
