<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use App\Mail\CertificateSent;

class CertificateController extends Controller
{
    public function index()
    {
        return view('welcome');
    }

    public function send(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
            'pdf_file' => 'required|file|mimes:pdf',
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
            if (isset($row[$emailIndex]) && filter_var($row[$emailIndex], FILTER_VALIDATE_EMAIL)) {
                $recipients[] = [
                    'name' => $row[$nameIndex] ?? 'Recipient',
                    'email' => $row[$emailIndex],
                    'page' => count($recipients) + 1 // Assign page numbers sequentially
                ];
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
        
        // Count pages to ensure match
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);

        if ($pageCount < count($recipients)) {
            $message = "PDF has $pageCount pages but Excel has " . count($recipients) . " recipients. Numbers must match (or PDF must have enough pages).";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }
            return back()->with('error', $message);
        }

        $sentCount = 0;

        foreach ($recipients as $recipient) {
            $pageNo = $recipient['page'];
            
            // Extract Page
            $newPdf = new Fpdi();
            $newPdf->setSourceFile($pdfPath);
            $newPdf->AddPage();
            $tplId = $newPdf->importPage($pageNo);
            $newPdf->useTemplate($tplId);

            $outputName = 'cert_' . preg_replace('/[^a-z0-9]/i', '_', $recipient['name']) . '.pdf';
            $outputPath = storage_path("app/public/temp/{$outputName}");
            
            // Ensure temp dir exists
            if (!file_exists(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0755, true);
            }

            $newPdf->Output('F', $outputPath);

            // Send Email
            try {
                Mail::to($recipient['email'])->send(new CertificateSent($recipient['name'], $outputPath));
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

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully processed and sent $sentCount certificates!",
                'count' => $sentCount
            ]);
        }

        return back()->with('success', "Successfully processed and sent $sentCount certificates!");
    }
}
