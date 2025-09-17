<?php

namespace Modules\Language\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Language\Repositories\LanguageRepository;
use Illuminate\Support\Facades\Cache;
use Modules\Language\Models\Language;
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
    public function getAllLanguages(): Collection
    {
        return $this->languageRepository->all();
    }
    public function createLanguage(array $data): Language
    {
        $this->clearCache();
        return $this->languageRepository->create($data);
    }

}
