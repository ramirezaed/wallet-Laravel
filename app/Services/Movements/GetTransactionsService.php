<?php

namespace App\Services\Movements;

use App\Models\Movimiento;

class GetTransactionsService
{
    public function GetTransactios()
    {
        //crea la cuneta del usario(el password se hashea en el modelo)
        $cuentaId = auth("api")->user()->cuenta->id;
        $query = Movimiento::query()
            ->where("cuenta_id", $cuentaId)->orderByDesc("created_at");
        return $query->paginate(15);
    }
}
