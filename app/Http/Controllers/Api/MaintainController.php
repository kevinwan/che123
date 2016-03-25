<?php namespace App\Http\Controllers\Api;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Input;
use DB;
use App\CarModel;
use App\Detail;
use App\Project;

use App\DetailProjectPrice;
use App\DetailSection;

use Illuminate\Http\Request;
use Validator;

class MaintainController extends Controller {

    private $kilometersPerSection = 7500;	//180天平均公里数
    private $connection;
    private $deviationKilometers = 500;
    private $commonMaintainId = 21;

    public function __construct(){
        $this->connection = DB::reconnect('pingjia');
    }

    /**
     * 姹借溅涔嬪淇濆吇鏁版嵁
     * @param Request $request
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mycar_id' => 'required|integer'
        ]);
        Log::info(Input::get('mycar_id'));
        if ($validator->fails()) {
            echo json_encode(['status'=>'false', 'msg'=>$validator->errors(), 'data'=>(object)null]);die;
        }
        $mycar_id = Input::get('mycar_id');
        //获取保养ID
        $myCarData = $this->connection->table('my_car')->where('id', $mycar_id)->first();
        if (is_null($myCarData)) {
            echo json_encode(['status'=>'false', 'msg'=>'mycar_id没有被发现', 'data'=>(object)null]);die;
        }
        $model_slug = $myCarData->model_detail;
        $kilometers = intval($myCarData->mileage * 10000);
        $detail_id = $this->connection->table('foreign_category_autohome')->where('detail_model_slug', $model_slug)->pluck('dmodel_id');
        $maintain_id = CarModel::where('id', $detail_id)->pluck('maintain_id');
        if (is_null($maintain_id)) {
            $year = $myCarData->year;
            $brand_name = $this->connection->table('open_category')->where('slug', $myCarData->brand)->pluck('name');
            $type_name = $this->connection->table('open_category')->where('slug', $myCarData->model)->pluck('name');
            $typeNames[] = $type_name;
            if (strpos($type_name, $brand_name) !== false) {
                $length = strlen($brand_name);
                $typeNames[] = substr($type_name, $length);
            }
            $typeId = $this->connection->table('autohome_types')->whereIn('name', $typeNames)->pluck('id');
            $nearModels = CarModel::select('maintain_id', 'year')->where('type_id', $typeId)->groupBy('year')->get();
            if (count($nearModels) > 0) {
                foreach ($nearModels as $nearModel) {
                    //取最相近同款年份的保养ID
                    if (!isset($minYearAbs) || abs($nearModel->year - $year) < $minYearAbs) {
                        $minYearAbs = $nearModel->year;
                        $maintain_id = $nearModel->maintain_id;
                    }
                }
            }
        }
        //通配
        if (is_null($maintain_id) && $myCarData->eval_price < 50) {
            $maintain_id = $this->commonMaintainId;
        }
        if (is_null($maintain_id)) {
            echo json_encode(['status'=>"false", 'msg'=>'匹配不到保养信息', 'data'=>(object)null]);die;
        }
        //保养信息
        $detailInfo = Detail::find($maintain_id);
        $projectInfo = Project::all();
        foreach ($projectInfo as $project) {
            $projectMaps[$project->id] = $project->name;
        }
        //价格
        $priceInfo = DetailProjectPrice::where('detail_id', $maintain_id)->get();
        //价格通配
        if(count($priceInfo) == 0) {
            $priceInfo = DetailProjectPrice::where('detail_id', $this->commonMaintainId)->get();
        }
        foreach($priceInfo as $pro_price) {
            $priceMaps[$pro_price->project_id] = $pro_price->price;
        }
        //我的车保养记录
        $maintainCarInfo = $this->connection->table('maintain_car_record')->where('mycar_id', $mycar_id)->orderBy('created_at', 'desc')->first();
        $fixKilometers = 0; //根据之前的保养里程，修正保养里程
        $current_not_maintain_flag = false; //不提醒当次保养过的数据
        if (!is_null($maintainCarInfo) && $maintainCarInfo->kilometers <= $kilometers) {
            $maintainKilometers = $maintainCarInfo->kilometers;
            $fixKilometers = $this->getFixKilometersByLastMaintainData($maintainKilometers, $detailInfo);
            if ($kilometers <= $maintainKilometers + $this->deviationKilometers) {
                $current_not_maintain_flag = true;
            }
        }
        //算出保养区间
        if ($kilometers <= $detailInfo->first_maintain_kilometers + $fixKilometers + $this->deviationKilometers) {
            $section = 0;
        } elseif ($kilometers <= $detailInfo->second_maintain_kilometers + $fixKilometers + $this->deviationKilometers) {
            $section = 1;
        } else {
            $section = ceil(($kilometers - ($detailInfo->second_maintain_kilometers + $fixKilometers + $this->deviationKilometers))
                    / $detailInfo->maintain_interval_kilometers) + 1;
        }
        if ($current_not_maintain_flag) {
            $section++;
        }
        //距离下次保养里程
        if ($section == 0) {
            $next_kilometers = $detailInfo->first_maintain_kilometers;
            $next_next_kilometers = $detailInfo->second_maintain_kilometers;
        } else {
            $next_kilometers = $detailInfo->second_maintain_kilometers + $detailInfo->maintain_interval_kilometers * ($section - 1);
            $next_next_kilometers = $detailInfo->second_maintain_kilometers + $detailInfo->maintain_interval_kilometers * $section;
        }

        $next['kilometers'] = $next_kilometers + $fixKilometers - $kilometers;
        $next['day'] = max(0, floor($next['kilometers'] / ($this->kilometersPerSection / 180)));
        if(abs($next['kilometers']) > $this->deviationKilometers) {
            $next['need_maintain_immediately'] = false;
        } else {
            $next['need_maintain_immediately'] = true;
        }
        $next['maintain_interval_kilometers'] = $detailInfo->maintain_interval_kilometers;
        //保养数据支持的最高里程数 10000为保留值
        $allSections = DetailSection::where('detail_id', $maintain_id)->get();
        $next['max_kilometers'] = (count($allSections)-2)*$detailInfo->maintain_interval_kilometers
            + $detailInfo->second_maintain_kilometers - 10000;

        $sectionInfo = DetailSection::where('detail_id', $maintain_id)->where('section', $section)->first();
        if (!is_null($sectionInfo)) {
            //需要保养项目
            $price = 0;
            $projects_ids = explode(',', $sectionInfo->project_ids);
            foreach($projects_ids as $projectId) {
                $arr['id'] = $projectId;
                $arr['name'] = $projectMaps[$projectId];
                if(isset($priceMaps[$projectId])) {
                    $price += $priceMaps[$projectId];
                }
                $projects[] = $arr;
            }
            $title = '推荐保养计划: '. $next_kilometers. '公里保养';
            $maintainData[0] = ['title'=>$title, 'price'=>$price, 'projects'=>$projects];
            //下次需要保养项目
            $another_sectionInfo = DetailSection::where('detail_id', $maintain_id)->where('section', $section+1)->first();
            if (!is_null($another_sectionInfo)) {
                $another_price = 0;
                $another_projects_ids = explode(',', $another_sectionInfo->project_ids);
                if(!empty($another_projects_ids)) {
                    foreach($another_projects_ids as $projectId) {
                        $arr['id'] = $projectId;
                        $arr['name'] = $projectMaps[$projectId];
                        if(isset($priceMaps[$projectId])) {
                            $another_price += $priceMaps[$projectId];
                        }
                        $another_projects[] = $arr;
                    }
                }
                $another_title = '保养计划: '. $next_next_kilometers. '公里保养';
                $maintainData[1] = ['title'=>$another_title, 'price'=>$another_price, 'projects'=>$another_projects];
            }
        } else {
            echo json_encode(['status'=>"false", 'msg'=>'无法得到保养项目信息', 'data'=>(object)null]);die;
        }

        echo json_encode(['status'=>"success", 'msg'=>'返回成功', 'data'=>['next'=>$next, 'maintain'=>$maintainData]]);
    }

    public function getFixKilometersByLastMaintainData($kilometers, $detailInfo)
    {
        if($kilometers <= $detailInfo->first_maintain_kilometers) {

            return $kilometers - $detailInfo->first_maintain_kilometers;
        } elseif($kilometers <= $detailInfo->second_maintain_kilometers) {
            $low_kilometers = $detailInfo->first_maintain_kilometers;
            $high_kilometers = $detailInfo->second_maintain_kilometers;
        } else {
            $section = ceil(($kilometers-$detailInfo->second_maintain_kilometers)/$detailInfo->maintain_interval_kilometers) + 1;
            $low_kilometers = $detailInfo->second_maintain_kilometers + $detailInfo->maintain_interval_kilometers*($section-2);
            $high_kilometers = $low_kilometers + $detailInfo->maintain_interval_kilometers;
        }
//        dd($low_kilometers,$high_kilometers);
        if (abs($kilometers-$low_kilometers) < abs($kilometers-$high_kilometers)) {

            return $kilometers - $low_kilometers;
        }

        return $kilometers - $high_kilometers;

    }

    public function makeAppointment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mycar_id' => 'required',
            'title' => 'required',
            'price' => 'required',
            'projects' => 'required',
        ]);
        if ($validator->fails()) {
            echo json_encode(['status'=>'false', 'msg'=>$validator->errors(), 'data'=>(object)null]);die;
        }
        $mycar_id = Input::get('mycar_id');
        $title = Input::get('title');
        $price = Input::get('price');
        $projects = Input::get('projects');
        $result = $this->connection->table('maintain_appointment')->insert([
            'mycar_id'=>$mycar_id,
            'title'=>$title,
            'price'=>$price,
            'projects'=>$projects,
            'created_at'=>date('Y-m-d H:i:s')
        ]);

        if($result) {
            echo json_encode(['status'=>"success"]);
        } else {
            echo json_encode(['status'=>"failure"]);
        }
    }

    public function addRecord(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mycar_id' => 'required|integer',
            'kilometers' => 'required|integer',
        ]);
        if ($validator->fails()) {
            echo json_encode(['status'=>'false', 'msg'=>$validator->errors()]);die;
        }
        $insertData['mycar_id'] = Input::get('mycar_id');
        $insertData['kilometers'] = Input::get('kilometers');
        $insertData['created_at'] = date('Y-m-d H:i:s');

        $result = $this->connection->table('maintain_car_record')->insert($insertData);
        if ($result) {
            $status = 'success';
            //如果已保养的里程数值大于现在的总里程，则把总里程修正到此数值
            $mileage = $this->connection->table('my_car')->where('id', $insertData['mycar_id'])->pluck('mileage');
            $kilometers = round($insertData['kilometers']/10000, 2);
            if ($kilometers > $mileage) {
                $this->connection->table('my_car')->where('id', $insertData['mycar_id'])->update(
                    ['mileage' => $kilometers,
                        'mileage_update_time' => date('Y-m-d H:i:s')]
                );
            }
        } else {
            $status = 'false';
        }
        echo json_encode(['status'=>$status]);
    }

}