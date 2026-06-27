<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Song extends Model
{
    //

    protected $fillable = [
        // εδώ μπαίνουν τα ονόματα των στηλών
        'artist',
        'title',
        'youtube_link',
        'status',
        'shazam_order',
        'resolved_at',
    ];
}
