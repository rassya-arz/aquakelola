<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedTransaction extends Model
{
    protected $fillable = ['pond_id', 'feed_id', 'type', 'quantity', 'notes', 'user_id'];

    protected function casts(): array
    {
        return ['quantity' => 'float'];
    }

    public function pond(): BelongsTo
    {
        return $this->belongsTo(Pond::class);
    }

    public function feed(): BelongsTo
    {
        return $this->belongsTo(FeedStock::class, 'feed_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function code(): string
    {
        return 'PK-'.str_pad((string) $this->id, 3, '0', STR_PAD_LEFT);
    }
}
