<?php

namespace ME\Ecom\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use ME\Ecom\Models\Brand;
use ME\Ecom\Models\Category;
use ME\Ecom\Models\Product;
use ME\Ecom\Support\Csv;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Bulk product import/export. Rows are matched by SKU: an existing SKU is updated, a new one is created.
 * Variants are not part of the CSV — edit them on the product page.
 */
class ProductCsv
{
    public const COLUMNS = ['sku', 'title', 'category', 'brand', 'price', 'discount_price', 'cost_price', 'stock', 'low_stock_threshold', 'is_active', 'is_featured', 'weight', 'short_description', 'description'];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function export(array $filters = []): StreamedResponse
    {
        $rows = function () use ($filters) {
            foreach (Product::with(['category', 'brand'])->filter($filters)->orderBy('id')->lazy() as $product) {
                yield [
                    $product->sku, $product->title, $product->category?->name, $product->brand?->name,
                    $product->price, $product->discount_price, $product->cost_price, $product->stock,
                    $product->low_stock_threshold, $product->is_active, $product->is_featured, $product->weight,
                    $product->short_description, $product->description,
                ];
            }
        };

        return Csv::download('products-'.now()->format('Y-m-d').'.csv', self::COLUMNS, $rows());
    }

    public function template(): StreamedResponse
    {
        return Csv::download('products-import-template.csv', self::COLUMNS, [
            ['TSHIRT-001', 'Cotton T-Shirt', 'Men > T-Shirts', 'Easy', '650', '550', '300', '40', '5', '1', '0', '0.25', 'Soft cotton t-shirt', '<p>100% cotton</p>'],
        ]);
    }

    /**
     * @return array{created: int, updated: int, errors: array<int, string>}
     */
    public function import(string $path): array
    {
        $result = ['created' => 0, 'updated' => 0, 'errors' => []];

        foreach (Csv::read($path) as $index => $row) {
            $line = $index + 2; // header is line 1
            $title = trim($row['title'] ?? '');

            if ($title === '' || ! is_numeric($row['price'] ?? null)) {
                $result['errors'][] = "Line {$line}: title and a numeric price are required.";

                continue;
            }

            DB::transaction(function () use ($row, $title, &$result) {
                $sku = trim($row['sku'] ?? '') ?: null;
                $product = $sku ? Product::firstWhere('sku', $sku) : null;

                $data = [
                    'title' => $title,
                    'category_id' => $this->categoryId($row['category'] ?? ''),
                    'brand_id' => $this->brandId($row['brand'] ?? ''),
                    'price' => (float) $row['price'],
                    'discount_price' => $this->number($row['discount_price'] ?? null),
                    'cost_price' => $this->number($row['cost_price'] ?? null),
                    'low_stock_threshold' => $this->number($row['low_stock_threshold'] ?? null),
                    'is_active' => ! in_array(strtolower(trim($row['is_active'] ?? '1')), ['0', 'no', 'false', 'inactive'], true),
                    'is_featured' => in_array(strtolower(trim($row['is_featured'] ?? '0')), ['1', 'yes', 'true'], true),
                    'weight' => $this->number($row['weight'] ?? null),
                    'short_description' => ($row['short_description'] ?? '') ?: null,
                    'description' => ($row['description'] ?? '') ?: null,
                ];

                // Variant products keep stock in their variants
                if (! $product?->has_variants && is_numeric($row['stock'] ?? null)) {
                    $data['stock'] = (int) $row['stock'];
                }

                if ($product) {
                    $product->update($data);
                    $result['updated']++;
                } else {
                    Product::create([...$data, 'sku' => $sku, 'slug' => $this->uniqueSlug($title)]);
                    $result['created']++;
                }
            });
        }

        return $result;
    }

    /**
     * "Men > T-Shirts" creates/uses the nested category path.
     */
    private function categoryId(string $path): ?int
    {
        $parentId = null;

        foreach (array_filter(array_map('trim', explode('>', $path))) as $name) {
            $category = Category::firstOrCreate(
                ['name' => $name, 'parent_id' => $parentId],
                ['slug' => $this->uniqueSlug($name, Category::class)]
            );
            $parentId = $category->id;
        }

        return $parentId;
    }

    private function brandId(string $name): ?int
    {
        $name = trim($name);

        return $name === '' ? null : Brand::firstOrCreate(['name' => $name], ['slug' => $this->uniqueSlug($name, Brand::class)])->id;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function uniqueSlug(string $text, string $model = Product::class, ?int $ignoreId = null): string
    {
        $base = Str::slug($text) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while ($model::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
