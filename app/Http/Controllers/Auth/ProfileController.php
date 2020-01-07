<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ResetsPasswords;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = '/files';


    public function __construct()
    {
        // @todo - change to verified.
        $this->middleware('auth');
    }

    public function index()
    {
        return view('auth.profile')->with('user', Auth::user());
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $this->validator($data, $user)->validate();

        if ($user) {
            $user->name  = $data['name'] ?? $user->name;
            $user->email = $data['email'] ?? $user->email;
            if (!empty($data['password'])) {
                $this->resetPassword($user, $data['password']);
            } else {
                $user->save();
            }
        }

        return redirect($this->redirectPath())->with('success', __('Profile updated!'));
    }

    /**
     * @param  array  $data
     * @param  Authenticatable  $user
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array &$data, Authenticatable $user)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', 'min:3'],
        ];
        if ($user->email !== $data['email']) {
            $rules['email'] = ['required', 'string', 'email:rfc,dns', 'max:255', 'unique:users'];
        } else {
            unset($data['email']);
        }
        if (!empty($data['password'])) {
            $rules['password'] = ['required', 'confirmed', 'min:8'];
        } else {
            unset($data['password']);
        }

        return Validator::make($data, $rules);
    }
}
