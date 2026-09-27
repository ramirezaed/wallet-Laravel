<?php

namespace App\Models;

use Database\Factories\FavoritoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorito extends Model
{
    /** @use HasFactory<FavoritoFactory> */
    use HasFactory;

    protected $table = 'favoritos';

    protected $fillable = [
        'cuenta_id',
        'cbu_favorito',
    ];

    public function cuentaPropietaria(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cuenta_id');
    }

    public function cuentaFavorita(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class, 'cbu_favorito', 'cbu');
    }
}
