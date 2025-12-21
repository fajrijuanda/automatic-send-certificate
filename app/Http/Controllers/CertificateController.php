<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use App\Mail\CertificateSent;
use App\Services\PdfService;

class CertificateController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function send(Request $request, PdfService $pdfService)
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0); // Unlimited

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv|max:102400', // 100MB
            'pdf_file' => 'required|file|mimes:pdf|max:102400', // 100MB
        ]);

        // 1. Process Excel
        $rows = Excel::toArray(new \stdClass(), $request->file('excel_file'));
        $data = $rows[0]; // Assume first sheet
        // Remove header if needed, or assume first row is data?
        // Let's assume the first row likely contains headers like 'name', 'email'.
        // To be safe, let's normalize headers to lowercase and look for 'email'.

        $headers = array_map(function($h) {
            return strtolower(trim($h));
        }, $data[0]);

        // Define possible column names
        $nameKeywords = ['name', 'nama', 'nama lengkap', 'full name', 'fullname', 'nama peserta'];
        $emailKeywords = ['email', 'e-mail', 'email address', 'alamat email'];

        // Find Name Index
        $nameIndex = false;
        foreach ($nameKeywords as $keyword) {
            $found = array_search($keyword, $headers);
            if ($found !== false) {
                $nameIndex = $found;
                break;
            }
        }

        // Find Email Index
        $emailIndex = false;
        foreach ($emailKeywords as $keyword) {
            $found = array_search($keyword, $headers);
            if ($found !== false) {
                $emailIndex = $found;
                break;
            }
        }

        // Fallback logic
        if ($emailIndex === false) {
             // If headers are not found, assume usage of standard columns if appropriate,
             // or likely the file has no headers.
             // Default: Name = 0, Email = 1
             $nameIndex = 0;
             $emailIndex = 1;
             $startIndex = 0;
        } else {
             $startIndex = 1; // Skip header
             // If name was not found but email was, default name to 0 if 0 != emailIndex?
             // Or maybe column before email?
             // Let's stick to default 0 if name not found.
             if ($nameIndex === false) {
                 $nameIndex = 0;
             }
        }

        $recipients = [];
        for ($i = $startIndex; $i < count($data); $i++) {
            $row = $data[$i];
            $rawEmail = $row[$emailIndex] ?? null;

            // Trim email to avoid hidden whitespace issues
            $email = trim($rawEmail);

            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'name' => $row[$nameIndex] ?? 'Recipient',
                    'email' => $email,
                    // Page will be assigned later
                ];
            } else {
                 // Log skipped email (Terminal & File)
                 $skippedName = $row[$nameIndex] ?? 'Unknown';
                 $reason = empty($email) ? 'Empty Email' : 'Invalid Email Format';
                 \Log::warning("SKIPPED_EMAIL: $skippedName ($rawEmail) - $reason");
                 error_log("SKIPPED_EMAIL: $skippedName ($rawEmail) - $reason");
            }
        }

        if (empty($recipients)) {
            $message = 'No valid emails found in Excel.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        // 2. Process PDF
        $pdfPath = $request->file('pdf_file')->getPathname();

        // 2a. Map Names to Pages using PdfService
        $names = array_column($recipients, 'name');
        $mapping = $pdfService->mapNamesToPages($pdfPath, $names);

        $validRecipients = [];
        $skippedRecipients = [];

        foreach ($recipients as $recipient) {
            $name = $recipient['name'];
            if (isset($mapping[$name])) {
                $recipient['page'] = $mapping[$name];
                $validRecipients[] = $recipient;
            } else {
                $skippedRecipients[] = $name;
                // Log skipped PDF match
                \Log::warning("SKIPPED_PDF_MISMATCH: $name (Not found in PDF pages)");
                error_log("SKIPPED_PDF_MISMATCH: $name (Not found in PDF pages)");
            }
        }

        if (empty($validRecipients)) {
             $message = "No matching names found in PDF! Skipped: " . implode(', ', $skippedRecipients);

             // Ensure this is visible in terminal too
             error_log("ERROR: $message");

             if ($request->wantsJson()) {
                 return response()->json(['success' => false, 'message' => $message], 422);
             }
             return back()->with('error', $message);
        }

        // Optional: Check if page count is sufficient, though mapping ensures we have valid pages.
        // We can skip the strict count check since we are not sequentially assigning anymore.
        // Logic handled by mapping.

        $recipients = $validRecipients;


        $sentCount = 0;

        foreach ($recipients as $recipient) {
            $pageNo = $recipient['page'];

            // Extract Page
            $newPdf = new Fpdi();
            $newPdf->setSourceFile($pdfPath);

            // Import page first to get size
            $tplId = $newPdf->importPage($pageNo);
            $size = $newPdf->getTemplateSize($tplId);

            // Determine orientation
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';

            $newPdf->AddPage($orientation, [$size['width'], $size['height']]);
            $newPdf->useTemplate($tplId);

            $outputName = 'cert_' . preg_replace('/[^a-z0-9]/i', '_', $recipient['name']) . '.pdf';
            // Use system temp dir to avoid permission issues on shared hosting
            $tempDir = sys_get_temp_dir();
            $outputPath = $tempDir . DIRECTORY_SEPARATOR . $outputName;

            // Ensure temp dir exists (it should, but just in case)
            if (!file_exists(dirname($outputPath))) {
                @mkdir(dirname($outputPath), 0755, true);
            }

            $newPdf->Output('F', $outputPath);

            // Send Email
            try {
                Mail::to($recipient['email'])->send(new CertificateSent($recipient['name'], $outputPath));

                // 1. Log ke file (storage/logs/laravel.log)
                \Log::info("SUCCESS_SENT: {$recipient['name']} ({$recipient['email']})");

                // 2. Tampilkan di Terminal (php artisan serve window)
                error_log("SUCCESS_SENT: {$recipient['name']} ({$recipient['email']})");

                $sentCount++;
            } catch (\Exception $e) {
                // Log error, continue?
                \Log::error("Failed to send to {$recipient['email']}: " . $e->getMessage());
            }

            // Cleanup temp file? Maybe keep for a bit or delete after send.
            // For now, let's not delete immediately so we can debug if needed, or use 'later' queue.
            // Since we are sync, we can delete.
            // unlink($outputPath);
        }

        $msg = "Successfully processed and sent $sentCount certificates!";
        if (!empty($skippedRecipients)) {
            $msg .= " Skipped " . count($skippedRecipients) . " names (not found in PDF): " . implode(', ', array_slice($skippedRecipients, 0, 5));
            if (count($skippedRecipients) > 5) $msg .= ", ...";
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'count' => $sentCount,
                'skipped' => $skippedRecipients
            ]);
        }

        return back()->with('success', $msg);
    }
}
