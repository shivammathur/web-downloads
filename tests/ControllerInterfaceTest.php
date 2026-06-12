<?php
declare(strict_types=1);

use App\Http\ControllerInterface;
use PHPUnit\Framework\TestCase;

class ControllerInterfaceTest extends TestCase {
    public function testInterfaceExists() {
        $this->assertTrue(interface_exists(ControllerInterface::class), "ControllerInterface should exist.");
    }
}
