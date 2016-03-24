<?php namespace App\Http\Controllers;

require __DIR__.'/../../library/Snoopy.class.php';

use App\Brand;
use App\Detail;
use App\DetailSection;
use App\library\Snoopy;
use App\Project;
use App\CarModel;
use App\DetailProjectPrice;
use DB;

class ScriptController extends Controller {

    public function getAutoHomeDelete() {
        set_time_limit(0);
        $between = [1,3000];
        $data = CarModel::whereBetween('maintain_id', $between)->lists('maintain_id');

        Detail::whereBetween('id',$between)->whereNotIn('id', $data)->delete();
        DetailProjectPrice::whereBetween('detail_id',$between)->whereNotIn('detail_id', $data)->delete();
        DetailSection::whereBetween('detail_id',$between)->whereNotIn('detail_id', $data)->delete();
    }

    public function getAutoHomeDetail()
    {
        set_time_limit(0);
        $snoopy = new Snoopy;
        $datas = CarModel::whereBetween('id', [6500,24000])->where('maintain_id', 0)->orderBy('id', 'asc')->get();
        $hasMaintains = array(); //适配车型数组
        foreach($datas as $data) {
            echo '------data_id------'.$data->id.'-START-';
            if(!in_array($data->id, $hasMaintains)) {
                $url = $data->url;
                $snoopy->fetch($url);
                $snoopy->results = mb_convert_encoding($snoopy->results, 'UTF-8', 'GBK');
                //首保
                preg_match('/首保：(\d+?)公里\/(\d+?)个月\s二保：(\d+?)公里\/(\d+?)个月\s+?间隔：(\d+?)公里\/(\d+?)个月/', $snoopy->results, $match);
                $maintain = new Detail();
                if(isset($match[1])) $maintain->first_maintain_kilometers = $match[1];
                if(isset($match[2])) $maintain->first_maintain_month = $match[2];
                if(isset($match[3])) $maintain->second_maintain_kilometers = $match[3];
                if(isset($match[4])) $maintain->second_maintain_month = $match[4];
                if(isset($match[5])) $maintain->maintain_interval_kilometers = $match[5];
                if(isset($match[6]))  $maintain->maintain_interval_month = $match[6];

                if($maintain->save()){
                    preg_match('/<tr><td><strong>保养项目\/里程[\s\S]+（元）<\/td>/', $snoopy->results, $match);
                    if(!isset($match[0])) {
                        continue;
                    } else
                        $maintain_results = $match[0];
                    //project
                    preg_match_all('/<tr><td>(.*?)<\/td>/', $maintain_results, $match);
                    $projects = $match[1];
                    //除去“保养项目/里程，前制动器，后制动器，总计”
                    array_shift($projects);
                    array_pop($projects);
                    array_pop($projects);
                    array_pop($projects);
                    $project_row_map = array_flip($projects);
                    //项目，价格信息入库
                    foreach($projects as $project) {
                        $pro = Project::firstOrCreate(['name'=>$project]);
                        preg_match('/<tr><td>'.$project.'<\/td>[\s]+<td>(\d*)<\/td>/', $maintain_results, $match);
                        if(isset($match[1]) && $match[1]) {
                            DetailProjectPrice::firstOrCreate(['detail_id'=>$maintain->id, 'project_id'=>$pro->id, 'price'=>$match[1]]);
                        }
                        $row_project_id_map[$project_row_map[$project]] = $pro['id'];
                    }
                    //列数
                    preg_match('/保养项目\/里程[\s\S]+?<tr><td>/', $maintain_results, $match);
                    preg_match_all('/(class="t_H3_item_bg")/', $match[0], $match);
                    $column = count($match[0]);
                    //行数
                    preg_match_all('/(<tr><td>)/', $maintain_results, $match);
                    $row = count($match[0])-4;
                    //section矩阵
                    for($c=0; $c<$column; $c++) {
                        $section = array();
                        for($r=0; $r<$row; $r++) {
                            $id = "c{$c}r{$r}";
                            preg_match('/'.$id.'"[\s]+?>(.*?)<\/td>/', $maintain_results, $match);
                            if(isset($match[1]) && !empty($match[1])) {
                                $section[] = $row_project_id_map[$r];
                            }
                        }
                        if(!empty($section)) {
                            $project_ids = implode(',', $section);
                            DetailSection::firstOrCreate(['detail_id'=>$maintain->id, 'section'=>$c, 'project_ids'=>$project_ids]);
                        }
                    }
                    //保存
                    $data->maintain_id = $maintain->id;
                    $data->save();
                    preg_match('/id="specBox"[\s\S]+?<div/', $snoopy->results, $match);
                    if(isset($match[0]) && !empty($match[0])){
                        $adaptMatch = $match[0];
                        preg_match_all('/<dd>(.+?)<\/dd>/', $adaptMatch, $match);
                        if(isset($match[1]) && !empty($match[1])){
                            //所有适用的车型
                            $model = CarModel::where('type_id', $data->type_id)->whereIn('name', $match[1]);echo 1;
                            $model->update(['maintain_id'=>$maintain->id]);
                            $ids = $model->lists('id');
//                            print_r($ids);die;
                            if(isset($ids) && !empty($ids)) {
                                $hasMaintains = empty($hasMaintains) ? $ids : array_merge($hasMaintains, $ids);
//                                print_r($hasMaintains);die;
                            }
                        }
                    }
                    echo 'new maintain_id-----'.$maintain->id.'-';
                }

            }
            echo '-------<br>';
        }
    }


