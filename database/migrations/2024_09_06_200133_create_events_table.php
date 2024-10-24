<?php

use App\Models\User;
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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('name', 255);
            $table->longText("description")->nullable();
            $table->longText("slug")->nullable();
            $table->text("location")->nullable();
            $table->text("streaming_url")->nullable();

            $table->date("start_date");
            $table->date("end_date")->nullable();
            $table->time("start_time");
            $table->time("end_time")->nullable();

            $table->boolean("is_complete")->default(false)->nullable();
            $table->enum("type", ['physical','virtual', 'hybrid'])->default('physical');
            $table->string('organizer_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
