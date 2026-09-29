<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\JalaliDate;
use App\Support\Persian;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Owner panel dashboard (full checklist/widgets in Phase 2 & 4).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        return view('dashboard', [
            'user' => $user,
            'menu' => $menu,
            'today' => JalaliDate::long(),
            'greeting' => $this->greeting(),
        ]);
    }

    private function greeting(): string
    {
        $hour = (int) now()->timezone('Asia/Tehran')->format('G');

        return match (true) {
            $hour < 12 => 'صبح بخیر',
            $hour < 17 => 'عصر بخیر',
            default => 'شب بخیر',
        };
    }
}
