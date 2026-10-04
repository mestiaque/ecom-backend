<?php

namespace ME\Ecom\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

class Csv
{
    /**
     * Stream a CSV download. A UTF-8 BOM is added so Excel shows Bangla text correctly.
     *
     * @param  array<int, string>  $headings
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    public static function download(string $filename, array $headings, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headings);

            foreach ($rows as $row) {
                fputcsv($out, array_map(fn ($value) => is_bool($value) ? (int) $value : $value, $row));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Read a CSV file into associative rows keyed by the (lower-cased) header line.
     *
     * @return array<int, array<string, string>>
     */
    public static function read(string $path): array
    {
        $handle = fopen($path, 'r');
        $headings = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($headings === null) {
                $headings = array_map(fn ($heading) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $heading))), $line);

                continue;
            }

            if (count(array_filter($line, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headings, array_pad(array_slice($line, 0, count($headings)), count($headings), ''));
        }

        fclose($handle);

        return $rows;
    }
}
