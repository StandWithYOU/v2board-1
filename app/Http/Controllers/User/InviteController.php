<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\InviteCode;
use App\Models\InvitePackage;
use App\Models\Order;
use App\Models\Plan;
use App\Models\User;
use App\Utils\Helper;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
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
        $defaultPackagePlanId = (int)config('v2board.package_plan_id', 0);
        $defaultPackageLimit = (int)config('v2board.package_limit', 3);
        $defaultPackageRecoveryLimit = (int)config('v2board.package_recovery_enable', 1);

        $defaultPlan = Plan::find($defaultPackagePlanId);
        $stat = [
            //已注册用户数
            // $user->countInvitedUsers(),
            //邀请礼包的剩余次数
            //$user->calAvailableNumberWithInvitePackages($defaultPackageLimit, $defaultPackageRecoveryLimit),
            //已获得礼包数
            $user->countActivatedInvitePackages(),
            //每次邀请可获得流量(GB)
            $defaultPlan !== null ? $defaultPlan->getAttribute(Plan::FIELD_TRANSFER_ENABLE) : 0,
            //已获得总流量(GB)
            $user->sumActivatedInvitePackagesValues(),
            //礼包购买恢复次数
            //$defaultPackageRecoveryLimit
        ];

        return response([
            'data' => [
                'codes' => $unUsedCodes,
                'stat' => $stat,
                'invite_url' => config('v2board.invite_url')
            ]
        ]);
    }


    /**
     * stats
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function stats(Request $request)
    {
        $sessionId = $request->session()->get('id');
        /**
         * @var User $user
         */
        $user = User::find($sessionId);
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        return response([
            'data' => [
                'invite_users' => $user->countInvitedUsers(),
                'invite_activated_packages' => $user->countActivatedInvitePackages(),
                'total_values' => $user->sumActivatedInvitePackagesValues(),
                'invite_code' => $user->getUnusedInviteCode() ? $user->getUnusedInviteCode()->getAttribute(InviteCode::FIELD_CODE) :null,
                'invite_url' => config('v2board.invite_url','')
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
        if ($user === null) {
            abort(500, __('The user does not exist'));
        }

        $invitePackages = $user->invitePackages()->get();

        foreach ($invitePackages as &$package) {
            /**
             * @var InvitePackage $package
             */
            $fromUser = $package->fromUser();
            if ($fromUser !== null) {
                $package['from_user_email'] = Helper::hiddenEmail($fromUser->getAttribute(User::FIELD_EMAIL));
            }
        }

        return response([
            'data' => $invitePackages,
        ]);
    }
}
