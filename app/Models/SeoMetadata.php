<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $guarded = ['id'];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
