<?php

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
        Schema::create('confectionaries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Event::class, 'event_id');
            $table->string('name');
            $table->string('slug');
            $table->longText('category')->nullable();
            $table->decimal("price", 16, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confectionaries');
    }
};
