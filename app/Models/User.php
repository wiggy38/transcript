<?php

namespace App\Models;

use App\Enums\Plan;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// is_admin, plan, google_id, inscription_ip et lien_en_attente ne sont pas fillable :
// ils ne se règlent jamais depuis une requête de formulaire.
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token', 'google_id', 'inscription_ip'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    /** Valeurs par défaut de la base, reprises sur les instances neuves. */
    protected $attributes = [
        'locale' => 'fr',
        'is_admin' => false,
        'plan' => 'free',
    ];

    public function transcriptions(): HasMany
    {
        return $this->hasMany(Transcription::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'plan' => Plan::class,
        ];
    }
}
