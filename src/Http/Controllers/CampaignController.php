<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use ME\Ecom\Models\Campaign;
use ME\Ecom\Models\Product;
use ME\Ecom\Services\ProductCsv;

class CampaignController extends EcomController
{
    public function __construct(private ProductCsv $slugs)
    {
        $this->middleware('authorization:ecom_campaign.view')->only('index');
        $this->middleware('authorization:ecom_campaign.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_campaign.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_campaign.delete')->only('destroy');
    }

    public function index(): View
    {
        $campaigns = Campaign::with('media')->withCount('products')->latest('starts_at')->paginate($this->perPage());

        return view('ecom::campaigns.index', compact('campaigns'));
    }

    public function create(): View
    {
        return view('ecom::campaigns.form', [
            'campaign' => new Campaign(['is_active' => true, 'discount_type' => 'percent', 'starts_at' => now(), 'ends_at' => now()->addDays(3)]),
            'products' => Product::orderBy('title')->get(['id', 'title', 'sku', 'price']),
            'selected' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->slugs->uniqueSlug($data['title'], Campaign::class);

        me_change_log('Campaign created: '.$data['title'], 'ecom.campaign.create')->with(['products'])->create(fn () => DB::transaction(function () use ($data, $request) {
            $campaign = Campaign::create($data);
            $campaign->syncMediaFromRequest($request, 'banner');
            $campaign->products()->sync($request->input('products', []));

            return $campaign;
        }));

        return redirect()->route('ecom.campaigns.index')->with('success', 'Campaign created.');
    }

    public function edit(Campaign $campaign): View
    {
        return view('ecom::campaigns.form', [
            'campaign' => $campaign,
            'products' => Product::orderBy('title')->get(['id', 'title', 'sku', 'price']),
            'selected' => $campaign->products()->pluck('ecom_products.id')->all(),
        ]);
    }

    public function update(Request $request, Campaign $campaign): RedirectResponse
    {
        $data = $this->validated($request);

        me_change_log('Campaign updated: '.$campaign->title, 'ecom.campaign.update')
            ->watch($campaign, ['products'])
            ->itemName('products', 'title')
            ->run(fn () => DB::transaction(function () use ($campaign, $data, $request) {
                $campaign->update($data);
                $campaign->syncMediaFromRequest($request, 'banner');
                $campaign->products()->sync($request->input('products', []));
            }));

        return redirect()->route('ecom.campaigns.index')->with('success', 'Campaign updated.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        me_change_log('Campaign deleted: '.$campaign->title, 'ecom.campaign.delete')->watch($campaign)->delete(fn () => $campaign->delete());

        return redirect()->route('ecom.campaigns.index')->with('success', 'Campaign deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => ['required', 'numeric', 'min:0', $request->discount_type === 'percent' ? 'max:100' : 'max:9999999'],
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
            'banner' => 'nullable|image|max:4096',
            'products' => 'nullable|array',
            'products.*' => 'integer|exists:ecom_products,id',
        ]);
        unset($data['banner'], $data['products']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
