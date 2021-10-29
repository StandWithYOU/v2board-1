<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InvitePackageFetch;
use App\Http\Requests\Admin\InvitePackageUpdate;
use App\Models\InvitePackage;
use App\Models\Order;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class InvitePackageController extends Controller
{

    /**
     * _filter
     *
     * @param Request $request
     * @param $builder
     */
    private function _filter(Request $request, $builder)
    {
        $reqFilter = (array)$request->input('filter');
        foreach ($reqFilter as $filter) {
            //兼容
            if ($filter['condition'] === '模糊' || $filter['condition'] === 'like') {
                $filter['condition'] = 'like';
                $filter['value'] = "%{$filter['value']}%";
            }
            $builder->where($filter['key'], $filter['condition'], $filter['value']);
        }
    }


    /**
     * fetch
     *
     * @param InvitePackageFetch $request
     * @return Application|ResponseFactory|Response
     */
    public function fetch(InvitePackageFetch $request)
    {
        $reqCurrent = $request->input('current') ? $request->input('current') : 1;
        $reqPageSize = $request->input('pageSize') >= 10 ? $request->input('pageSize') : 10;
        $invitePackageModel = InvitePackage::orderBy(Order::FIELD_CREATED_AT, "DESC");
        $this->_filter($request, $invitePackageModel);
        $total = $invitePackageModel->count();
        $orders = $invitePackageModel->forPage($reqCurrent, $reqPageSize)
            ->get();
        return response([
            'data' => $orders,
            'total' => $total
        ]);
    }

}

