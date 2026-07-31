<?php
declare(strict_types=1);namespace App\Http\Controllers\TeachingAssignments;use App\Http\Controllers\Controller;use Illuminate\View\View;
class TeachingAssignmentGuideController extends Controller {public function __invoke():View{abort_unless(request()->user()->can('teaching-assignments.view')||request()->user()->can('teaching-assignments.view-own'),403);return view('teaching-assignments.guide');}}
