<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\Category;
use ME\Ecom\Services\ProductCsv;

class CategoryController extends EcomController
{
    public function __construct(private ProductCsv $slugs)
    {
        $this->middleware('authorization:ecom_category.view')->only('index');
        $this->middleware('authorization:ecom_category.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_category.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_category.delete')->only('destroy');
    }

    public function index(): View
    {
        return view('ecom::categories.index', ['categories' => Category::tree()]);
    }

    public function create(): View
    {
        return view('ecom::categories.form', ['category' => new Category(['is_active' => true]), 'parents' => Category::tree()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['name'], Category::class);
        $data['image'] = $request->hasFile('image') ? $this->storeImage($request->file('image'), 'categories') : null;
        $data['banner'] = $request->hasFile('banner') ? $this->storeImage($request->file('banner'), 'categories') : null;

        me_change_log('Category created: '.$data['name'], 'ecom.category.create')->create(fn () => Category::create($data));

        return redirect()->route('ecom.categories.index')->with('success', 'Category created.');
    }

    public function edit(Category $category): View
    {
        return view('ecom::categories.form', ['category' => $category, 'parents' => Category::tree($category->id)]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $this->validated($request, $category);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['name'], Category::class, $category->id);
        $data['image'] = $this->replaceImage($request, 'image', $category->image, 'categories');
        $data['banner'] = $this->replaceImage($request, 'banner', $category->banner, 'categories');

        me_change_log('Category updated: '.$category->name, 'ecom.category.update')->watch($category)->run(fn () => $category->update($data));

        return redirect()->route('ecom.categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'Move or delete its sub-categories first.');
        }

        me_change_log('Category deleted: '.$category->name, 'ecom.category.delete')->watch($category)->delete(fn () => $category->delete());
        $this->deleteImage($category->image);
        $this->deleteImage($category->banner);

        return redirect()->route('ecom.categories.index')->with('success', 'Category deleted. Its products are now uncategorised.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'parent_id' => ['nullable', 'integer', Rule::exists('ecom_categories', 'id'), Rule::notIn($category ? array_merge([$category->id], $category->descendantIds()) : [])],
            'description' => 'nullable|string',
            'sort_order' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
            'banner' => 'nullable|image|max:4096',
        ]);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        unset($data['image'], $data['banner']);

        return $data;
    }
}
