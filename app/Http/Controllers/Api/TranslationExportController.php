<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExportTranslationsRequest;
use App\Services\Translations\TranslationExporter;
use Symfony\Component\HttpFoundation\Response;

class TranslationExportController extends Controller
{
    public function __construct(private readonly TranslationExporter $translationExporter) {}

    public function __invoke(ExportTranslationsRequest $request): Response
    {
        return $this->translationExporter->response($request, $request->validated());
    }
}