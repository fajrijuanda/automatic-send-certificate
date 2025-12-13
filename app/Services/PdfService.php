<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use Illuminate\Support\Facades\Log;

class PdfService
{
    protected $parser;

    public function __construct()
    {
        $this->parser = new Parser();
    }

    /**
     * Map names to page numbers in the PDF.
     * returns an array: ['name1' => pageNum, 'name2' => pageNum]
     * 
     * @param string $pdfPath
     * @param array $names List of names to search for.
     * @return array
     */
    public function mapNamesToPages(string $pdfPath, array $names): array
    {
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            $mapping = [];
            
            // Normalize names for easier matching
            $normalizedNames = [];
            foreach ($names as $originalName) {
                // Remove extra spaces, lowercase
                $cleanName = strtolower(trim(preg_replace('/\s+/', ' ', $originalName)));
                if (!empty($cleanName)) {
                    $normalizedNames[$originalName] = $cleanName;
                }
            }

            foreach ($pages as $pageNumber => $page) {
                // Page numbers in Smalot are usually 0-indexed in the array, but let's verify.
                // Actually $pdf->getPages() returns an array.
                // We want 1-based page number for FPDI.
                $actualPageNumber = $pageNumber + 1;
                
                $text = $page->getText();
                $cleanText = strtolower(trim(preg_replace('/\s+/', ' ', $text)));
                
                foreach ($normalizedNames as $originalName => $searchName) {
                    if (str_contains($cleanText, $searchName)) {
                        // Found a match!
                        // If multiple pages have the same name, this uses the last one found... 
                        // Or we can check if it's already set. 
                        // Let's assume one certificate per name for now.
                        $mapping[$originalName] = $actualPageNumber;
                    }
                }
            }

            return $mapping;

        } catch (\Exception $e) {
            Log::error("PDF Parsing Error: " . $e->getMessage());
            return [];
        }
    }
}
