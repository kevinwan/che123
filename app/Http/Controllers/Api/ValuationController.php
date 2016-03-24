<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Model\Valuation\Che300;
use App\Model\Valuation\CheChong;
use App\Model\Valuation\JingZhenGu;
use Request;

class ValuationController extends Controller
{

    public function index(Request $request)
    {
       $start_time = time();
       $Che300Model = new Che300();
       $data['che300'] = $Che300Model->valuation($request::all());

       $CheChongModel = new CheChong();
       $data['chechong'] = $CheChongModel->valuation($request::all());

       $JingZhenGuModel = new JingZhenGu();
       $data['jingzhengu'] = $JingZhenGuModel->valuation($request::all());

       $end_time = time();

       $diff_time = $end_time - $start_time;
       return ['success' => true, 'data' => $data, 'time' => $diff_time];
    }


}
