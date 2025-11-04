<?php

namespace App\Helpers;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use setasign\Fpdf\Fpdf;
use setasign\Fpdi\Fpdi;

class StampHelper
{
    // public static function stampIncomingMail($documentID)
    // {
    //     $document = Document::find($documentID);

    //     if (!$documentID) {
    //         return response()->json(['message' => 'Document not found.'], 404);
    //     }

    //     // Paths for the original PDF and the output
    //     // $originalPdfPath = storage_path('app/public/' . $document->file_path);
    //     // $documentPath = storage_path( 'app/public/' . $document->file_path);
    //     $documentPath = $document->file_path;
    //     // $outputPdfPath = storage_path( $document->file_path);


    //     // Create an instance of FPDI
    //     $pdf = new Fpdi();

    //     // Load the PDF
    //     $pageCount = $pdf->setSourceFile($documentPath);
    //     // dd($pageCount);
    //     for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    //         // Import the current page
    //         $templateId = $pdf->importPage($pageNo);
    //         $size = $pdf->getTemplateSize($templateId);

    //         // Add a page with the same dimensions
    //         $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
    //         $pdf->useTemplate($templateId);

    //         // Set the font and color for the stamp
    //         $pdf->SetFont('Arial', 'B', 16);
    //         $pdf->SetTextColor(36, 161, 32); // Green color for the stamp36, 161, 32

    //         // Add the stamp (e.g., CONFIDENTIAL)
    //         $pdf->SetXY(20, 20); // Position for the stamp
    //         $pdf->Cell(0, 10, 'RECEIVED', 0, 1, 'C');
    //         $pdf->SetFont('Arial', '', 10); // Set font for the date
    //         $currentDate = now()->format('F j, Y, g A'); // Define the date
    //         $pdf->Cell(0, 10, $currentDate, 0, 1, 'C');

    //         // Add the QR Code
    //         // $pdf->Image($qrCodePath, 20, $size['height'] - 50, 30, 30); // Position and size of the QR code
    //     }

    //     // Save the output PDF
    //     $pdf->Output($documentPath, 'F');

    //     // return response()->download($outputPdfPath, 'document_with_stamp_qrcode.pdf');
    //     return [
    //         $documentPath,
    //         'message' => 'Document Stamped',
    //     ];
    // }

    public static function stampIncomingMail($documentID)
    {
        $document = Document::find($documentID);

        if (!$document) {
            return response()->json(['message' => 'Document not found.'], 404);
        }

        $cloudinaryUrl = $document->file_path;

        // Extract public ID from Cloudinary URL
        $publicId = self::extractPublicIdFromCloudinaryUrl($cloudinaryUrl);
        if (!$publicId) {
            return response()->json(['message' => 'Invalid Cloudinary URL.'], 400);
        }

        // Temporary local files
        $tempOriginal = tempnam(sys_get_temp_dir(), 'pdf_original_') . '.pdf';
        $tempStamped = tempnam(sys_get_temp_dir(), 'pdf_stamped_') . '.pdf';

        try {
            // Download PDF locally
            $pdfContent = file_get_contents($cloudinaryUrl);
            if ($pdfContent === false) {
                throw new \Exception("Failed to download PDF from Cloudinary URL.");
            }
            file_put_contents($tempOriginal, $pdfContent);

            // Create FPDI and stamp PDF as before
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempOriginal);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                $pdf->SetFont('Arial', 'B', 16);
                $pdf->SetTextColor(36, 161, 32);
                $pdf->SetXY(20, 20);
                $pdf->Cell(0, 10, 'RECEIVED', 0, 1, 'C');
                $pdf->SetFont('Arial', '', 10);
                $currentDate = now()->format('F j, Y, g A');
                $pdf->Cell(0, 10, $currentDate, 0, 1, 'C');
            }

            // Save stamped PDF locally
            $pdf->Output('F', $tempStamped);

            // Upload stamped PDF to Cloudinary with the SAME public ID (overwrite original)
            $cloudinary = new CloudinaryHelper();

            // Upload using public_id extracted so it overwrites existing file
            $uploadResult = $cloudinary->upload($tempStamped, null, $publicId);

            // Files are overwritten in Cloudinary, so database doesn't need updating

            // Clean up temp files
            @unlink($tempOriginal);
            @unlink($tempStamped);

            return [
                'file_path' => $document->file_path, // same original URL
                'message' => 'Document stamped and original overwritten successfully',
            ];
        } catch (\Exception $e) {
            @unlink($tempOriginal);
            @unlink($tempStamped);

            Log::error("Error stamping document ID {$documentID}: " . $e->getMessage());

            return response()->json(['message' => 'Failed to stamp document.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Helper function to extract Cloudinary public ID from URL
     * e.g. from https://res.cloudinary.com/demo/image/upload/v1234/folder/file.pdf 
     * returns folder/file
     */
    public static function extractPublicIdFromCloudinaryUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path'])) {
            return null;
        }

        $path = ltrim($parsedUrl['path'], '/'); // e.g. demo/image/upload/v1234/folder/file.pdf

        // Remove leading cloud name segment (e.g. demo/)
        $path = preg_replace('#^[^/]+/#', '', $path);

        // Remove resource_type/upload prefix (e.g. image/upload/ or raw/upload/)
        $path = preg_replace('#^(image|raw|video)/upload/#', '', $path);

        // Remove version segment which may appear at start or within path
        // e.g. v1234/folder/file.pdf or folder/v1234/file.pdf
        $path = preg_replace('#^v[0-9]+/#', '', $path); // leading version
        $path = preg_replace('#/v[0-9]+/#', '/', $path); // mid-path version

        // Remove file extension like .pdf
        $publicId = preg_replace('#\.[^.]+$#', '', $path);

        return $publicId;
    }

    /**
     * Extract file extension from Cloudinary URL path, e.g. pdf, jpg
     */
    public static function extractExtensionFromCloudinaryUrl(string $url): ?string
    {
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['path'])) {
            return null;
        }
        $path = ltrim($parsedUrl['path'], '/');
        // Remove cloud name if present
        $path = preg_replace('#^[^/]+/#', '', $path);
        // Remove resource_type/upload prefix
        $path = preg_replace('#^(image|raw|video)/upload/#', '', $path);
        // Strip version segments
        $path = preg_replace('#^v[0-9]+/#', '', $path);
        $path = preg_replace('#/v[0-9]+/#', '/', $path);
        // Capture extension
        if (preg_match('#\.([^.]+)$#', $path, $m)) {
            return strtolower($m[1]);
        }
        return null;
    }
}
