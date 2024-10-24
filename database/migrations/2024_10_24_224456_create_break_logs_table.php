<?php

use App\Models\Event;
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
        Schema::create('break_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'attendee');
            $table->foreignIdFor(Event::class, 'event_id');
            $table->string('reason')->nullable();
            $table->dateTime('break_time');
            $table->dateTime('resume_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('break_logs');
    }
};
