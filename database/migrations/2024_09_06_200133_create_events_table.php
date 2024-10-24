<?php

use App\Models\EventOrganizer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->date("end_date")->default(DB::raw('CURRENT_DATE'))->nullable();
            $table->time("start_time");
            $table->time("end_time")->nullable();

            $table->boolean("is_complete")->default(false)->nullable();
            $table->boolean("is_free")->default(false)->nullable();
            $table->boolean("featured")->default(false)->nullable();
            $table->enum("type", ['physical','virtual', 'hybrid'])->default('physical');
            $table->longText("tags")->nullable();
            $table->integer("rating")->nullable();
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
