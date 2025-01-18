<?php

namespace YoutubeChatCapture\Models;

use Illuminate\Database\Eloquent\Model;

class ProcessingBatch extends Model
{
    protected $table = 'processing_batches';
    protected $fillable = [
        'batch_status',
        'started_at',
        'completed_at',
        'error_message'
    ];
    
    public $timestamps = false;
} 