<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\Warranty;

/**
 * Warranty master data (Catalog → Warranties).
 */
class WarrantyController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_warranty.view')->only('index');
        $this->middleware('authorization:ecom_warranty.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_warranty.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_warranty.delete')->only('destroy');
    }

    public function index(): View
    {
        return view('ecom::warranties.index', ['warranties' => Warranty::withCount('products')->orderBy('days')->get()]);
    }

    public function create(): View
    {
        return view('ecom::warranties.form', ['warranty' => new Warranty(['duration' => 1, 'duration_unit' => 'year', 'is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('Warranty created: '.$data['name'], 'ecom.warranty.create')->create(fn () => Warranty::create($data));

        return redirect()->route('ecom.warranties.index')->with('success', 'Warranty created.');
    }

    public function edit(Warranty $warranty): View
    {
        return view('ecom::warranties.form', compact('warranty'));
    }

    public function update(Request $request, Warranty $warranty): RedirectResponse
    {
        $data = $this->validated($request, $warranty);
        me_change_log('Warranty updated: '.$warranty->name, 'ecom.warranty.update')->watch($warranty)->run(fn () => $warranty->update($data));

        return redirect()->route('ecom.warranties.index')->with('success', 'Warranty updated. Orders already placed keep the warranty they were sold with.');
    }

    public function destroy(Warranty $warranty): RedirectResponse
    {
        if ($count = $warranty->products()->count()) {
            return back()->with('error', "{$warranty->name} is set on {$count} products. Change those products first.");
        }

        me_change_log('Warranty deleted: '.$warranty->name, 'ecom.warranty.delete')->watch($warranty)->delete(fn () => $warranty->delete());

        return redirect()->route('ecom.warranties.index')->with('success', 'Warranty deleted.');
    }

    /**
     * Days come from duration × unit unless a different number was typed in.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Warranty $warranty = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ecom_warranties', 'name')->ignore($warranty?->id)],
            'duration' => 'required|integer|min:1|max:1000',
            'duration_unit' => ['required', Rule::in(array_keys(Warranty::UNITS))],
            'days' => 'nullable|integer|min:1|max:36500',
            'description' => 'nullable|string|max:1000',
        ]);
        $data['days'] = (int) ($data['days'] ?? 0) ?: Warranty::daysFor($data['duration'], $data['duration_unit']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
