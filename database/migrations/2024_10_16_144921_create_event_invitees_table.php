<?php

use App\Models\BookedEvent;
use App\Models\Event;
use App\Models\PurchasedTicket;
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
        Schema::create('event_invitees', function (Blueprint $table) {
            $table->id();
            $table->string("email");
            $table->foreignIdFor(Event::class, "event_id");
            $table->foreignIdFor(User::class, "user_id");
            $table->foreignIdFor(PurchasedTicket::class, "purchased_ticket_id");
            $table->longText('invitation_url')->nullable();
            $table->longText('code')->nullable();
            $table->enum('status',['pending','accepted','rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_invitees');
    }
};
