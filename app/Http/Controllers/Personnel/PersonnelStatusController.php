<?php
declare(strict_types=1); namespace App\Http\Controllers\Personnel;
use App\Http\Controllers\Controller; use App\Models\Personnel; use App\Services\Personnel\PersonnelService; use Illuminate\Http\{RedirectResponse,Request};
class PersonnelStatusController extends Controller {public function __construct(private PersonnelService $service){}public function activate(Request $r,Personnel $personnel):RedirectResponse{$this->service->activate($personnel,$r->user());return back()->with('status','Personalia berhasil diaktifkan.');}public function deactivate(Request $r,Personnel $personnel):RedirectResponse{$this->service->deactivate($personnel,$r->user(),$r->boolean('deactivate_account'));return back()->with('status','Personalia berhasil dinonaktifkan.');}}
