<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function purchasedTickets()
    {
        return $this->hasMany(PurchasedTicket::class);
    }

    public function getAccountAttribute()
    {
        return BankAccount::where('id', $this->bank_account_id)->first();
    }
}
