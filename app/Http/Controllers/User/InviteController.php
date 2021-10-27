<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InvitePackage;
use DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Order;
use App\Models\InviteCode;
use App\Models\Plan;
use App\Utils\Helper;
use Illuminate\Http\Response;

class InviteController extends Controller
{
    /**
     * save
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function save(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        $inviteCodesCount = $user->countUnusedInviteCodes();
        $inviteGenLimit = config('v2board.invite_gen_limit', 5);


        if ($inviteCodesCount >= $inviteGenLimit) {
            abort(500, __('The maximum number of creations has been reached'));
        }
        $inviteCode = new InviteCode();
        $inviteCode->setAttribute(InviteCode::FIELD_USER_ID, $sessionId);
        $inviteCode->setAttribute(InviteCode::FIELD_CODE, Helper::randomChar(8));
        return response([
            'data' => $inviteCode->save()
        ]);
    }

    /**
     * details
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function details(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }
        $invitedOrderDetails = $user->getInvitedOrderDetails(Order::STATUS_COMPLETED);
        return response([
            'data' => $invitedOrderDetails,
        ]);
    }

    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        $unUsedCodes = $user->getUnusedInviteCodes();
        $defaultCommissionRate = config('v2board.invite_commission', 10);
        $defaultPackagePlanId    =  config('v2board.package_plan_id', 0);
        $defaultPackageLimit =  (int)config('v2board.package_limit', 3);
        $defaultPackageRecoveryEnable =  (boolean)config('v2board.package_recovery_enable', 0);
        $commissionRate = $user->getAttribute(User::FIELD_COMMISSION_RATE) ?: $defaultCommissionRate;


        $stat = [
            //已注册用户数
            $user->countInvitedUsers(),
            //有效的佣金
            $user->statCommissionBalance(Order::STATUS_COMPLETED, Order::COMMISSION_STATUS_VALID),
            //确认中的佣金
            $user->statCommissionBalance(Order::STATUS_COMPLETED, Order::COMMISSION_STATUS_PENDING),
            //佣金比例
            (int)$commissionRate,
            //可用佣金
            (int)$user->getAttribute(User::FIELD_COMMISSION_BALANCE),
            //邀请礼包ID
            (int)$defaultPackagePlanId,
            //邀请礼包的剩余次数
            $user->calAvailableNumberWithInvitePackages($defaultPackageLimit, $defaultPackageRecoveryEnable),
            //已获得的邀请礼包总数
            $user->countInvitePackages(),
            //系统默认邀请礼包总数
            $defaultPackageLimit,
            //系统是否开启限制恢复机制
            $defaultPackageRecoveryEnable,
        ];

        return response([
            'data' => [
                'codes' => $unUsedCodes,
                'stat' => $stat
            ]
        ]);
    }


    /**
     * 获取礼包列表
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function packages(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user ===  null) {
            abort(500, __('The user does not exist'));
        }

        $invitedOrderDetails = $user->invitePackages()->get();
        return response([
            'data' => $invitedOrderDetails,
        ]);
    }


    /**
     * 使用礼包
     *
     * @param Request $request
     * @return Application|ResponseFactory|Response
     * @throws \Throwable
     */
    public function applyPackage(Request $request)
    {
        $sessionId = $request->session()->get('id');
        $reqPackageId = $request->input('package_id');

        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        /**
         * @var InvitePackage $package
         */
        $package = InvitePackage::find($reqPackageId);
        if ($package === null) {
            abort(500, __('Invite package does not exist'));
        }

        if ($package->getAttribute(InvitePackage::FIELD_USER_ID) !== $sessionId) {
            abort(500, __("Cannot use this package"));
        }

        if ($package->isInvalid()) {
            abort(500, __("The package is invalid"));
        }


        if ($package->isUsed()) {
            abort(500, __("The package has been used" ));
        }

        /**
         * @var Plan $packagePlan
         */
        $packagePlan = $package->plan();
        if ($packagePlan === null) {
            abort(500, __("Subscription plan does not exist"));
        }

        $planCycle =  $package->getAttribute(InvitePackage::FIELD_PLAN_CYCLE);
        $resetOnetimeTrafficEnable = (boolean)config('v2board.reset_onetime_traffic_enable', 1);
        $userExpiredAt = $user->getAttribute(User::FIELD_EXPIRED_AT);

        DB::beginTransaction();
        switch ($planCycle) {
            case Order::CYCLE_ONETIME:
                if ($resetOnetimeTrafficEnable || $userExpiredAt !== null) {
                    $user->resetTraffic();
                    $user->buyPlan($packagePlan);
                } else {
                    $user->buyPlan($packagePlan, null,  true );
                }
                break;
            case Order::CYCLE_RESET_PRICE:
                $user->resetTraffic();
                break;
            default:
                $user->resetTraffic();
                $user->buyPlan($packagePlan, Plan::expiredTime($planCycle, $userExpiredAt));
        }

        if (!$user->save()) {
            DB::rollBack();
            abort(500, __("Save failed"));
        }

        $package->setAttribute(InvitePackage::FIELD_STATUS, InvitePackage::STATUS_USED);
        if (!$package->save()) {
            DB::rollBack();
            abort(500, __("Save failed"));
        }

        DB::commit();
        return response([
            'data' => true,
        ]);
    }
}
