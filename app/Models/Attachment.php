<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'file_path',
    ];
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}