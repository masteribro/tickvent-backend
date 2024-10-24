<?php

use App\Models\Confectionary;
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
        Schema::create('confectionary_images', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Confectionary::class, "confectionary_id");
            $table->longText('image_url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('confectionary_images');
    }
};
