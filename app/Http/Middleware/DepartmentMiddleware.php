<?php

namespace App\Http\Middleware;

use App\Models\Application;
use App\Models\Department;
use App\Models\ServiceType;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $routeParameter = 'application'): Response
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->role === 'officer' && $user->department_id !== null,
            Response::HTTP_FORBIDDEN,
        );

        $resource = $request->route($routeParameter);

        if (! $resource instanceof Model && is_numeric($resource)) {
            $resource = match ($routeParameter) {
                'application' => Application::query()->find($resource),
                'department' => Department::query()->find($resource),
                'serviceType' => ServiceType::query()->find($resource),
                default => null,
            };
        }

        $departmentId = $this->departmentIdFor($resource);

        abort_unless(
            $departmentId !== null && $departmentId === (int) $user->department_id,
            Response::HTTP_FORBIDDEN,
        );

        return $next($request);
    }

    private function departmentIdFor(mixed $resource): ?int
    {
        $departmentId = match (true) {
            $resource instanceof Department => $resource->getKey(),
            $resource instanceof ServiceType => $resource->department_id,
            $resource instanceof Application => $resource->serviceType()->value('department_id'),
            default => null,
        };

        return $departmentId === null ? null : (int) $departmentId;
    }
}
