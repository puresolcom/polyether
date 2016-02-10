<?php

namespace Polyether\Backend\Http\Controllers\Auth;

use Auth;
use Etherbase\App\Repositories\AuditRepository as Audit;
use Etherbase\App\User;
use Flash;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;
use Polyether\Backend\Http\Controllers\Controller;
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

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct ()
    {
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator (array $data)
    {
        return Validator::make($data, [
            'first_name' => 'required|min:3|max:255',
            'last_name'  => 'required|min:3|max:255',
            'username'   => 'required|min:3|max:255',
            'email'      => 'required|email|max:255|unique:users',
            'password'   => 'required|confirmed|min:6',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array $data
     *
     * @return User
     */
    protected function create (array $data)
    {
        $user = User::create([
            'first_name' => $data[ 'first_name' ],
            'last_name'  => $data[ 'last_name' ],
            'username'   => $data[ 'username' ],
            'email'      => $data[ 'email' ],
            'password'   => bcrypt($data[ 'password' ]),
        ]);

        Flash::success("Welcome" . $user->first_name . ", your user has been created");

        return $user;
    }

    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function postLogin (Request $request)
    {

        $this->validate($request, [
            'username' => 'required|min:3|max:255',
            'password' => 'required',
        ]);

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials, $request->has('remember'))) {

            $user = Auth::user();
            // Allow only if user is root or enabled.
            if (('root' == $user->username) || ($user->enabled)) {
                Flash::success("Welcome " . Auth::user()->first_name);
                return redirect()->intended($this->redirectPath());
            } else {
                Auth::logout();
                return redirect(route('login'))
                    ->withInput($request->only('username', 'remember'))
                    ->withErrors([
                        'username' => 'Unable to login using the login credentials provided',
                    ]);
            }
        }
        return redirect($this->loginPath())
            ->withInput($request->only('username', 'remember'))
            ->withErrors($this->getFailedLoginMessage());
    }

    /**
     * Show the application login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLogin ()
    {

        if (null !== Auth::user())
            return redirect()->intended($this->redirectPath());

        $page_title = "Login";

        return view('backend::auth.login', compact('page_title'));
    }

    /**
     * Show the application registration form.
     *
     * @return \Illuminate\Http\Response
     */
    public function getRegister ()
    {
        $page_title = "Register";

        return view('backend::auth.register', compact('page_title'));
    }

}
