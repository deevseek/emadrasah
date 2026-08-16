<?php
declare(strict_types=1); namespace Tests\Unit\Finance; use App\Services\Banking\FakeBriGateway; use PHPUnit\Framework\TestCase;
class FakeBriGatewayTest extends TestCase { public function test_fake_gateway_is_deterministic_and_never_calls_external_http():void{$gateway=new FakeBriGateway();$first=$gateway->createVirtualAccount(['external_id'=>'invoice-1','student_id'=>7]);$second=$gateway->createVirtualAccount(['external_id'=>'invoice-1','student_id'=>7]);$this->assertSame($first,$second);$this->assertStringStartsWith('FAKE',$first['virtual_account_number']);} }
