<?php

declare(strict_types=1);

namespace App\Http\Controllers\Btaq;

use App\Enums\BtaqMaterialCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Btaq\StoreBtaqMaterialRequest;
use App\Http\Requests\Btaq\UpdateBtaqMaterialRequest;
use App\Models\BtaqLevel;
use App\Models\BtaqMaterial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BtaqMaterialController extends Controller
{
    public function index(Request $request): View { $materials = BtaqMaterial::query()->with('level')->when($request->q, fn ($q,$v)=>$q->where('name','like',"%$v%"))->when($request->btaq_level_id, fn ($q,$v)=>$q->where('btaq_level_id',$v))->when($request->category, fn ($q,$v)=>$q->where('category',$v))->paginate(15); return view('btaq.materials.index', ['materials'=>$materials,'levels'=>BtaqLevel::orderBy('sequence')->get(),'categories'=>BtaqMaterialCategory::cases()]); }
    public function create(): View { return view('btaq.materials.form', ['material'=>new BtaqMaterial,'levels'=>BtaqLevel::where('is_active',true)->get(),'categories'=>BtaqMaterialCategory::cases()]); }
    public function store(StoreBtaqMaterialRequest $request): RedirectResponse { BtaqMaterial::create($request->validated()); return redirect()->route('btaq-materials.index')->with('status','Materi BTAQ disimpan.'); }
    public function show(BtaqMaterial $btaqMaterial): View { return view('btaq.materials.show',['material'=>$btaqMaterial]); }
    public function edit(BtaqMaterial $btaqMaterial): View { return view('btaq.materials.form',['material'=>$btaqMaterial,'levels'=>BtaqLevel::get(),'categories'=>BtaqMaterialCategory::cases()]); }
    public function update(UpdateBtaqMaterialRequest $request, BtaqMaterial $btaqMaterial): RedirectResponse { $btaqMaterial->update($request->validated()); return redirect()->route('btaq-materials.show',$btaqMaterial)->with('status','Materi diperbarui.'); }
}
