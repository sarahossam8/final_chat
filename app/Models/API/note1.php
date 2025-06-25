<?php

namespace App\Models\API;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class note1 extends Model
{
    protected $guarded = []; 

    public function note1()
    {
        return $this->belongsTo(User::class);
    
    }
    protected $fillable = [
        'text', 
        'users_id',
        'output_text',
    'is_edited',  
    'title',
    ];

}
