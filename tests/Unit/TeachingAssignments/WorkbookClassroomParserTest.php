<?php

declare(strict_types=1);

namespace Tests\Unit\TeachingAssignments;

use App\Models\{Classroom, GradeLevel};
use App\Services\TeachingAssignments\{WorkbookClassroomParser, WorkbookNameMatcher};
use PHPUnit\Framework\TestCase;

class WorkbookClassroomParserTest extends TestCase
{
    private function room(int $id, int $grade, string $name): Classroom
    {
        $room = new Classroom(['id' => $id, 'code' => $name, 'name' => $name]); $room->id = $id; $room->setRelation('gradeLevel', new GradeLevel(['number' => $grade, 'name' => 'Kelas '.$grade])); return $room;
    }

    public function test_double_spaces_match_and_combined_grades_expand_to_every_related_classroom(): void
    {
        $parser = new WorkbookClassroomParser(new WorkbookNameMatcher); $rooms = collect([$this->room(1, 4, "IV  A"), $this->room(2, 5, 'V A'), $this->room(3, 6, 'VI A'), $this->room(4, 3, 'III A')]);
        $this->assertSame(1, $parser->match('IV A', $rooms)['match']->id);
        $this->assertSame([1, 2, 3], $parser->expand('Kelas IV, V & VI', $rooms)->pluck('id')->all());
    }
}
