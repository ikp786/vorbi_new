<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Auth;



class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'unique_id',
        'login_type',
        'type',
        'social_media_id',
        'mobile',
        'status',
        'profile_pic',
        'city',
        'language_id',
        'gender',
        'visible_status',
        'device_token'
    ];


    public function getProfilePicAttribute($value)
    {
        return $value != null ? asset('user_images/'.$value) : asset('default/images.png');
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function topics()
    {
        return $this->belongsToMany(Topic::class);
    }
    
    function language(){

        return $this->belongsTo(Language::class);
    }

    public function notification()
    {
        return $this->hasMany(Notification::class);
    }

    public function ratings()
    {
        return $this->hasMany(Ratting::class, 'to_user_id');
    }


    public function callsMade()
{
    return $this->hasMany(CallHistory::class, 'caller_id');
}

public function callsReceived()
{
    return $this->hasMany(CallHistory::class, 'callee_id');
}

public function allCalls()
{
    $callsMade = $this->callsMade();
    $callsReceived = $this->callsReceived();

    return $callsMade->union($callsReceived);
}

public function allCallsId($date = null)
{
    $callsMade = $this->callsMade()->select('callee_id');
    $callsReceived = $this->callsReceived()->select('callee_id');
    if ($date) {
        $callsMade->whereDate('created_at', $date);
        $callsReceived->whereDate('created_at', $date);
    }

    return $callsMade->union($callsReceived);
}
 
}
