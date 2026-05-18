<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchTranslationsRequest;
use App\Http\Requests\StoreTranslationRequest;
use App\Http\Requests\UpdateTranslationRequest;
use App\Http\Resources\TranslationResource;
use App\Models\Translation;
use App\Services\Translations\TranslationManager;
use App\Services\Translations\TranslationSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TranslationController extends Controller
{
    public function __construct(
        private readonly TranslationManager $translationManager,
        private readonly TranslationSearch $translationSearch
    ) {}

    public function index(SearchTranslationsRequest $request): AnonymousResourceCollection
    {
        return TranslationResource::collection($this->translationSearch->paginate($request->validated()));
    }

    public function store(StoreTranslationRequest $request): JsonResponse
    {
        $translation = $this->translationManager->create($request->validated());

        return (new TranslationResource($translation))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Translation $translation): TranslationResource
    {
        return new TranslationResource($translation->load('tags'));
    }

    public function update(UpdateTranslationRequest $request, Translation $translation): TranslationResource
    {
        return new TranslationResource(
            $this->translationManager->update($translation, $request->validated())
        );
    }
}