<?php
namespace App\Http\Controllers\Access;
use App\Http\Controllers\Controller;use App\Models\User;use App\Services\Access\UserService;use Illuminate\Http\RedirectResponse;use Illuminate\Http\Request;
class UserStatusController extends Controller {public function activate(Request $r,User $user):RedirectResponse{(new UserService)->status($r->user(),$user,true);return back()->with('status','Akun pengguna berhasil diaktifkan.');}public function deactivate(Request $r,User $user):RedirectResponse{(new UserService)->status($r->user(),$user,false);return back()->with('status','Akun pengguna berhasil dinonaktifkan.');}}
