<?php

use App\Enums\OrderStatus;
use App\Enums\OrderTypeEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('reference_code')->nullable();
            $table->string('type')->default(OrderTypeEnum::SERVICE->value);
            $table->string('status')->default(OrderStatus::PENDING->value);
            $table->text('note')->nullable();
            $table->boolean('is_read')->default(false);
            $table->decimal('total_amount', 10, 3)->default(0);
            $table->decimal('discount_amount', 10, 3)->default(0);
            $table->decimal('paid_amount', 10, 3)->default(0);
            $table->decimal('delivery_fee', 10, 3)->default(0);
            $table->decimal('tax_amount', 10, 3)->default(0);
            $table->decimal('service_fee', 10, 3)->default(0);
            $table->string('payment_status')->default(PaymentStatusEnum::UNPAID->value);
            $table->string('otp_code')->nullable();
            $table->boolean('requires_otp')->default(false);
            $table->string('delivery_address')->nullable();
            $table->decimal('tip_amount', 10, 3)->nullable();
            $table->timestamp('estimated_delivery_time')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedBigInteger('actioned_by')->nullable();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('couier_id')->nullable()->constrained('couiers')->nullOnDelete();
            // $table->foreignId('pos_shift_id')->nullable()->constrained('pos_shifts')->nullOnDelete();
            // $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
