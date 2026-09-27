<?php

namespace App\Services\Account;

use App\DTO\Account\createAccountDTO;
use App\Models\Cuenta;
use App\Generators\CbuGenerator;


class createAccountService
{
    public function create(createAccountDTO $data): Cuenta
    {
        $cbu = CbuGenerator::generate();
        //array merge une los dos arreglos, los datos que recibe del dto + el cbu
        return Cuenta::create(array_merge(
            $data->toArray(),
            ['cbu' => $cbu]
        ));
    }
}
