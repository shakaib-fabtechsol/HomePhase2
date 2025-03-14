<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    protected $fillable = [
        'user_id',
        'account_holder_name',
        'bank',
        'branch_name',
        'ssn',
        'account_number',
        'bank_routing_number',
    ];
}