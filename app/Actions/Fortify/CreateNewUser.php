<?php

namespace App\Actions\Fortify;

use App\Models\User;
use App\Services\Comptes\LimiteComptesGratuits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(
        private readonly Request $request,
        private readonly LimiteComptesGratuits $limite,
    ) {}

    /**
     * Valide et crée un compte gratuit. L'adresse IP de création sert à la limite de
     * comptes gratuits ; la langue suit l'interface au moment de l'inscription.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        $ip = $this->request->ip();

        if ($this->limite->atteinte($ip)) {
            throw ValidationException::withMessages([
                'email' => __('comptes.inscription.limite_atteinte'),
            ]);
        }

        $utilisateur = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
            'locale' => app()->getLocale(),
        ]);

        // Hors assignation de masse : l'IP ne se règle jamais depuis le formulaire.
        $utilisateur->forceFill(['inscription_ip' => $ip])->save();

        return $utilisateur;
    }
}
