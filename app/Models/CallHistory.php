<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallHistory extends Model
{
    use HasFactory;

    protected $fillable = ['caller_id','callee_id'];


    
public function caller()
{
    return $this->belongsTo(User::class, 'caller_id');
}

public function callee()
{
    return $this->belongsTo(User::class, 'callee_id');
}

}
