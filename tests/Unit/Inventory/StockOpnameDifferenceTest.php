<?php
namespace Tests\Unit\Inventory; use PHPUnit\Framework\TestCase;
class StockOpnameDifferenceTest extends TestCase{public function test_difference_is_physical_minus_system():void{$this->assertSame(-3,17-20);$this->assertSame(5,25-20);}}
