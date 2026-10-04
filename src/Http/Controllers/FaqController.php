<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Faq;

/**
 * FAQ entries for the storefront FAQ page (question + rich-text answer, grouped by category).
 */
class FaqController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_content.faq');
    }

    public function index(Request $request): View
    {
        return view('ecom::faqs.index', [
            'faqs' => Faq::ordered()
                ->when($request->input('category'), fn ($q, $category) => $category === Faq::GENERAL ? $q->whereNull('category') : $q->where('category', $category))
                ->get()
                ->groupBy('category_name'),
            'categories' => Faq::categories(),
        ]);
    }

    public function create(): View
    {
        return view('ecom::faqs.form', [
            'faq' => new Faq(['is_active' => true, 'sort_order' => (int) Faq::max('sort_order') + 1]),
            'categories' => Faq::categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('FAQ created: '.$data['question'], 'ecom.faq.create')->create(fn () => Faq::create($data));

        return redirect()->route('ecom.faqs.index')->with('success', 'FAQ added.');
    }

    public function edit(Faq $faq): View
    {
        return view('ecom::faqs.form', ['faq' => $faq, 'categories' => Faq::categories()]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $data = $this->validated($request);
        me_change_log('FAQ updated: '.$faq->question, 'ecom.faq.update')->watch($faq)->run(fn () => $faq->update($data));

        return redirect()->route('ecom.faqs.index')->with('success', 'FAQ updated.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        me_change_log('FAQ deleted: '.$faq->question, 'ecom.faq.delete')->watch($faq)->delete(fn () => $faq->delete());

        return redirect()->route('ecom.faqs.index')->with('success', 'FAQ deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'category' => 'nullable|string|max:100',
            'question' => 'required|string|max:255',
            'answer' => 'required|string|max:10000',
            'sort_order' => 'nullable|integer|min:0|max:100000',
        ]);

        $category = trim((string) ($data['category'] ?? ''));

        return [
            ...$data,
            'category' => $category === '' || strcasecmp($category, Faq::GENERAL) === 0 ? null : $category,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
