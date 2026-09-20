<?php

declare(strict_types=1);
namespace Tests\Feature\Pmbm;
use App\Http\Requests\Pmbm\StoreApplicantRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
class ApplicantIntakeValidationTest extends TestCase {
 #[Test] public function intake_uses_shared_conditional_rules_for_guardian_and_special_needs(): void { $rules=(new StoreApplicantRequest())->rules(); $this->assertSame('required_if:special_needs,lainnya|nullable|string|max:500',$rules['special_needs_other']); $this->assertSame('required_if:guardian_type,other|nullable|string|max:200',$rules['guardian_name']); $this->assertArrayHasKey('father_education',$rules); $this->assertArrayHasKey('mother_education',$rules); }
}
