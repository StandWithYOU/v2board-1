<?php

namespace App\Http\Controllers\Passport;

use App\Http\Controllers\Controller;
use App\Http\Requests\Passport\AuthRegister;
use App\Http\Requests\Passport\AuthForget;
use App\Http\Requests\Passport\AuthLogin;
use App\Models\InvitePackage;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\Plan;
use App\Models\User;
use App\Models\InviteCode;
use App\Utils\Helper;
use App\Utils\Dict;
use App\Utils\CacheKey;
use Illuminate\Support\Facades\DB;
use ReCaptcha\ReCaptcha;

class AuthController extends Controller
{
    /**
     * register
     *
     * @param AuthRegister $request
     * @return JsonResponse
     */
    public function register(AuthRegister $request): JsonResponse
    {
        $reqRecaptchaData = $request->input('recaptcha_data');
        $reqEmail = $request->input('email');
        $reqEmailCode = $request->input('email_code');
        $reqPassword = $request->input('password');
        $reqInviteCode = $request->input('invite_code');

        if ((int)config('v2board.recaptcha_enable', 0)) {
            $recaptcha = new ReCaptcha(config('v2board.recaptcha_key'));
            $recaptchaResp = $recaptcha->verify($reqRecaptchaData);
            if (!$recaptchaResp->isSuccess()) {
                abort(500, __('Invalid code is incorrect'));
            }
        }

        if ((int)config('v2board.email_whitelist_enable', 0)) {
            if (!Helper::emailSuffixVerify(
                $reqEmail,
                config('v2board.email_whitelist_suffix', Dict::EMAIL_WHITELIST_SUFFIX_DEFAULT))
            ) {
                abort(500, __('Email suffix is not in the Whitelist'));
            }
        }

        if ((int)config('v2board.email_gmail_limit_enable', 0)) {
            $prefix = explode('@', $reqEmail)[0];
            if (strpos($prefix, '.') !== false || strpos($prefix, '+') !== false) {
                abort(500, __('Gmail alias is not supported'));
            }
        }

        if ((int)config('v2board.stop_register', 0)) {
            abort(500, __('Registration has closed'));
        }
        if ((int)config('v2board.invite_force', 0)) {
            if (empty($request->input('invite_code'))) {
                abort(500, __('You must use the invitation code to register'));
            }
        }

        if ((int)config('v2board.email_verify', 0)) {
            if (empty($reqEmailCode)) {
                abort(500, __('Email verification code cannot be empty'));
            }
            if (Cache::get(CacheKey::get(CacheKey::EMAIL_VERIFY_CODE, $reqEmail)) !== $reqEmailCode) {
                abort(500, __('Incorrect email verification code'));
            }
        }
        $existUser = User::findByEmail($reqEmail);
        if ($existUser) {
            abort(500, __('Email already exists'));
        }


        DB::beginTransaction();
        $user = new User();
        $user->setAttribute(User::FIELD_EMAIL, $reqEmail);
        $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqPassword, PASSWORD_ARGON2ID));
        $user->setAttribute(User::FIELD_UUID, Helper::guid(true));
        $user->setAttribute(User::FIELD_TOKEN, Helper::guid());

        if ($reqInviteCode) {
            $inviteCode = InviteCode::where('code', $reqInviteCode)
                ->where(InviteCode::FIELD_STATUS, InviteCode::STATUS_UNUSED)
                ->first();

            /**
             * @var InviteCode $inviteCode
             */
            if ($inviteCode === null) {
                if ((int)config('v2board.invite_force', 0)) {
                    abort(500, __('Invalid invitation code'));
                }
            } else {
                $user->setAttribute(User::FIELD_INVITE_USER_ID, $inviteCode->getAttribute(InviteCode::FIELD_USER_ID) ?
                    $inviteCode->getAttribute(InviteCode::FIELD_USER_ID) : null);
                if (!(int)config('v2board.invite_never_expire', 0)) {
                    $inviteCode->setAttribute(InviteCode::FIELD_STATUS, InviteCode::STATUS_USED);
                    if (!$inviteCode->save()) {
                        DB::rollBack();
                        abort(500, __('Save failed'));
                    }
                }
            }
        }

        // try out
        if ((int)config('v2board.try_out_plan_id', 0)) {
            /**
             * @var Plan $plan
             */
            $plan = Plan::find(config('v2board.try_out_plan_id'));
            if ($plan !== null) {
                $user->setAttribute(User::FIELD_TRANSFER_ENABLE, $plan->getAttribute(User::FIELD_TRANSFER_ENABLE) * 1073741824);
                $user->setAttribute(User::FIELD_PLAN_ID, $plan->getKey());
                $user->setAttribute(User::FIELD_GROUP_ID, $plan->getAttribute(Plan::FIELD_GROUP_ID) ?: 0);
                $configTryOutHour = (config('v2board.try_out_hour', 1) * 3600);
                if ($configTryOutHour === 0) {
                    $user->setAttribute(User::FIELD_EXPIRED_AT, null);
                } else {
                    $user->setAttribute(User::FIELD_EXPIRED_AT, time() + (config('v2board.try_out_hour', 1) * 3600));
                }
            }
        }

        if (!$user->save()) {
            DB::rollBack();
            abort(500, __('Register failed'));
        }

        //invite plan
        $inviteUserId = $user->getAttribute(User::FIELD_INVITE_USER_ID);
        $configPackagePlanId = (int)config('v2board.package_plan_id', 0);
        if ($configPackagePlanId > 0 && $inviteUserId > 0) {
            $packagePlan = Plan::find($configPackagePlanId);
            $inviteUser =  User::find($inviteUserId);

            if ($packagePlan !== null || $inviteUser === null) {
                $configPackageLimit = (int)config('v2board.package_limit', 3);
                $configPackageRecoveryLimit = (boolean)config('v2board.package_recovery_limit', 0);
                $availableInvitePackageNumber = $user->calAvailableNumberWithInvitePackages($configPackageLimit,
                    $configPackageRecoveryLimit);

                if ($availableInvitePackageNumber > 0) {
                    //赠送流量
                    $inviteUser->buyPlan($packagePlan, null,  true );
                    if (!$inviteUser->save()) {
                        DB::rollBack();
                        abort(500, __('Save failed'));
                    }
                    //添加日志
                    $invitePackage = new InvitePackage();
                    $invitePackage->setAttribute(InvitePackage::FIELD_VALUE, $packagePlan->getAttribute(Plan::FIELD_TRANSFER_ENABLE));
                    $invitePackage->setAttribute(InvitePackage::FIELD_STATUS, InvitePackage::STATUS_ACTIVATED);
                    $invitePackage->setAttribute(InvitePackage::FIELD_USER_ID, $inviteUserId);
                    $invitePackage->setAttribute(InvitePackage::FIELD_FROM_USER_ID, $user->getKey());
                    if (!$invitePackage->save()) {
                        DB::rollBack();
                        abort(500, __('Save failed'));
                    }
                }
            }
        }

        //自动生成一个邀请码
        $inviteCodesCount = $user->countUnusedInviteCodes();
        $inviteGenLimit = config('v2board.invite_gen_limit', 5);
        if ($inviteCodesCount < $inviteGenLimit) {
            $inviteCode = new InviteCode();
            $inviteCode->setAttribute(InviteCode::FIELD_USER_ID, $user->getKey());
            $inviteCode->setAttribute(InviteCode::FIELD_CODE, Helper::randomChar(8));
            if (!$inviteCode->save()) {
                DB::rollBack();
                abort(500, __('Save failed'));
            }
        }

        DB::commit();

        if ((int)config('v2board.email_verify', 0)) {
            Cache::forget(CacheKey::get(CacheKey::EMAIL_VERIFY_CODE, $request->input('email')));
        }

        $request->session()->put('email', $user->getAttribute(User::FIELD_EMAIL));
        $request->session()->put('id', $user->getKey());
        return response()->json([
            'data' => true
        ]);
    }

    /**
     * login
     *
     * @param AuthLogin $request
     * @return Application|ResponseFactory|Response
     */
    public function login(AuthLogin $request)
    {
        $reqEmail = $request->input('email');
        $reqPassword = $request->input('password');

        /**
         * @var User $user
         */
        $user = User::findByEmail($reqEmail);
        if ($user === null) {
            abort(500, __('Incorrect email or password'));
        }

        if (!Helper::multiPasswordVerify(
            $user->getAttribute(User::FIELD_PASSWORD_ALGO),
            $user->getAttribute(User::FIELD_PASSWORD_SALT),
            $reqPassword,
            $user->getAttribute(User::FIELD_PASSWORD))
        ) {
            abort(500, __('Incorrect email or password'));
        }

        if ($user->isBanned()) {
            abort(500, __('Your account has been suspended'));
        }

        $data = [
            'token' => $user->getAttribute(User::FIELD_TOKEN),
            'auth_data' => base64_encode("{$user->getAttribute(User::FIELD_EMAIL)}:{$user->getAttribute(User::FIELD_PASSWORD)}")
        ];
        $request->session()->put('email', $user->getAttribute(User::FIELD_EMAIL));
        $request->session()->put('id', $user->getAttribute(User::FIELD_ID));
        if ($user->isAdmin()) {
            $request->session()->put('is_admin', true);
            $data['is_admin'] = true;
        }
        if ($user->isStaff()) {
            $request->session()->put('is_staff', true);
            $data['is_staff'] = true;
        }
        return response([
            'data' => $data
        ]);
    }

    public function token2Login(Request $request)
    {
        if ($request->input('token')) {
            $redirect = '/#/login?verify=' . $request->input('token') . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
            if (config('v2board.app_url')) {
                $location = config('v2board.app_url') . $redirect;
            } else {
                $location = url($redirect);
            }
            return redirect()->to($location)->send();
        }

        if (empty($request->input('verify'))) {
            abort(500, '参数错误');

        }
        $key = CacheKey::get(CacheKey::TEMP_TOKEN, $request->input('verify'));
        $userId = Cache::get($key);
        if (!$userId) {
            abort(500, __('Token error'));
        }
        $user = User::find($userId);
        if (!$user) {
            abort(500, __('The user does not '));
        }
        if ($user->banned) {
            abort(500, __('Your account has been suspended'));
        }
        $request->session()->put('email', $user->email);
        $request->session()->put('id', $user->id);
        if ($user->is_admin) {
            $request->session()->put('is_admin', true);
        }

        return response([
            'data' => true
        ]);
    }

    /**
     * get temp token
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function getTempToken(Request $request)
    {
        $reqToken = $request->input('token');
        /**
         * @var User $user
         */
        $user = User::findByToken($reqToken);
        if ($user === null) {
            abort(500, __('Token error'));
        }

        $code = Helper::guid();
        $key = CacheKey::get(CacheKey::TEMP_TOKEN, $code);
        Cache::put($key, $user->getKey(), 60);
        return response([
            'data' => $code
        ]);
    }

    public function getQuickLoginUrl(Request $request)
    {
        $authData = explode(':', base64_decode($request->input('auth_data')));
        if (!isset($authData[0])) {
            abort(500, __('Token error'));
        }

        $user = User::where('email', $authData[0])
            ->where('password', $authData[1])
            ->first();
        if (!$user) {
            abort(500, __('Token error'));
        }

        $code = Helper::guid();
        $key = CacheKey::get(CacheKey::TEMP_TOKEN, $code);
        Cache::put($key, $user->id, 60);
        $redirect = '/#/login?verify=' . $code . '&redirect=' . ($request->input('redirect') ? $request->input('redirect') : 'dashboard');
        if (config('v2board.app_url')) {
            $url = config('v2board.app_url') . $redirect;
        } else {
            $url = url($redirect);
        }
        return response([
            'data' => $url
        ]);
    }

    /**
     * check
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function check(Request $request)
    {
        $sessionId = $request->session()->get('id');
        $sessionIsAdmin = $request->session()->get('is_admin');
        $data = [
            'is_login' => (bool)$sessionId
        ];
        if ($sessionIsAdmin) {
            $data['is_admin'] = true;
        }
        return response([
            'data' => $data
        ]);
    }

    /**
     * forget
     *
     * @param AuthForget $request
     * @return Application|ResponseFactory|Response
     */
    public function forget(AuthForget $request)
    {
        $reqEmail = $request->input('email');
        $reqEmailCode = $request->input('email_code');
        $reqPassword = $request->input('password');

        if (Cache::get(CacheKey::get(CacheKey::EMAIL_VERIFY_CODE, $reqEmail)) !== $reqEmailCode) {
            abort(500, __('Incorrect email verification code'));
        }

        /**
         * @var User $user
         */
        $user = User::findByEmail($reqEmail);
        if ($user === null) {
            abort(500, __('This email is not registered in the system'));
        }

        $user->setAttribute(User::FIELD_PASSWORD, password_hash($reqPassword, PASSWORD_ARGON2ID));
        $user->setAttribute(User::FIELD_PASSWORD_ALGO, null);
        $user->setAttribute(User::FIELD_PASSWORD_SALT, null);


        if (!$user->save()) {
            abort(500, __('Reset failed'));
        }
        Cache::forget(CacheKey::get(CacheKey::EMAIL_VERIFY_CODE, $reqEmail));
        return response([
            'data' => true
        ]);
    }

}
