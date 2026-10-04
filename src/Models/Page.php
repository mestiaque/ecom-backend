<?php

namespace ME\Ecom\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $table = 'ecom_pages';

    protected $fillable = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
