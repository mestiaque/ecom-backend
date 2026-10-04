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
use ME\Ecom\Services\ProductCsv;

/**
 * Variant attributes (Color, Size, Storage, ...) and their values.
 */
class AttributeController extends EcomController
{
    public function __construct(private ProductCsv $slugs)
    {
        $this->middleware('authorization:ecom_attribute.view')->only('index');
        $this->middleware('authorization:ecom_attribute.create')->only(['create', 'store']);
        $this->middleware('authorization:ecom_attribute.edit')->only(['edit', 'update']);
        $this->middleware('authorization:ecom_attribute.delete')->only('destroy');
    }

    public function index(): View
    {
        $attributes = Attribute::with(['values' => fn ($q) => $q->withCount('variants')])->orderBy('sort_order')->orderBy('name')->get();

        return view('ecom::attributes.index', compact('attributes'));
    }

    public function create(): View
    {
        return view('ecom::attributes.form', ['attribute' => new Attribute(['type' => 'select'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        me_change_log('Attribute created: '.$data['name'], 'ecom.attribute.create')->with(['values'])->create(fn () => DB::transaction(function () use ($data, $request) {
            $attribute = Attribute::create([...$data, 'slug' => $this->slugs->uniqueSlug($data['name'], Attribute::class)]);
            $this->saveValues($attribute, $request);

            return $attribute;
        }));

        return redirect()->route('ecom.attributes.index')->with('success', 'Attribute created.');
    }

    public function edit(Attribute $attribute): View
    {
        $attribute->load(['values' => fn ($q) => $q->withCount('variants')]);

        return view('ecom::attributes.form', compact('attribute'));
    }

    public function update(Request $request, Attribute $attribute): RedirectResponse
    {
        $data = $this->validated($request, $attribute);

        me_change_log('Attribute updated: '.$attribute->name, 'ecom.attribute.update')
            ->watch($attribute, ['values'])
            ->itemName('values', 'value')
            ->run(fn () => DB::transaction(function () use ($attribute, $data, $request) {
                $attribute->update($data);
                $this->saveValues($attribute, $request);
            }));

        return redirect()->route('ecom.attributes.index')->with('success', 'Attribute updated.');
    }

    public function destroy(Attribute $attribute): RedirectResponse
    {
        if (AttributeValue::where('attribute_id', $attribute->id)->has('variants')->exists()) {
            return back()->with('error', "{$attribute->name} is used by product variants. Remove it from those products first.");
        }

        me_change_log('Attribute deleted: '.$attribute->name, 'ecom.attribute.delete')->watch($attribute, ['values'])->delete(fn () => $attribute->delete());

        return redirect()->route('ecom.attributes.index')->with('success', 'Attribute deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Attribute $attribute = null): array
    {
        $request->merge(['values' => array_values(array_filter(
            (array) $request->input('values', []),
            fn ($row) => trim((string) ($row['value'] ?? '')) !== ''
        ))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('ecom_attributes', 'name')->ignore($attribute?->id)],
            'type' => ['required', Rule::in(array_keys(Attribute::TYPES))],
            'sort_order' => 'nullable|integer|min:0',
            'values' => 'required|array|min:1',
            'values.*.id' => ['nullable', 'integer', Rule::in($attribute ? $attribute->values()->pluck('id')->all() : [])],
            'values.*.value' => 'required|string|max:100|distinct:ignore_case',
            'values.*.color_code' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], [
            'values.required' => 'Add at least one value.',
            'values.*.value.distinct' => 'The value ":input" is listed twice.',
        ]);
        unset($data['values']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    /**
     * Create / update / delete values to match the form. Values used by a variant cannot be removed.
     *
     * @throws ValidationException
     */
    private function saveValues(Attribute $attribute, Request $request): void
    {
        $keep = [];

        foreach (array_values($request->input('values', [])) as $sort => $row) {
            $values = [
                'value' => trim($row['value']),
                'color_code' => $attribute->isColor() ? ($row['color_code'] ?? null) : null,
                'sort_order' => $sort,
            ];

            $value = ! empty($row['id'])
                ? tap($attribute->values()->findOrFail($row['id']))->update($values)
                : $attribute->values()->create($values);
            $keep[] = $value->id;
        }

        $removed = $attribute->values()->whereNotIn('id', $keep);
        $inUse = (clone $removed)->has('variants')->pluck('value');

        if ($inUse->isNotEmpty()) {
            throw ValidationException::withMessages(['values' => 'These values are used by product variants and cannot be removed: '.$inUse->implode(', ')]);
        }

        $removed->delete();
    }
}
