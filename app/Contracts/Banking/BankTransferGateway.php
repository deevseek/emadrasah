<?php
declare(strict_types=1); namespace App\Contracts\Banking; interface BankTransferGateway { public function transfer(array $request):array; public function inquire(string $externalId):array; }
