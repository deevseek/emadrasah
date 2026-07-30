<?php

declare(strict_types=1);
namespace App\Http\Controllers\Access;
use App\Http\Controllers\Controller;use App\Http\Requests\Access\StoreRoleRequest;use App\Http\Requests\Access\UpdateRoleRequest;use App\Models\Role;use App\Services\Access\RoleService;use Illuminate\Http\RedirectResponse;use Illuminate\Http\Request;use Illuminate\View\View;use Spatie\Permission\Models\Permission;
class RoleController extends Controller
{
 public function __construct(private RoleService $service){}
 public function index():View{return view('access.roles.index',['roles'=>Role::withCount(['users','permissions'])->orderByDesc('is_system')->orderBy('display_name')->get(),'stats'=>['total'=>Role::count(),'system'=>Role::where('is_system',true)->count(),'custom'=>Role::where('is_system',false)->count(),'permissions'=>Permission::count()]]);}
 public function show(Role $role):View{return view('access.roles.show',compact('role'));}
 public function create():View{return view('access.roles.form',['role'=>new Role,'groups'=>config('permissions'),'selected'=>[],'editing'=>false]);}
 public function store(StoreRoleRequest $r):RedirectResponse{$role=$this->service->create($r->user(),$r->validated());return redirect()->route('roles.show',$role)->with('status','Role berhasil ditambahkan.');}
 public function edit(Role $role):View{abort_if($role->name==='super-admin',403,'Hak akses Super Admin tidak dapat diubah.');return view('access.roles.form',['role'=>$role,'groups'=>config('permissions'),'selected'=>$role->permissions->pluck('name')->all(),'editing'=>true]);}
 public function update(UpdateRoleRequest $r,Role $role):RedirectResponse{$this->service->update($r->user(),$role,$r->validated());return redirect()->route('roles.show',$role)->with('status','Role berhasil diperbarui.');}
 public function destroy(Request $r,Role $role):RedirectResponse{$this->service->delete($r->user(),$role);return redirect()->route('roles.index')->with('status','Role berhasil dihapus.');}
}
