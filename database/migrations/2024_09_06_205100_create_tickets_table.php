<?php

use App\Models\BankAccount;
use App\Models\Event;
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class, 'event_id');
            $table->string('type')->default('Regular');
            $table->string("slug")->nullable();
            $table->decimal("price", 16, 2);
            $table->integer("table_for");
            $table->foreignIdFor(BankAccount::class, 'bank_account_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
