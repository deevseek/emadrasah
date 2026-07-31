<?php

declare(strict_types=1);

namespace Tests\Unit\TeachingAssignments;

use App\Services\TeachingAssignments\WorkbookNameMatcher;
use PHPUnit\Framework\TestCase;

class WorkbookNameMatcherTest extends TestCase
{
    public function test_name_with_apostrophe_spacing_case_and_title_period_variations_matches(): void
    {
        $matcher = new WorkbookNameMatcher;
        $candidates = [(object) ['id' => 1, 'full_name' => "M. Ma'ruf, S.Pd."]];
        $result = $matcher->match("  m.  ma’ruf, s.pd ", $candidates);
        $this->assertSame('matched', $result['status']);
        $this->assertSame(1, $result['match']->id);
        $rois = $matcher->match("RO’IS  RO’DATUL URBAH, S.Pd.", [(object) ['id' => 2, 'full_name' => "RO'IS RO'DATUL URBAH, S.Pd."]]);
        $this->assertSame(2, $rois['match']->id);
    }
}
