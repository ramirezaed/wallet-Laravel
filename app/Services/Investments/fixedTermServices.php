<?php

namespace App\Services\Investments;

use App\DTO\Investments\fixedTermDTO;
use Carbon\Carbon;

class fixedTermServices
{

    private const TNA = 0.30;

    public function create(FixedTermDTO $data): array
    {
        $interes = $data->monto * self::TNA * ($data->plazo / 365);
        $total   = $data->monto + $interes;
        $fechaCreacion = Carbon::now();
        $fechaFin      = $fechaCreacion->copy()->addDays($data->plazo);

        return [
            'monto'          => $data->monto,
            'plazo'          => $data->plazo,
            'tna'            => self::TNA,
            'interes'        => round($interes, 2),
            'total'          => round($total, 2),
            'created_at'     => $fechaCreacion->toDateTimeString(),
            'fecha_fin'      => $fechaFin->toDateTimeString(),
        ];
    }
}
