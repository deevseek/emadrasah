<?php

declare(strict_types=1);
namespace App\Http\Controllers\Access;
use App\Http\Controllers\Controller;
use App\Http\Requests\Access\StoreUserRequest;
use App\Http\Requests\Access\UpdateUserRequest;
use App\Models\LoginHistory;
use App\Models\Role;
use App\Models\User;
use App\Services\Access\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(private UserService $service){}
    public function index(Request $request):View{$query=User::query()->with('roles')->when($request->filled('search'),fn($q)=>$q->where(fn($q)=>$q->where('name','like','%'.$request->search.'%')->orWhere('username','like','%'.$request->search.'%')->orWhere('email','like','%'.$request->search.'%')))->when($request->filled('role'),fn($q)=>$q->role($request->role))->when($request->status==='active',fn($q)=>$q->where('is_active',true))->when($request->status==='inactive',fn($q)=>$q->where('is_active',false));return view('access.users.index',['users'=>$query->latest()->paginate(15)->withQueryString(),'roles'=>Role::orderBy('display_name')->get(),'stats'=>['total'=>User::count(),'active'=>User::where('is_active',true)->count(),'inactive'=>User::where('is_active',false)->count(),'never'=>User::whereNull('last_login_at')->count()]]);}
    public function create(Request $request):View{return view('access.users.form',['user'=>new User(['name'=>$request->string('name')->trim()->toString(),'email'=>$request->string('email')->trim()->lower()->toString()]),'roles'=>$this->roles($request),'editing'=>false]);}
    public function store(StoreUserRequest $request):RedirectResponse{$user=$this->service->create($request->user(),$request->validated());return redirect()->route('users.show',$user)->with('status','Pengguna berhasil ditambahkan.');}
    public function show(User $user):View{$user->load('roles');$lastLogin=LoginHistory::where('user_id',$user->id)->where('successful',true)->latest('attempted_at')->first();return view('access.users.show',compact('user','lastLogin'));}
    public function edit(Request $request,User $user):View{$this->service->guard($request->user(),$user);return view('access.users.form',['user'=>$user->load('roles'),'roles'=>$this->roles($request),'editing'=>true]);}
    public function update(UpdateUserRequest $request,User $user):RedirectResponse{$this->service->update($request->user(),$user,$request->validated());return redirect()->route('users.show',$user)->with('status','Data pengguna berhasil diperbarui.');}
    private function roles(Request $request){return Role::query()->when(!$request->user()->hasRole('super-admin'),fn($q)=>$q->where('name','!=','super-admin'))->inDisplayOrder()->get();}
}
