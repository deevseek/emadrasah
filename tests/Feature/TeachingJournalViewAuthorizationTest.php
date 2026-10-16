<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Academic\TeachingJournalController;
use App\Models\{Personnel, TeachingJournal, User};
use App\Services\Academic\AcademicAccessService;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class TeachingJournalViewAuthorizationTest extends TestCase
{
    public function test_owner_can_view_own_journal(): void
    {
        $this->checkAccess(true, false, 7, 7);
        $this->addToAssertionCount(1);
    }

    public function test_view_all_permission_allows_other_journals_without_personnel_link(): void
    {
        $this->checkAccess(true, true, null, 7);
        $this->addToAssertionCount(1);
    }

    public function test_other_personnel_cannot_view_journal(): void
    {
        $this->assertForbidden(true, false, 8, 7);
    }

    public function test_missing_personnel_link_cannot_view_journal(): void
    {
        $this->assertForbidden(true, false, null, 7);
    }

    public function test_view_all_does_not_replace_base_view_permission(): void
    {
        $this->assertForbidden(false, true, 7, 7);
    }

    private function assertForbidden(bool $view, bool $viewAll, ?int $personnelId, int $ownerId): void
    {
        try {
            $this->checkAccess($view, $viewAll, $personnelId, $ownerId);
            $this->fail('Akses jurnal seharusnya ditolak.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    private function checkAccess(bool $view, bool $viewAll, ?int $personnelId, int $ownerId): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('can')->with('teaching-journals.view')->andReturn($view);
        $user->shouldReceive('can')->with('teaching-journals.view-all')->andReturn($viewAll);
        $user->setRelation('personnel', $personnelId === null ? null : new Personnel(['id' => $personnelId]));
        $request = Request::create('/');
        $request->setUserResolver(fn () => $user);
        $journal = new TeachingJournal(['personnel_id' => $ownerId]);
        $controller = new TeachingJournalController(Mockery::mock(AcademicAccessService::class));

        $method = new \ReflectionMethod($controller, 'ensureView');
        $method->invoke($controller, $request, $journal);
    }
}