    //根据品牌抓取详情页链接
    public function getAutoHomeUrls()
    {
        set_time_limit(0);
        $snoopy = new Snoopy;
        $brands = Brand::all();

        foreach($brands as $brand) {
            $n = 1; //起始页数
            $all_urls = [];
            while($n > 0) {
                $url = "http://car.autohome.com.cn/baoyang/list_{$n}_{$brand->autohome_brand_id}_0_0_0.html"; //构造抓取URL
                $snoopy->fetch($url);
                preg_match('/class="nodata_text"/', $snoopy->results, $match);
                if(isset($match[0])) $n = -1;
                else {
                    preg_match_all('/class="colorGray"[\s\S]+?<\/span>/', $snoopy->results, $match);
                    if(isset($match[0]) && !empty($match[0])) {
                        $engines_match = $match[0];
                        foreach($engines_match as $prg_str) {
                            preg_match_all('/href="(.+?)"/', $prg_str, $match);
                            $all_urls = !empty($all_urls) ? array_merge($all_urls, $match[1]) : $match[1];
                        }
                    }
                    $n++;
                }
            }
            if(!empty($all_urls))
                foreach($all_urls as $url) {
                    Detail::firstOrCreate(['autohome_url'=>$url, 'brand_id'=>$brand->id]);
                }
            echo $brand->name.'-end-<br>';
        }
    }

    //分类
    public function getAutoHomeCategories() {
        set_time_limit(0);
        $snoopy = new Snoopy;
        $url = "http://car.autohome.com.cn/baoyang/list_1_33_0_0_0.html"; //构造抓取URL
        $snoopy->fetch($url);
        preg_match('/id="frame_tree"[\s\S]+?<ul>[\s\S]+?<\/ul>/', $snoopy->results, $match);
        print_r($match);die;
        $snoopy->results = $match[0];
        preg_match_all('/id="brand_.+?>(\w+)\(/', $snoopy->results, $match);

        if(isset($match[0]) && !empty($match[0])) {
            $engines_match = $match[0];
            foreach($engines_match as $prg_str) {
                preg_match_all('/href="(.+?)"/', $prg_str, $match);
                $all_urls = !empty($all_urls) ? array_merge($all_urls, $match[1]) : $match[1];
            }
        }

        if(!empty($all_urls))
            foreach($all_urls as $url) {
                Detail::firstOrCreate(['autohome_url'=>$url, 'brand_id'=>$brand->id]);
            }
        echo $brand->name.'-end-<br>';
    }

    public function getTest() {

        /*$result = DB::table('autohome_types')->get();
        foreach($result as $re) {
            $years = explode(',', $re->year);
            foreach($years as $year) {
                $id = $re->id.$year;
                if(isset($AutoHome_Baoyang_Spec[$id])){
                    $models = explode(',', $AutoHome_Baoyang_Spec[$id]);
                }
            }
            if(isset($AutoHome_Baoyang_Series[$re->id])) {
                $types = explode(',', $AutoHome_Baoyang_Series[$re->id]);
                foreach($types as $i=>$type) {
                    if($i%2==0) {
                        $names = explode(' ',$types[$i+1]);
                        $insert_array[] = ['id'=>$type, 'name'=>$names[1], 'brand_id'=>$re->id];
                    }
                }
            }
        }
        DB::table('autohome_types')->insert($insert_array);*/

    }

}
