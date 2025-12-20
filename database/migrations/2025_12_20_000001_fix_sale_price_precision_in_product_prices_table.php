};
    }
        });
            $table->decimal('sale_price')->nullable()->change();
            // Revert to decimal without precision (though this is the broken state)
        Schema::table('product_prices', function (Blueprint $table) {
    {
    public function down(): void
     */
     * Reverse the migrations.
    /**

    }
        });
            $table->decimal('sale_price', 8, 3)->nullable()->change();
            // Change sale_price to have correct precision (8,3) to match price column
        Schema::table('product_prices', function (Blueprint $table) {
    {
    public function up(): void
     */
     * This causes data loss and inconsistency with other price columns.
     *
     * which defaults to decimal(8,1) instead of decimal(8,3)
     * Issue: sale_price was defined as decimal() without precision
     *
     * in product_prices table that was missing in migration 2025_11_11_124609
     * CRITICAL FIX: Adds precision specification to sale_price column
     *
     * Run the migrations.
    /**
{
return new class extends Migration

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;


