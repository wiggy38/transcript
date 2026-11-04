<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/** Donne l'accès au back-office (/admin) à un compte existant. */
class CreerAdministrateur extends Command
{
    protected $signature = 'voxia:make-admin {email : Adresse du compte existant}';

    protected $description = 'Donne l\'accès au back-office à un compte existant';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $utilisateur = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if ($utilisateur === null) {
            $this->error(__('administration.commandes.admin_inconnu', ['email' => $email]));

            return self::FAILURE;
        }

        $utilisateur->forceFill(['is_admin' => true])->save();

        $this->info(__('administration.commandes.admin_cree', ['email' => $utilisateur->email]));

        return self::SUCCESS;
    }
}
