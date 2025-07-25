<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Lib\Intended;
use App\Models\DeviceToken;
use App\Models\UserDevice;
use App\Models\UserLogin;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller {

    use AuthenticatesUsers;

    protected $username;

    public function __construct() {
        parent::__construct();
        $this->username = $this->findUsername();
    }

    public function showLoginForm() {
        $pageTitle = "Login";
        Intended::identifyRoute();
        return view('Template::user.auth.login', compact('pageTitle'));
    }

    public function login(Request $request) {

        $this->validateLogin($request);

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        Intended::reAssignSession();

        return $this->sendFailedLoginResponse($request);
    }

    public function findUsername() {
        $login = request()->input('username');

        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        request()->merge([$fieldType => $login]);
        return $fieldType;
    }

    public function username() {
        return $this->username;
    }

    protected function validateLogin($request) {

        $validator = Validator::make($request->all(), [
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ]);
        if ($validator->fails()) {
            Intended::reAssignSession();
            $validator->validate();
        }

    }

    public function logout() {
        $this->guard()->logout();
        request()->session()->invalidate();

        $notify[] = ['success', 'You have been logged out.'];
        return to_route('user.login')->withNotify($notify);
    }

    public function authenticated(Request $request, $user) {
        $ip = getRealIP();

        if (gs('device_limit') && $user->plan) {
            $userDevices     = UserDevice::where('user_id', $user->id)->distinct()->pluck('device_id')->toArray();
            $currentDeviceId = md5($_SERVER['HTTP_USER_AGENT']);

            if (count($userDevices) == @$user->plan->device_limit && !in_array($currentDeviceId, $userDevices)) {
                session()->flush();
                Auth::logout();
                $notify[] = ['error', 'Device limit is over'];
                return to_route('user.login')->withNotify($notify);
            }
            $existDevice = UserDevice::where('user_id', $user->id)->where('device_id', $currentDeviceId)->exists();
            if (!$existDevice) {
                $device            = new UserDevice();
                $device->user_id   = $user->id;
                $device->device_id = $currentDeviceId;
                $device->save();
            }
        }

        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();
        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();

        if (session()->has('device_token')) {
            $deviceToken = session()->get('device_token');
            $token       = DeviceToken::where('token', $deviceToken)->first();
            if ($token) {
                $token->user_id = auth()->id();
                $token->save();
            }
            session()->forget('device_token');
        }

        $redirection = Intended::getRedirection();
        return $redirection ? $redirection : to_route('user.home');
    }

}
