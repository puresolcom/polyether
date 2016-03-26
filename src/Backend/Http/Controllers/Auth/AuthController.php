<?php

namespace Polyether\Backend\Http\Controllers\Auth;

use Auth;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Polyether\Backend\Http\Controllers\Controller;
use UserGate;
use Validator;

class AuthController extends Controller
{
    /*
      |--------------------------------------------------------------------------
      | Registration & Login Controller
      |--------------------------------------------------------------------------
      |
      | This controller handles the registration of new users, as well as the
      | authentication of existing users. By default, this controller uses
      | a simple trait to add these behaviors. Why don't you explore it?
      |
     */

    use AuthenticatesAndRegistersUsers;


    protected $redirectTo = '/';

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin(Request $request)
    {

        $this->validate($request, ['username' => 'required|min:3|max:255', 'password' => 'required',]);

        $credentials = $request->only('username', 'password');
        $credentials[ 'enabled' ] = 1;

        if (Auth::attempt($credentials, $request->has('remember'))) {
            return redirect()->intended($this->getRedirectUrl());
        } else {
            return redirect(route('login'))->withInput($request->except(['password']))
                                           ->withErrors([$this->getFailedLoginMessage()]);
        }

    }

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogin()
    {

        if (null !== Auth::user()) {
            return redirect()->intended($this->redirectPath());
        }

        $page_title = "Login";

        return view('backend::auth.login', compact('page_title'));
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister()
    {
        $page_title = "Register";

        return view('backend::auth.register', compact('page_title'));
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name'   => 'required|min:3|max:255',
            'last_name'    => 'required|min:3|max:255',
            'username'     => 'required|min:3|max:32|unique:users',
            'email'        => 'required|email|max:255|unique:users',
            'password'     => 'required|confirmed|min:6',
            'terms_agreed' => 'required',
        ], ['terms_agreed.required' => 'You must agree our TOS in order to proceed with registration']);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return User
     */
    protected function create(array $data)
    {
        $user = UserGate::create([
            'first_name' => $data[ 'first_name' ],
            'last_name'  => $data[ 'last_name' ],
            'username'   => $data[ 'username' ],
            'email'      => $data[ 'email' ],
            'password'   => bcrypt($data[ 'password' ]),
            'enabled'    => true,
        ]);

        if ($user) {
            $user->attachRole(2);
        }

        return $user;
    }

}
