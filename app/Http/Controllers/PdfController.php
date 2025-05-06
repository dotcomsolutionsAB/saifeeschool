<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PdfController extends Controller
{
    public function generatePDF($id)
    {
        // Fetch Data from DB
        $data = DB::table('transfer_certificate')->where('st_roll_no', $id)->first();

        if (!$data) {
            return response()->json(['message' => 'No data found'], 404);
        }

        // Convert Date of Birth to Words
        $data->dob_words = $this->convertDateToWords($data->dob);

        // Load Blade and Generate PDF
        ini_set('memory_limit', '1024M'); // or '1024M'
        $pdf = Pdf::loadView('pdf.transfer_certificate', (array) $data)
                  ->setPaper('a4', 'portrait');

        return $pdf->stream('Transfer_Certificate.pdf');
    }

    private function convertDateToWords($dateString)
    {
        $timestamp = strtotime($dateString);
        $day = date('j', $timestamp);
        $month = date('F', $timestamp);
        $year = date('Y', $timestamp);

        return strtoupper("$day DAY OF $month, $year");
    }
}