<?php

declare(strict_types=1);

namespace App\Http\Controllers\Foundation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Foundation\UserRequest;
use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View { $q=User::query()->with('roles')->when($request->q,fn($x,$v)=>$x->where(fn($s)=>$s->where('name','like',"%$v%")->orWhere('email','like',"%$v%")))->when($request->status!==null&&$request->status!=='',fn($x)=>$x->where('is_active',(bool)$request->integer('status')))->when($request->role,fn($x,$v)=>$x->whereHas('roles',fn($r)=>$r->where('roles.id',$v))); return view('foundation.users.index',['users'=>$q->paginate(15),'roles'=>Role::orderBy('display_name')->get()]); }
    public function create(): View { return view('foundation.users.form',['user'=>new User(),'roles'=>Role::orderBy('display_name')->get()]); }
    public function store(UserRequest $request, ActivityLogger $logger): RedirectResponse { $data=$request->safe()->except('roles'); $data['password']=Hash::make($data['password']); $user=User::create($data); $user->roles()->sync($request->input('roles',[])); $logger->log('user.created',$user,[],$user->toArray()); return redirect()->route('users.show',$user); }
    public function show(User $user): View { return view('foundation.users.show',['user'=>$user->load('roles'),'histories'=>LoginHistory::where('user_id',$user->id)->latest('attempted_at')->paginate(10)]); }
    public function edit(User $user): View { return view('foundation.users.form',['user'=>$user->load('roles'),'roles'=>Role::orderBy('display_name')->get()]); }
    public function update(UserRequest $request, User $user, ActivityLogger $logger): RedirectResponse { $old=$user->toArray(); $data=$request->safe()->except('roles'); if(empty($data['password'])) unset($data['password']); else $data['password']=Hash::make($data['password']); $user->update($data); $this->guardLastSuperAdmin($user,$request->input('roles',[])); $user->roles()->sync($request->input('roles',[])); $logger->log('user.updated',$user,$old,$user->fresh()->toArray()); return redirect()->route('users.show',$user); }
    public function toggle(User $user, ActivityLogger $logger): RedirectResponse { if($user->is_active) $this->guardLastSuperAdmin($user,[]); $old=$user->toArray(); $user->update(['is_active'=>!$user->is_active]); $logger->log($user->is_active?'user.activated':'user.deactivated',$user,$old,$user->fresh()->toArray()); return back(); }
    private function guardLastSuperAdmin(User $user, array $newRoles): void { $super=Role::where('name','super-admin')->first(); if(! $super) return; $isSuper=$user->roles()->whereKey($super->id)->exists(); $keeps=in_array($super->id,array_map('intval',$newRoles),true); $others=User::where('id','!=',$user->id)->where('is_active',true)->whereHas('roles',fn($q)=>$q->whereKey($super->id))->exists(); if($isSuper && ! $keeps && ! $others) throw ValidationException::withMessages(['roles'=>'Super Admin terakhir tidak boleh kehilangan akses.']); }
}
