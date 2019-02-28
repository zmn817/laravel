<?php

namespace ThirtyThree\Sms\Models;

use Illuminate\Database\Eloquent\Model;

class SmsMessage extends Model
{
    protected $table = 'sms_messages';

    protected $fillable = [
        'country_code',
        'phone_number',
        'content',
        'template',
        'data',
        'success',
        'errors',
    ];

    protected $casts = ['data' => 'json', 'success' => 'boolean', 'errors' => 'json'];
}
