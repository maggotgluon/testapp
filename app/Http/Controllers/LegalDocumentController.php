<?php

namespace App\Http\Controllers;

use App\Services\LegalDocumentService;
use Illuminate\View\View;

class LegalDocumentController extends Controller
{
    public function show(string $document, LegalDocumentService $documents): View
    {
        return view('legal.show', [
            'document' => $documents->document($document),
            'documents' => $documents->all(),
        ]);
    }
}
