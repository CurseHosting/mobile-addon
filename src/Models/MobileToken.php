<?php

namespace App\MobileAddon\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MobileToken extends Model
{
    const HIGH_VALUE = 'HV';
    const LOW_VALUE = 'LV';

    protected $hidden = ['token'];

    protected $fillable = ['user_id', 'type', 'uuid', 'expires_at'];

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($appToken) {
            $appToken->token = self::generateKey();
		});
    }

    public function scopeValidFor($query, $uuid)
    {
        return $query
            ->where('expires_at', '>', now())
            ->where('type', self::HIGH_VALUE)
            ->where('uuid', $uuid);
    }

    public function scopeValidExchangeFor($query, $uuid)
    {
        return $query
            ->where('expires_at', '>', now())
            ->where('type', self::LOW_VALUE)
            ->where('uuid', $uuid);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function generateKey()
    {
        return str_random(64);
    }

}
