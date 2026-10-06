<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use ME\Ecom\Models\Banner;

class BannerController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_content.banner');
    }

    public function index(): View
    {
        return view('ecom::banners.index', ['banners' => Banner::with('media')->orderBy('position')->orderBy('sort_order')->get()]);
    }

    public function create(): View
    {
        return view('ecom::banners.form', ['banner' => new Banner(['is_active' => true, 'position' => 'slider'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request, true);
        $banner = me_change_log('Banner created', 'ecom.banner.create')->create(fn () => Banner::create($data));
        // Files go to me_media (metheme)
        $banner->syncMediaFromRequest($request, 'image');

        return redirect()->route('ecom.banners.index')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner): View
    {
        return view('ecom::banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner): RedirectResponse
    {
        $data = $this->validated($request, false);

        // A banner is a picture: removing it is only allowed together with a new upload
        if ($request->input('image_remove') && ! $request->hasFile('image')) {
            return back()->withInput()->withErrors(['image' => 'A banner needs an image — upload a new one to replace it.']);
        }

        me_change_log('Banner updated', 'ecom.banner.update')->watch($banner)->run(fn () => $banner->update($data));
        $banner->syncMediaFromRequest($request, 'image');

        return redirect()->route('ecom.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner): RedirectResponse
    {
        me_change_log('Banner deleted', 'ecom.banner.delete')->watch($banner)->delete(fn () => $banner->delete());

        return redirect()->route('ecom.banners.index')->with('success', 'Banner deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, bool $imageRequired): array
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'link' => 'nullable|string|max:500',
            'button_text' => 'nullable|string|max:50',
            'position' => ['required', Rule::in(array_keys(Banner::POSITIONS))],
            'sort_order' => 'nullable|integer|min:0',
            'image' => [$imageRequired ? 'required' : 'nullable', 'image', 'max:4096'],
        ]);
        unset($data['image']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
