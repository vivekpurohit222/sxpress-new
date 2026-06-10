<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('throttle:5,1')->only('login');
    }

    /**
     * The user has been authenticated.
     * Block inactive users and record last login timestamp.
     *
     * @param  Request  $request
     * @param  mixed    $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function authenticated(Request $request, $user)
    {
        if (!$user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors([
                'email' => 'Your account has been deactivated. Contact your administrator.',
            ]);
        }

        // Record last login timestamp (column may not exist on legacy schema)
        if (in_array('last_login_at', $user->getConnection()->getSchemaBuilder()->getColumnListing('users'))) {
            $user->forceFill(['last_login_at' => now()])->saveQuietly();
        }

        // Return null to let the trait handle the redirect via $redirectTo
        return null;
    }
}
