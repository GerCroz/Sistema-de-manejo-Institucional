<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'title',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function institutions(): BelongsToMany
    {
        return $this->belongsToMany(Institution::class)->withPivot('role')->withTimestamps();
    }
}
