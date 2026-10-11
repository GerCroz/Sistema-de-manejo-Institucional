<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

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
