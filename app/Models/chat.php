<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
        use HasFactory;

    protected $fillable = [
        'text', 
        'users_id',
        'output_text',
    'is_edited',  
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
