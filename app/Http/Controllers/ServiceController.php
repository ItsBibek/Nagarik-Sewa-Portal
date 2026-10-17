<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Contracts\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        $departments = Department::query()
            ->whereHas('serviceTypes', fn ($query) => $query->where('is_active', true))
            ->with([
                'serviceTypes' => fn ($query) => $query
                    ->where('is_active', true)
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get();

        return view('services.index', [
            'departments' => $departments,
        ]);
    }
}
