<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function dashboard(Request $request): View
    {
        if ($request->user()->is_super_admin && $request->routeIs('admin.dashboard')) return view('app.admin');
        $canManage = $request->user()->memberships()->whereIn('role', ['owner', 'admin', 'manager'])->exists();
        if (!$canManage) return view('app.tenant');
        return view('app.dashboard');
    }
    public function tenant(): View { return view('app.tenant'); }
}
