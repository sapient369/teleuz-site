<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Lib\SocialLogin;
use App\Models\UserDevice;
use App\Models\UserLogin;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Status;

class LoginController extends Controller {
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

    protected $username;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct() {
        parent::__construct();
        $this->username = $this->findUsername();
    }

    public function login(Request $request) {

        $validator = $this->validateLogin($request);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $credentials = request([$this->username, 'password']);
        if (!Auth::attempt($credentials)) {
            $response[] = 'Unauthorized user';
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $response],
            ]);
        }

        $user        = $request->user();
        $tokenResult = $user->createToken('auth_token', ['user'])->plainTextToken;

        $general = gs();
        if ($general->device_limit && $user->plan) {
            $userDevices     = UserDevice::where('user_id', $user->id)->distinct()->pluck('device_id')->toArray();
            $currentDeviceId = md5($_SERVER['HTTP_USER_AGENT']);
            if (count($userDevices) == @$user->plan->device_limit && !in_array($currentDeviceId, $userDevices)) {
                session()->flush();
                Auth::logout();
                $response = 'Device limit is over';
                return response()->json([
                    'remark'  => 'device_limit_error',
                    'status'  => 'error',
                    'message' => ['error' => $response],
                ]);
            }
            $device            = new UserDevice();
            $device->user_id   = $user->id;
            $device->device_id = $currentDeviceId;
            $device->save();
        }

        $this->authenticated($request, $user);

        $response[] = 'Login Successful';
        return response()->json([
            'remark'  => 'login_success',
            'status'  => 'success',
            'message' => ['success' => $response],
            'data'    => [
                'user'         => auth()->user(),
                'access_token' => $tokenResult,
                'token_type'   => 'Bearer',
            ],
        ]);

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

    protected function validateLogin(Request $request) {
        $validationRule = [
            $this->username() => 'required|string',
            'password'        => 'required|string',
        ];

        $validate = Validator::make($request->all(), $validationRule);
        return $validate;

    }

    public function logout() {
        auth()->user()->tokens()->delete();

        $notify[] = 'Logout Successful';
        return response()->json([
            'remark'  => 'logout',
            'status'  => 'success',
            'message' => ['success' => $notify],
        ]);
    }

    public function authenticated(Request $request, $user) {
        $ip        = getRealIP();
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
    }

    public function checkToken(Request $request) {
        $validationRule = [
            'token' => 'required',
        ];

        $validator = Validator::make($request->all(), $validationRule);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }
        $exists = PersonalAccessToken::where('token', hash('sha256', $request->token))->exists();
        if ($exists) {
            $notify[] = 'Token exists';
            return response()->json([
                'remark'  => 'token_exists',
                'status'  => 'success',
                'message' => ['success' => $notify],
            ]);
        }
        $notify[] = 'Token doesn\'t exists';
        return response()->json([
            'remark'  => 'token_not_exists',
            'status'  => 'error',
            'message' => ['success' => $notify],
        ]);

    }

    public function socialLogin(Request $request) {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,facebook,linkedin',
            'token'    => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'remark'  => 'validation_error',
                'status'  => 'error',
                'message' => ['error' => $validator->errors()->all()],
            ]);
        }

        $provider    = $request->provider;
        $socialLogin = new SocialLogin($provider, true);
        try {
            $loginResponse = $socialLogin->login();
            $response[]    = 'Login Successful';
            return response()->json([
                'remark'  => 'login_success',
                'status'  => 'success',
                'message' => ['success' => $response],
                'data'    => $loginResponse,
            ]);
        } catch (\Exception $e) {
            $notify[] = $e->getMessage();
            return response()->json([
                'remark'  => 'login_error',
                'status'  => 'error',
                'message' => ['error' => $notify],
            ]);
        }
    }

}
