<?php

namespace ME\Ecom\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ME\Ecom\Models\Review;

class ReviewController extends EcomController
{
    public function __construct()
    {
        $this->middleware('authorization:ecom_review.view')->only('index');
        $this->middleware('authorization:ecom_review.approve')->only('approve');
        $this->middleware('authorization:ecom_review.delete')->only('destroy');
    }

    public function index(Request $request): View
    {
        $reviews = Review::with('product')
            ->when($request->status === 'pending', fn ($q) => $q->where('is_approved', false))
            ->when($request->status === 'approved', fn ($q) => $q->where('is_approved', true))
            ->when($request->rating, fn ($q, $rating) => $q->where('rating', $rating))
            ->when($request->search, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('comment', 'like', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p->where('title', 'like', "%{$search}%"))))
            ->latest('id')
            ->paginate($this->perPage())
            ->withQueryString();

        return view('ecom::reviews.index', [
            'reviews' => $reviews,
            'pendingCount' => Review::where('is_approved', false)->count(),
        ]);
    }

    public function approve(Review $review): RedirectResponse
    {
        $review->update(['is_approved' => ! $review->is_approved]);

        return back()->with('success', $review->is_approved ? 'Review approved.' : 'Review hidden.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        me_change_log('Review deleted ('.$review->product?->title.')', 'ecom.review.delete')->watch($review)->delete(fn () => $review->delete());

        return back()->with('success', 'Review deleted.');
    }
}
