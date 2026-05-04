<?php

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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('store_slug')->unique();
            $table->decimal('commission_rate', 5, 4);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->decimal('reserve_price', 12, 2);
            $table->decimal('current_price', 12, 2);
            $table->decimal('bid_increment', 12, 2);
            $table->enum('status', ['draft', 'scheduled', 'live', 'ended', 'cancelled']);

            $table->boolean('is_live')->virtualAs("status = 'live'");

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('auction_id')->constrained();
            $table->decimal('amount', 12, 2);
            $table->timestamp('placed_at')->useCurrent();
            $table->unique(['user_id', 'auction_id', 'amount']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('file_path');
            $table->timestamps();
        });

        Schema::create('watchlists', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained();
            $table->foreignId('auction_id')->constrained();
            $table->boolean('notify_at_close')->default(false);
            $table->primary(['user_id', 'auction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watchlists');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('bids');
        Schema::dropIfExists('auctions');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('vendors');
    }
};
