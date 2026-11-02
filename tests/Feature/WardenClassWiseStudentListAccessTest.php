<?php

namespace Tests\Feature;

use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pipeline\Pipeline;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class WardenClassWiseStudentListAccessTest extends TestCase
{
    public function test_warden_can_access_list_and_print_export_routes(): void
    {
        foreach (['principal.student-lists.index', 'principal.student-lists.print'] as $name) {
            $this->assertSame(200, $this->authorizeRoute($name, true)->getStatusCode());
        }
    }

    public function test_warden_without_list_permission_is_denied(): void
    {
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('You do not have the required permission.');
        $this->authorizeRoute('principal.student-lists.index', false);
    }

    private function authorizeRoute(string $name, bool $allowed)
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasAnyRole')->withArgs(fn ($roles) => in_array('Warden', $roles, true))->andReturnTrue();
        $user->shouldReceive('hasAnyPermission')->with(['view_class_wise_student_lists'])->andReturn($allowed);
        $request = Request::create('/principal/student-lists');
        $request->setUserResolver(fn () => $user);
        $middleware = collect(app('router')->getRoutes()->getByName($name)->gatherMiddleware())
            ->filter(fn ($item) => str_starts_with($item, 'role:') || str_starts_with($item, 'permission:'))
            ->map(fn ($item) => str_replace(['role:', 'permission:'], [RoleMiddleware::class.':', PermissionMiddleware::class.':'], $item))
            ->all();

        return app(Pipeline::class)->send($request)->through($middleware)
            ->then(fn () => response('Allowed'));
    }
}

