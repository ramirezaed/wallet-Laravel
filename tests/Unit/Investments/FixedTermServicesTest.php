<?php

namespace Tests\Unit\Services\Investments;

use App\DTO\Investments\FixedTermDTO;
use App\Services\Investments\fixedTermServices;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FixedTermServicesTest extends TestCase
{
    private fixedTermServices $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-03-20 12:00:00');
        $this->service = new fixedTermServices();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_calcula_con_monto_1_y_plazo_365_exacto(): void
    {
        $dto = new FixedTermDTO(monto: 1.00, plazo: 365);
        $resultado = $this->service->create($dto);

        $this->assertEquals(1.00, $resultado['monto']);
        $this->assertEquals(365, $resultado['plazo']);
        $this->assertEquals(0.30, $resultado['interes']);
        $this->assertEquals(1.30, $resultado['total']);
        $this->assertEquals('2027-03-20 12:00:00', $resultado['fecha_fin']);
    }
}
