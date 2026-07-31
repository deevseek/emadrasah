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
    public function test_real_classroom_names_match_by_grade_and_normalized_name_without_cross_grade_fuzzy_match(): void
    {
        $parser = new WorkbookClassroomParser(new WorkbookNameMatcher);
        $names = [2 => ["Al-Mu'min", 'Al-Wahhab'], 3 => ['Al-Khaliq', 'Al-Lathif'], 4 => ['Al-Basith', 'Al-Karim'], 5 => ["Al-‘Alim", 'Al-Hakim'], 6 => ['Al-Majid']];
        $rooms = collect(); $id = 1;
        foreach ($names as $grade => $classNames) foreach ($classNames as $name) $rooms->push($this->room($id++, $grade, $name));
        foreach (["Kelas II Al-Mu'min", 'Kelas II Al-Wahhab', 'Kelas III Al-Khaliq', 'Kelas III Al-Lathif', 'Kelas IV Al-Basith', 'Kelas IV Al-Karim', "Kelas V Al-'Alim", 'Kelas V Al-Hakim', 'Kelas VI Al-Majid'] as $label) $this->assertSame('matched', $parser->match($label, $rooms)['status'], $label);
        foreach (['Kelas I Ar-Rahman', 'Kelas I Ar-Rahim', 'Kelas I As-Salam'] as $label) $this->assertSame('unmatched', $parser->match($label, $rooms)['status']);
        $this->assertSame('full_day', $parser->suggestProgram('Kelas I As-Salam'));
        $this->assertSame('regular', $parser->suggestProgram('Kelas I Ar-Rahman'));
        $this->assertSame('regular', $parser->suggestProgram('Kelas I Ar-Rahim'));
        $this->assertSame('unmatched', $parser->match("Kelas II Al-Mu'min", collect([$this->room(99, 3, "Al-Mu'min")]))['status']);
    }

}
