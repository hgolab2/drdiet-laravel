<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'message_id',
        'type',
        'from',
        'to',
        'message_type',
        'body',
        'status',
        'has_media',
        'file_url',
        'local_file_path',
        'payload',
    ];

    protected $casts = [
        'has_media' => 'boolean',
        'payload'   => 'array',
    ];
}
