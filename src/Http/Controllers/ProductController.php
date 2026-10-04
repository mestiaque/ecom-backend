<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use ME\Ecom\Models\Attribute;
use ME\Ecom\Models\AttributeValue;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Category;
use ME\Ecom\Models\Product;
use ME\Ecom\Models\ProductVariant;
use ME\Ecom\Models\Warranty;
use ME\Ecom\Services\ProductCsv;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends EcomController
{
    public function __construct(private ProductCsv $csv)
    {
        $this->middleware('authorization:ecom_product.view')->only(['index', 'show']);
        $this->middleware('authorization:ecom_product.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_product.edit')->only(['edit', 'update', 'toggle']);
        $this->middleware('authorization:ecom_product.delete')->only('destroy');
        $this->middleware('authorization:ecom_product.import')->only(['import', 'importTemplate']);
        $this->middleware('authorization:ecom_product.export')->only('export');
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'category', 'brand', 'status', 'stock']);
        $products = Product::with(['category', 'brand', 'primaryImage'])
            ->filter($filters)
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('ecom::products.index', [
            'products' => $products,
            'categories' => Category::tree(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('ecom::products.form', $this->formData(new Product(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['slug'] = $this->csv->uniqueSlug(($data['slug'] ?? null) ?: $data['title']);

        $product = me_change_log('Product created: '.$data['title'], 'ecom.product.create')
            ->with(['variants', 'images'])
            ->create(fn () => DB::transaction(function () use ($request, $data) {
                $product = Product::create($data);
                $this->saveImages($product, $request);
                $this->saveVariants($product, $request);

                return $product;
            }));

        return redirect()->route('ecom.products.show', $product)->with('success', 'Product created.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'brand', 'warranty', 'images', 'variants.values', 'variants.image', 'campaigns'])
            ->loadAvg(['reviews' => fn ($q) => $q->where('is_approved', true)], 'rating')
            ->loadCount(['reviews', 'orderItems']);
        $sold = (int) $product->orderItems()->whereHas('order', fn ($q) => $q->countsAsSale())->sum('quantity');

        return view('ecom::products.show', compact('product', 'sold'));
    }

    public function edit(Product $product): View
    {
        return view('ecom::products.form', $this->formData($product->load(['images', 'variants.values'])));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validated($request, $product);
        $data['slug'] = $this->csv->uniqueSlug(($data['slug'] ?? null) ?: $data['title'], Product::class, $product->id);

        me_change_log('Product updated: '.$product->title, 'ecom.product.update')
            ->watch($product, ['variants', 'images'])
            ->itemName('variants', fn ($variant) => $variant['sku'] ?: 'Variant #'.$variant['id'])
            ->itemName('images', fn ($image) => basename($image['path']))
            ->run(fn () => DB::transaction(function () use ($request, $product, $data) {
                $product->update($data);
                $this->saveImages($product, $request);
                $this->saveVariants($product, $request);
            }));

        return redirect()->route('ecom.products.show', $product)->with('success', 'Product updated.');
    }

    public function toggle(Product $product): RedirectResponse
    {
        me_change_log(($product->is_active ? 'Product hidden: ' : 'Product activated: ').$product->title, 'ecom.product.update')
            ->watch($product)
            ->run(fn () => $product->update(['is_active' => ! $product->is_active]));

        return back()->with('success', $product->is_active ? 'Product is now active.' : 'Product is now hidden.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $paths = $product->images()->get(['path', 'thumbnail'])->flatMap(fn ($image) => [$image->path, $image->thumbnail])->filter();

        me_change_log('Product deleted: '.$product->title, 'ecom.product.delete')
            ->watch($product, ['variants'])
            ->delete(fn () => $product->delete());
        $paths->each(fn ($path) => $this->deleteImage($path));

        return redirect()->route('ecom.products.index')->with('success', 'Product deleted.');
    }

    public function export(Request $request): StreamedResponse
    {
        return $this->csv->export($request->only(['search', 'category', 'brand', 'status', 'stock']));
    }

    public function importTemplate(): StreamedResponse
    {
        return $this->csv->template();
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $result = $this->csv->import($request->file('file')->getRealPath());
        me_change_log("Products imported: {$result['created']} created, {$result['updated']} updated", 'ecom.product.import')
            ->record([], ['created' => $result['created'], 'updated' => $result['updated'], 'errors' => count($result['errors'])]);

        $message = "Import finished: {$result['created']} created, {$result['updated']} updated.";

        return redirect()->route('ecom.products.index')
            ->with('success', $message)
            ->with('import_errors', $result['errors']);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => Category::tree(),
            'brands' => Brand::orderBy('name')->get(),
            'warranties' => Warranty::active()->orWhere('id', $product->warranty_id)->orderBy('days')->get(),
            'attributes' => Attribute::with('values')->orderBy('sort_order')->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Product $product = null): array
    {
        $variantIds = collect($request->input('variants', []))->pluck('id')->filter()->all();

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('ecom_products', 'sku')->ignore($product?->id)],
            'category_id' => 'nullable|integer|exists:ecom_categories,id',
            'brand_id' => 'nullable|integer|exists:ecom_brands,id',
            'warranty_id' => 'nullable|integer|exists:ecom_warranties,id',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'delivery_charge_adjustment' => 'nullable|numeric|min:-100000|max:100000',
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|max:4096',
            'variants' => 'nullable|array',
            'variants.*.id' => ['nullable', 'integer', Rule::in($product ? $product->variants()->pluck('id')->all() : [])],
            'variants.*.values' => 'required_with:variants|array|min:1',
            'variants.*.values.*' => 'integer|exists:ecom_attribute_values,id',
            'variants.*.product_image_id' => ['nullable', 'integer', Rule::in($product ? $product->images()->pluck('id')->all() : [])],
            'variants.*.sku' => ['nullable', 'string', 'max:100', 'distinct', Rule::unique('ecom_product_variants', 'sku')->whereNotIn('id', $variantIds)],
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.discount_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required_with:variants|integer|min:0',
        ], [
            'discount_price.lt' => 'Discount price must be less than the regular price.',
            'variants.*.sku.unique' => 'Variant SKU :input is already used.',
        ]);

        $hasVariants = $request->boolean('has_variants') && count($request->input('variants', [])) > 0;

        if ($hasVariants) {
            $this->checkCombinations($request->input('variants'));
        }

        unset($data['images'], $data['variants']);
        $data['has_variants'] = $hasVariants;
        $data['stock'] = (int) ($data['stock'] ?? 0);
        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['free_delivery'] = $request->boolean('free_delivery');
        $data['delivery_charge_adjustment'] = $data['free_delivery'] || (float) ($data['delivery_charge_adjustment'] ?? 0) === 0.0 ? null : $data['delivery_charge_adjustment'];

        return $data;
    }

    /**
     * Every variant needs one value per attribute, all variants use the same attributes,
     * and no combination may repeat.
     *
     * @param  array<int|string, array<string, mixed>>  $variants
     *
     * @throws ValidationException
     */
    private function checkCombinations(array $variants): void
    {
        $attributeOf = AttributeValue::whereIn('id', collect($variants)->pluck('values')->flatten()->filter()->all())->pluck('attribute_id', 'id');
        $attributeSet = null;
        $seen = [];

        foreach ($variants as $i => $variant) {
            $ids = array_map('intval', (array) ($variant['values'] ?? []));
            $attributes = array_map(fn ($id) => $attributeOf[$id] ?? null, $ids);
            $fail = fn (string $message) => throw ValidationException::withMessages(["variants.{$i}.values" => $message]);

            if (! $ids || in_array(null, $attributes, true)) {
                $fail('Choose the attribute values for every variant.');
            }
            if (count($attributes) !== count(array_unique($attributes))) {
                $fail('A variant can have only one value of each attribute.');
            }

            sort($attributes);
            sort($ids);
            $set = implode('-', $attributes);
            $key = implode('-', $ids);

            if ($attributeSet !== null && $set !== $attributeSet) {
                $fail('All variants must use the same attributes.');
            }
            if (isset($seen[$key])) {
                $fail('Two variants have the same combination.');
            }

            $attributeSet = $set;
            $seen[$key] = true;
        }
    }

    /**
     * Create / update / delete variant rows to match the form. Stock becomes the variant total.
     */
    private function saveVariants(Product $product, Request $request): void
    {
        if (! $product->has_variants) {
            $product->variants()->delete();

            return;
        }

        $keep = [];

        foreach ($request->input('variants', []) as $row) {
            $values = [
                'product_image_id' => ($row['product_image_id'] ?? null) ?: null,
                'sku' => ($row['sku'] ?? null) ?: null,
                'price' => is_numeric($row['price'] ?? null) ? $row['price'] : null,
                'discount_price' => is_numeric($row['discount_price'] ?? null) ? $row['discount_price'] : null,
                'stock' => (int) ($row['stock'] ?? 0),
                'is_active' => ! empty($row['is_active']),
            ];

            $variant = ! empty($row['id'])
                ? tap(ProductVariant::where('product_id', $product->id)->findOrFail($row['id']))->update($values)
                : $product->variants()->create($values);
            $variant->values()->sync(array_map('intval', (array) $row['values']));
            $keep[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keep)->delete();
        $product->syncStockFromVariants();
    }

    /**
     * Remove ticked images, set the primary image and add new uploads at the end.
     */
    private function saveImages(Product $product, Request $request): void
    {
        $remove = array_map('intval', (array) $request->input('remove_images', []));

        foreach ($product->images()->whereIn('id', $remove)->get() as $image) {
            $this->deleteImage($image->path);
            $image->delete();
        }

        if ($primaryId = (int) $request->input('primary_image')) {
            $product->images()->where('id', $primaryId)->update(['sort_order' => 0]);
            $product->images()->where('id', '!=', $primaryId)->where('sort_order', 0)->update(['sort_order' => 1]);
        }

        $next = (int) $product->images()->max('sort_order') + 1;

        foreach ((array) $request->file('images', []) as $file) {
            $product->images()->create(['path' => $this->storeImage($file, 'products'), 'sort_order' => $next++]);
        }
    }
}
