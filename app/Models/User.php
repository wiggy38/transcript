<?php

namespace App\Models;

use App\Enums\Plan;
use App\Support\Langue;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Paddle\Billable;

// is_admin, plan, google_id, inscription_ip et lien_en_attente ne sont pas fillable :
// ils ne se règlent jamais depuis une requête de formulaire.
#[Fillable(['name', 'email', 'password', 'locale'])]
#[Hidden(['password', 'remember_token', 'google_id', 'inscription_ip'])]
class User extends Authenticatable implements FilamentUser, HasLocalePreference, MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasFactory, MustVerifyEmail, Notifiable;

    /** Langue des notifications et courriels envoyés en file : la préférence du compte, sinon la langue par défaut. */
    public function preferredLocale(): string
    {
        return Langue::pour($this);
    }

    /** Back-office (/admin) : réservé aux comptes is_admin, posé par la commande voxia:make-admin. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin === true;
    }

    /** Valeurs par défaut de la base, reprises sur les instances neuves. */
    protected $attributes = [
        'locale' => null,
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

    public function packAchats(): HasMany
    {
        return $this->hasMany(PackAchat::class);
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
            'abonnement_debut_at' => 'datetime',
        ];
    }
}
