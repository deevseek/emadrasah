<?php
declare(strict_types=1); namespace App\Http\Controllers\Classrooms; use App\Http\Controllers\Controller; use App\Services\Classrooms\ClassroomGuideService; use Illuminate\View\View;
class ClassroomGuideController extends Controller {public function __invoke(ClassroomGuideService $s):View{(request()->user()->can('classrooms.view')||request()->user()->can('classrooms.view-own'))?:abort(403);return view('classrooms.guide',['sections'=>$s->sections()]);}}
