<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Services\Menus\SlugRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Post-signup onboarding: business name, business type and menu ID (slug).
 */
class OnboardingController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        return view('onboarding.show', [
            'user' => $user,
            'menu' => $user->menus()->first(),
            'businessTypes' => Menu::BUSINESS_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(Menu::BUSINESS_TYPES))],
            'slug' => ['required', 'string', 'max:40'],
        ], [
            'name.required' => 'نام کسب‌وکار را وارد کنید.',
            'name.min' => 'نام کسب‌وکار باید حداقل ۲ کاراکتر باشد.',
            'business_type.required' => 'نوع کسب‌وکار را انتخاب کنید.',
            'business_type.in' => 'نوع کسب‌وکار انتخاب‌شده معتبر نیست.',
            'slug.required' => 'شناسه منو را وارد کنید.',
        ], [
            'name' => 'نام کسب‌وکار',
            'business_type' => 'نوع کسب‌وکار',
            'slug' => 'شناسه منو',
        ]);

        $user = $request->user();
        $existing = $user->menus()->first();

        // Slug rules apply on creation. (Changing an ID later shows the
        // QR-breaking warning dialog — Phase 2, spec §7.6.)
        $slugCheck = SlugRules::check($data['slug'], $existing?->id);
        if (! $slugCheck['ok']) {
            return back()
                ->withErrors(['slug' => SlugRules::errorMessage($slugCheck['error'])])
                ->withInput();
        }

        DB::transaction(function () use ($user, $data, $existing, $slugCheck) {
            if ($existing) {
                $existing->update([
                    'name' => $data['name'],
                    'business_type' => $data['business_type'],
                ]);

                return;
            }

            $menu = Menu::query()->create([
                'user_id' => $user->id,
                'slug' => $slugCheck['slug'],
                'name' => $data['name'],
                'business_type' => $data['business_type'],
                'status' => Menu::STATUS_TRIAL,
                'trial_ends_at' => now()->addDays((int) config('menoyeman.trial_days', 10)),
            ]);

            $user->update(['name' => $data['name']]);
        });

        return redirect()->route('panel.dashboard')->with('status', 'منوی شما ساخته شد! 🎉');
    }

    /** Live slug availability check (JSON) for the onboarding form. */
    public function checkSlug(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slug' => ['required', 'string', 'max:40'],
        ]);

        $existing = $request->user()->menus()->first();
        $result = SlugRules::check($data['slug'], $existing?->id);

        return response()->json([
            'ok' => $result['ok'],
            'slug' => $result['slug'],
            'error' => $result['error'],
            'message' => $result['ok'] ? 'این شناسه آزاد است.' : SlugRules::errorMessage($result['error']),
        ]);
    }
}
