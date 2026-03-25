<?php

namespace App\Core\Files\Models;

use App\Models\Organizacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileDownload extends Model
{
    use HasFactory;

    protected $table = 'core_file_downloads';

    protected $fillable = [
        'managed_file_id',
        'organizacion_id',
        'user_id',
        'channel',
        'status',
        'downloaded_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'downloaded_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ManagedFile::class, 'managed_file_id');
    }

    public function organizacion(): BelongsTo
    {
        return $this->belongsTo(Organizacion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
