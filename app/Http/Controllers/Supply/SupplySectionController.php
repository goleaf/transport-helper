<?php

namespace App\Http\Controllers\Supply;

use App\Http\Controllers\Controller;
use App\Support\SupplyNavigation;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SupplySectionController extends Controller
{
    public function show(Request $request, string $section): View
    {
        abort_unless($request->user(), 403);

        $sectionData = SupplyNavigation::section($section);
        abort_if($sectionData === null, 404);

        return view('supply.sections.show', [
            'section' => $sectionData,
        ]);
    }
}
