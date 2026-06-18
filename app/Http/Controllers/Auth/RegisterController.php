<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * Registration is disabled for the public. Only authenticated
     * SuperAdmin/Admin users can create new accounts via UserController.
     * This controller is kept as a fallback but gated behind auth + role.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'office' => ['required', 'string', 'exists:branches,branch_name'],
            'password' => [
                'required', 'string', 'min:8', 'confirmed',
                'regex:/[A-Z]/',      // at least one uppercase
                'regex:/[0-9]/',      // at least one digit
            ],
        ], [
            'office.exists' => 'The selected office/branch is invalid. Please contact your administrator.',
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\Models\User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'office' => $data['office'],
            'password' => $data['password'],  // plaintext — the User model's 'hashed' cast handles bcrypt
            'is_active' => true,
        ]);

        // Assign default 'Staff' role to new users per SXPRESS_LOGIC_SKILL §14
        $user->assignRole('Staff');

        return $user;
    }
}
