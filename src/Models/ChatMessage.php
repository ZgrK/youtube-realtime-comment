<?php

namespace YoutubeChatCapture\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'chat_messages';
    protected $fillable = [
        'message_content',
        'user_youtube_id',
        'user_display_name',
        'timestamp',
        'chat_id',
        'live_stream_id'
    ];
    
    public $timestamps = false;
} 