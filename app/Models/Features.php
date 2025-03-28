<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Features extends Model
{
    use HasFactory;
    protected $table = 'features';

    protected $fillable = [
        'id',
        'name',
        'model_id',
        'prompt',
        'presetStyle',
        'initImageId',
        'preprocessorId',
        'strengthType',
        'description',
        'weight',
        'slug',
        'detech_face',
        'remove_bg',
        'is_effect',
        'is_pro',
        'status',
        'image',
        'created_at',
        'updated_at',
        'api_endpoint',
        'image',
    ];

    // Định nghĩa mối quan hệ với model SubFeatures
    public function subFeatures()
    {
        return $this->hasMany(SubFeatures::class, 'feature_id', 'id')->where('status', 1);
    }

    // Thêm accessor để trả về URL đầy đủ của ảnh
    protected function imageUrl(): Attribute
    {
        return Attribute::get(function ($value, $attributes) {
            return $attributes['image'] ? asset('storage/' . $attributes['image']) : null;
        });
    }
    public function sizes() {
        return $this->belongsToMany(ImageSize::class, 'features_sizes', 'feature_id', 'size_id');
    }
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    public function getHighlightsAttribute()
    {
        // Include the feature itself if it is highlighted
        $highlightedItems = $this->is_highlight ? collect([$this]) : collect();

        // Add any highlighted subFeatures
        $highlightedItems = $highlightedItems->merge(
            $this->subFeatures->filter(fn($subFeature) => $subFeature->is_highlight == 1)
        );

        return $highlightedItems;
    }
}
