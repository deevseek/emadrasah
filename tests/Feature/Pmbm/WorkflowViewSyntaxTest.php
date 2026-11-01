<?php

declare(strict_types=1);

namespace Tests\Feature\Pmbm;

use Tests\TestCase;

class WorkflowViewSyntaxTest extends TestCase
{
    public function test_workflow_compiles_to_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/pmbm/admin/workflow.blade.php'));
        $compiled = $this->app['blade.compiler']->compileString($source);

        // Parse every branch, including completed parent and child interviews.
        $tokens = token_get_all($compiled, TOKEN_PARSE);

        $this->assertNotEmpty($tokens);
    }
}
