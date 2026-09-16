<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportPhoto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'report_id',
        'file_path',
        'original_name',
        'mime_type',
        'created_at',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
