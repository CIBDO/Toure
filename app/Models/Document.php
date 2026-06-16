<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Document extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'category',
        'title',
        'description',
        'date_document',
        'tags',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'checksum',
        'is_private',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_document' => 'date',
        'tags' => 'array',
        'is_private' => 'boolean',
        'size' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function documentable()
    {
        return $this->morphTo();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isPreviewable(): bool
    {
        $previewable = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        return in_array(strtolower($this->mime_type), $previewable, true);
    }
}
