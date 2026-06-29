<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ThemeController extends Controller
{
    public function select(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'themes_id' => ['required', 'exists:themes,id'],
        ]);

        $request->user()->update([
            'themes_id' => $validated['themes_id'],
        ]);

        return back();
    }
}
