<?php

namespace App\Modules\Identity\Actions\Fortify;

use App\Modules\Identity\Enums\Role;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered customer.
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
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^(\+62|62|0)8[0-9]{7,12}$/'],
            'password' => $this->passwordRules(),
        ], [
            'phone.regex' => 'Format nomor HP tidak valid, contoh: 081234567890.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'password' => Hash::make($input['password']),
            ]);

            $user->assignRole(Role::Customer->value);

            return $user;
        });
    }
}
