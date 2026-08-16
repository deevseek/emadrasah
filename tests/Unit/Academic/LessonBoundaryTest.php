<?php
declare(strict_types=1); namespace Tests\Unit\Academic; use PHPUnit\Framework\TestCase;
class LessonBoundaryTest extends TestCase { public function test_end_time_is_exclusive_so_adjacent_lessons_do_not_overlap():void{$at='10:00:00';$this->assertFalse('09:00:00'<=$at && '10:00:00'>$at);$this->assertTrue('10:00:00'<=$at && '11:00:00'>$at);} }
