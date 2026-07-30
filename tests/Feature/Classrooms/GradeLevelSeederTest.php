<?php
declare(strict_types=1); namespace Tests\Feature\Classrooms;
use App\Models\GradeLevel; use Database\Seeders\GradeLevelSeeder; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class GradeLevelSeederTest extends TestCase {use RefreshDatabase; public function test_seeder_is_idempotent_and_orders_six_grade_levels():void{$this->seed(GradeLevelSeeder::class);$this->seed(GradeLevelSeeder::class);$this->assertSame(6,GradeLevel::count());$this->assertSame([1,2,3,4,5,6],GradeLevel::orderBy('sort_order')->pluck('number')->all());}}
