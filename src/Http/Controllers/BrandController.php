<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Brand;
use ME\Ecom\Services\ProductCsv;

class BrandController extends EcomController
{
    public function __construct(private ProductCsv $slugs)
    {
        $this->middleware('authorization:ecom_brand.view')->only('index');
        $this->middleware('authorization:ecom_brand.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_brand.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_brand.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $brands = Brand::with('media')->withCount('products')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('ecom::brands.index', compact('brands'));
    }

    public function create(): View
    {
        return view('ecom::brands.form', ['brand' => new Brand(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['name'], Brand::class);

        $brand = me_change_log('Brand created: '.$data['name'], 'ecom.brand.create')->create(fn () => Brand::create($data));
        // Files go to me_media (metheme)
        $brand->syncMediaFromRequest($request, 'logo');

        return redirect()->route('ecom.brands.index')->with('success', 'Brand created.');
    }

    public function edit(Brand $brand): View
    {
        return view('ecom::brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['name'], Brand::class, $brand->id);

        me_change_log('Brand updated: '.$brand->name, 'ecom.brand.update')->watch($brand)->run(fn () => $brand->update($data));
        $brand->syncMediaFromRequest($request, 'logo');

        return redirect()->route('ecom.brands.index')->with('success', 'Brand updated.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        me_change_log('Brand deleted: '.$brand->name, 'ecom.brand.delete')->watch($brand)->delete(fn () => $brand->delete());

        return redirect()->route('ecom.brands.index')->with('success', 'Brand deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'logo' => 'nullable|image|max:2048',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['logo']);

        return $data;
    }
}
