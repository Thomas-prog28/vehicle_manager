<?php

namespace App\Tests\Unit;

use App\Entity\FuelLog;
use App\Entity\Invoice;
use App\Entity\Vehicle;
use PHPUnit\Framework\TestCase;

final class VehicleTest extends TestCase 
{
    public function testPlateIsNormalizedToStandardFormat():void
    {
        $vehicle = new Vehicle();

        //Format SIV (depuis 2009) saisi en minuscules avec espaces
        $vehicle->setPlate(' ab 123 cd ');
        $this->assertSame('AB-123-CD', $vehicle->getPlate());

        //Format ancien(avant 2009 saisi sans espaces
        $vehicle->setPlate('1234ab28');
        $this->assertSame('1234 AB 28', $vehicle->getPlate());
    }

    public function testCurrentMileageReturnHighestBetweenLastInvoiceAndFuelLog():void
    {
        $vehicle = new vehicle();
        $this->assertSame(0, $vehicle->getCurrentMileage());

        $invoice = new Invoice();
        $invoice->setMileage(133463);
        $vehicle->addInvoice($invoice);

        $this->assertSame(133463, $vehicle->getCurrentMileage());

        $fuelLog = new FuelLog();
        $fuelLog->setMileage(134120);
        $vehicle->addFuelLog($fuelLog);

        $this->assertSame(134120, $vehicle->getCurrentMileage());
    }
}