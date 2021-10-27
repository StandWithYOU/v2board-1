<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use App\Models\Plan;
use Illuminate\Http\Response;

class PlanController extends Controller
{
    /**
     * fetch
     *
     * @param Request $request
     * @return ResponseFactory|Response
     */
    public function fetch(Request $request)
    {
        $reqId = (int)$request->input("id");
        $reqDisplayAll = (bool)$request->input('display_all');

        if ($reqId > 0) {
            $plan = Plan::find($reqId);
            if (!$plan) {
                abort(500, __('Subscription plan does not exist'));
            }
            $data = $plan;
        } else {
            if ($reqDisplayAll) {
                $data = Plan::all();
            } else {
                $data = Plan::getShowPlans();
            }
        }

        return response([
            'data' => $data
        ]);
    }
}
