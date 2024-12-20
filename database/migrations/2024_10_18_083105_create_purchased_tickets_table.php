<?php

use App\Models\Event;
use App\Models\Ticket;
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
        Schema::create('purchased_tickets', function (Blueprint $table) {
            $table->id();
            $table->longText('reference');
            $table->foreignIdFor(User::class, "user_id");
            $table->foreignIdFor(Ticket::class, "ticket_id");
            $table->foreignIdFor(Event::class, "event_id");
            $table->enum('status',['pending','paid','refund'])->default('pending');
            $table->bigInteger("invitations");
            $table->bigInteger("invitations_sent")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchased_tickets');
    }
};
