<?php

namespace Modules\Language\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\Language\Models\Language;

class LanguageObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Language $language): void
    {
        Cache::forget('languages.codes');

        if ($language->is_default) {
            Language::where('id', '!=', $language->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Language $language): void
    {
        Cache::forget('languages.codes');

        if ($language->is_default && $language->wasChanged('is_default')) {
            Language::where('id', '!=', $language->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Language $language): void
    {
        Cache::forget('languages.codes');
    }

    /**
     * Handle the "restored" event.
     */
    public function restored(Language $language): void
    {
        Cache::forget('languages.codes');
    }

    /**
     * Handle the "force deleted" event.
     */
    public function forceDeleted(Language $language): void
    {
        Cache::forget('languages.codes');
    }
}
