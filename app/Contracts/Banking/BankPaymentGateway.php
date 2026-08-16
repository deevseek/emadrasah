<?php
declare(strict_types=1); namespace App\Contracts\Banking; interface BankPaymentGateway { public function createVirtualAccount(array $request):array; public function inquire(string $externalId):array; }
