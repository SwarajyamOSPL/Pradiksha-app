<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Contracts\View\View;

class PackageController extends Controller
{
    public function index(): View
    {
        return view('packages.index', [
            'packages' => Package::query()->active()->withCount('products')->orderBy('name')->paginate(12),
        ]);
    }

    public function show(Package $package): View
    {
        abort_unless($package->is_active, 404);

        return view('packages.show', [
            'package' => $package->load('products'),
        ]);
    }
}
