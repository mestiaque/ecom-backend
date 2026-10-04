<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\Page;
use ME\Ecom\Services\ProductCsv;

class PageController extends EcomController
{
    public function __construct(private ProductCsv $slugs)
    {
        $this->middleware('authorization:ecom_content.page');
    }

    public function index(): View
    {
        return view('ecom::pages.index', ['pages' => Page::orderBy('title')->get()]);
    }

    public function create(): View
    {
        return view('ecom::pages.form', ['page' => new Page(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['title'], Page::class);
        me_change_log('Page created: '.$data['title'], 'ecom.page.create')->create(fn () => Page::create($data));

        return redirect()->route('ecom.pages.index')->with('success', 'Page created.');
    }

    public function edit(Page $page): View
    {
        return view('ecom::pages.form', compact('page'));
    }

    public function update(Request $request, Page $page): RedirectResponse
    {
        $data = $this->validated($request, $page);
        $data['slug'] = $this->slugs->uniqueSlug(($data['slug'] ?? null) ?: $data['title'], Page::class, $page->id);
        me_change_log('Page updated: '.$page->title, 'ecom.page.update')->watch($page)->run(fn () => $page->update($data));

        return redirect()->route('ecom.pages.index')->with('success', 'Page updated.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        me_change_log('Page deleted: '.$page->title, 'ecom.page.delete')->watch($page)->delete(fn () => $page->delete());

        return redirect()->route('ecom.pages.index')->with('success', 'Page deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Page $page = null): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('ecom_pages', 'slug')->ignore($page?->id)],
            'content' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
