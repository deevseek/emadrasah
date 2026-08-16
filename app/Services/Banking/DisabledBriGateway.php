<?php
declare(strict_types=1); namespace App\Services\Banking; use App\Contracts\Banking\{BankPaymentGateway,BankTransferGateway}; use RuntimeException;
class DisabledBriGateway implements BankPaymentGateway,BankTransferGateway { private function stop():never{throw new RuntimeException('Integrasi BRI dinonaktifkan sampai produk API dan kredensial resmi tersedia.');} public function createVirtualAccount(array $request):array{$this->stop();} public function transfer(array $request):array{$this->stop();} public function inquire(string $externalId):array{$this->stop();} }
