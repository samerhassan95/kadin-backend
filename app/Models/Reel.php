<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\Loadable;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * App\Models\Reel
 *
 * @property int $id
 * @property int $shop_id
 * @property string $video_url
 * @property string|null $description
 * @property boolean $active
 * @property int $likes_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Shop $shop
 * @property-read \Illuminate\Database\Eloquent\Collection|Like[] $likes
 * @method static Builder|self newModelQuery()
 * @method static Builder|self newQuery()
 * @method static Builder|self query()
 * @method static Builder|self whereActive($value)
 * @method static Builder|self whereCreatedAt($value)
 * @method static Builder|self whereDescription($value)
 * @method static Builder|self whereId($value)
 * @method static Builder|self whereLikesCount($value)
 * @method static Builder|self whereShopId($value)
 * @method static Builder|self whereUpdatedAt($value)
 * @method static Builder|self whereVideoUrl($value)
 * @mixin Eloquent
 */
class Reel extends Model
{
    use HasFactory, Loadable;

    protected $guarded = ['id'];

    protected $fillable = [
        'shop_id',
        'product_id',
        'title',
        'video_url',
        'description',
        'active',
        'is_active',
        'likes_count'
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_active' => 'boolean',
        'likes_count' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likable');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function isLikedByUser($userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->likes()->where('user_id', $userId)->exists();
    }
}