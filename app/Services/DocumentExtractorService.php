<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser as PdfParser;

class DocumentExtractorService
{
    public function extract(UploadedFile|string $file): string
    {
        $path = $file instanceof UploadedFile ? $file->getRealPath() : $file;
        $extension = $file instanceof UploadedFile ? $file->getClientOriginalExtension() : pathinfo($file, PATHINFO_EXTENSION);

        return match (strtolower($extension)) {
            'pdf' => $this->extractPdf($path),
            'docx', 'doc' => $this->extractDocx($path),
            'txt' => file_get_contents($path),
            default => throw new \InvalidArgumentException("Unsupported file type: {$extension}"),
        };
    }

    protected function extractPdf(string $path): string
    {
        $parser = new PdfParser;
        $pdf = $parser->parseFile($path);

        return $pdf->getText();
    }

    protected function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText()."\n";
                }
            }
        }

        return trim($text);
    }
}
