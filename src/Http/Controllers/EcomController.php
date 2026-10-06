<?php

namespace ME\Ecom\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use ME\Http\Controllers\Controller;

/**
 * Base for every ecom admin controller.
 */
abstract class EcomController extends Controller
{
    protected function perPage(): int
    {
        return (int) get_setting('pagination', 15) ?: 15;
    }

    /**
     * ?from= / ?to= date range, defaulting to the last $days days.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function dateRange(Request $request, int $days = 30): array
    {
        $from = $request->date('from') ? CarbonImmutable::parse($request->date('from'))->startOfDay() : CarbonImmutable::now()->subDays($days - 1)->startOfDay();
        $to = $request->date('to') ? CarbonImmutable::parse($request->date('to'))->endOfDay() : CarbonImmutable::now()->endOfDay();

        return $from > $to ? [$to->startOfDay(), $from->endOfDay()] : [$from, $to];
    }
}
