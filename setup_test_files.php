<?php
require __DIR__.'/vendor/autoload.php';

// FPDF is usually global when installed via setasign/fpdf, or check mapping.
// But to be safe, let's try global.
if (!class_exists('FPDF')) {
    // maybe it is namespaced?
    // Actually setasign/fpdf allows usage of FPDF class.
}

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(40,10,'Certificate for Person 1');
$pdf->AddPage();
$pdf->Cell(40,10,'Certificate for Person 2');
$pdf->Output('F', __DIR__.'/public/test_cert.pdf');

$fp = fopen(__DIR__.'/public/test_list.csv', 'w');
fputcsv($fp, ['Name', 'Email']);
fputcsv($fp, ['Alice', 'alice@example.com']);
fputcsv($fp, ['Bob', 'bob@example.com']);
fclose($fp);

echo "Test files created in public folder.\n";
