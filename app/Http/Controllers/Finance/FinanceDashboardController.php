<?php
declare(strict_types=1);namespace App\Http\Controllers\Finance;use App\Http\Controllers\Controller;use App\Services\Finance\FinanceDashboardService;class FinanceDashboardController extends Controller{public function __invoke(FinanceDashboardService $service){return view('finance.dashboard',$service->data());}}
