<?php

namespace App\Services;

use App\Repositories\LanguageRepository;
use Illuminate\Support\Facades\Cache;

class LanguageService
{
    public function __construct(
        protected LanguageRepository $languageRepository
    ) {}

    /**
     * Get all language codes with caching.
     */
    public function getAllCodes(): array
    {
        return Cache::rememberForever('languages.codes', function () {
            $languages = $this->languageRepository->getAllCodes();
            return empty($languages) ? ['en'] : $languages;
        });
    }

    /**
     * Clear cached languages.
     */
    public function clearCache(): void
    {
        Cache::forget('languages.codes');
    }
}
