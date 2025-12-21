<?php

namespace App\Services;


use Illuminate\Support\Facades\Log;

class PdfService
{
    public function __construct()
    {
        // No constructor logic needed for system command
    }

    /**
     * Map names to page numbers in the PDF using pdftotext.
     */
    public function mapNamesToPages(string $pdfPath, array $names): array
    {
        try {
            $mapping = [];
            $normalizedNames = [];

            // Normalize names
            foreach ($names as $originalName) {
                $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', $originalName)));
                if (!empty($cleanName)) {
                    $normalizedNames[$originalName] = $cleanName;
                }
            }

            // Execute pdftotext to extract ALL text at once
            // -layout maintains layout which is good for separation
            // Outputting to stdout ('-')
            // Encapsulate path in quotes
            $cmd = 'pdftotext -layout "' . $pdfPath . '" -';
            $fullText = shell_exec($cmd);

            if ($fullText === null) {
                Log::error("pdftotext failed to return output.");
                return [];
            }

            // Split by Form Feed character (\f) which denotes page breaks
            // Note: The first page is index 0.
            $pages = explode("\f", $fullText);
            \Log::info("PdfService: Extracted " . count($pages) . " pages from PDF.");
            error_log("PdfService: Extracted " . count($pages) . " pages from PDF.");

            // Phase 2: Match Names
            foreach ($pages as $index => $pageText) {
                $actualPageNumber = $index + 1;
                $cleanText = strtolower(trim(preg_replace('/\s+/', ' ', $pageText)));

                // Skip empty pages (often last page after \f is empty)
                if (empty($cleanText)) continue;

                foreach ($normalizedNames as $originalName => $searchName) {
                    if (str_contains($cleanText, $searchName)) {
                        $mapping[$originalName] = $actualPageNumber;
                        unset($normalizedNames[$originalName]);
                    }
                }

                if (empty($normalizedNames)) break;
            }

            return $mapping;

        } catch (\Exception $e) {
            Log::error("PDF Parsing Error (pdftotext): " . $e->getMessage());
            return [];
        }
    }
}
