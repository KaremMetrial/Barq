<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\ProductPrice;
use Modules\Offer\Models\Offer;
use App\Helpers\CurrencyHelper;

class BackfillCurrencyMinorUnits extends Command
{
    protected $signature = 'backfill:currency-minor-units {--batch=1000} {--dry-run}';

    protected $description = 'Backfill price_minor and offer discount_amount_minor using country.currency_factor for all product prices and offers.';

    public function handle()
    {
        $batch = (int) $this->option('batch');
        $dryRun = (bool) $this->option('dry-run');

        $this->info('Starting backfill for product_prices');

        ProductPrice::chunkById($batch, function ($prices) use ($dryRun) {
            foreach ($prices as $price) {
                // Resolve currency factor from product->store->address->zone->city->governorate->country
                $factor = $price->product?->store?->address?->zone?->city?->governorate?->country?->currency_factor ?? 100;

                $computed = CurrencyHelper::toMinorUnits((float) $price->price, (int) $factor);

                if ($dryRun) {
                    $this->line("ProductPrice id={$price->id} would set price_minor={$computed} (factor={$factor})");
                    continue;
                }

                $price->price_minor = $computed;
                $price->purchase_price_minor = CurrencyHelper::toMinorUnits((float) $price->purchase_price, (int) $factor);
                $price->sale_price_minor = $price->sale_price !== null ? CurrencyHelper::toMinorUnits((float) $price->sale_price, (int) $factor) : null;
                $price->saveQuietly();
            }
        });

        $this->info('Product prices backfill complete.');

        $this->info('Starting backfill for offers');

        Offer::chunkById($batch, function ($offers) use ($dryRun) {
            foreach ($offers as $offer) {
                // Only fixed discounts need minor units
                if ($offer->discount_type->value === \App\Enums\SaleTypeEnum::FIXED->value) {
                    // Prefer offer currency_factor if set, else infer from offerable
                    $factor = $offer->currency_factor ?? null;

                    if (!$factor) {
                        $offerable = $offer->offerable;

                        if ($offerable) {
                            if (method_exists($offerable, 'store') && $offerable->store) {
                                $factor = $offerable->store->address?->zone?->city?->governorate?->country?->currency_factor ?? 100;
                            } elseif (property_exists($offerable, 'country')) {
                                $factor = $offerable->country?->currency_factor ?? 100;
                            }
                        }
                    }

                    $factor = $factor ?? 100;
                    $computed = CurrencyHelper::toMinorUnits((float) $offer->discount_amount, (int) $factor);

                    if ($dryRun) {
                        $this->line("Offer id={$offer->id} would set discount_amount_minor={$computed} (factor={$factor})");
                        continue;
                    }

                    $offer->discount_amount_minor = $computed;
                    $offer->currency_factor = $factor;
                    $offer->currency_code = $offer->currency_code ?? ($offer->offerable?->store?->address?->zone?->city?->governorate?->country?->currency_name ?? null);
                    $offer->saveQuietly();
                }
            }
        });

        $this->info('Offers backfill complete.');

        return 0;
    }
}
