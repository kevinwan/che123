<?php namespace App\Model\Valuation;

use Request;
use DB;

class Che300 extends ValuationModel
{

    public function valuation($params)
    {
        $brand = $params['brand'];
        $series = $params['series'];
        $model = $params['model'];
        $date = $params['date'];
        $city = $params['city'];
        $kilometer = $params['kilometer'];

        $series_id = 0;$model_id = 0;$province_id = 0;$city_id = 0;$url = '';
        $brand_id = DB::table('che300_brands')->where('name', $brand)->pluck('id');
        if(!is_null($brand_id)) {
            $series_id = $this->getSeriesId($brand_id, $series, $brand);
            if(!is_null($series_id)) {
                $model_id = $this->getModelId($series_id, $model);
                list($province_id, $city_id) = $this->getProvinceCityId($city);
                if(!is_null($model_id) && !is_null($province_id) && !is_null($city_id)) {
                    $url = "http://www.che300.com/pinggu/v{$province_id}c{$city_id}m{$model_id}r{$date}g{$kilometer}";
                    $result = $this->getDealPrice($url);
                    if(!is_null($result)) {
                        $result['url'] = $url;

                        return $result;
                    }
                }
            }
        }

        return [
            'normal_dealer_price' => '',
            'low_dealer_price' => '',
            'high_dealer_price' => '',
            'url' => '',
            'msg' => "brand_id->{$brand_id}|series_id->{$series_id}|model_id->{$model_id}|province_id->{$province_id}|city_id->{$city_id}|url->{$url}"
        ];
    }

    /**
     * query series id
     * @param $brand_id
     * @param $series
     * @param $brand
     * @return null
     */
    private  function getSeriesId($brand_id, $series, $brand)
    {
        $url = "http://7xklo4.com1.z0.glb.clouddn.com/series/series_brand{$brand_id}.json?v=26";
        $series_list = json_decode($this->snoopyFetch($url), true);
        if(!empty($series_list)) {
            foreach ($series_list as $one_series) {
                if($one_series['series_name'] == $series
                    || $brand. $one_series['series_name'] == $series
                    || $one_series['series_name'] == $brand. $series
                ) {

                    return $one_series['series_id'];
                }
            }
        }

        return null;
    }

    /**
     * query model id
     * @param $series_id
     * @param $model
     * @return null
     */
    private  function getModelId($series_id, $model)
    {
        $url = "http://7xklo4.com1.z0.glb.clouddn.com/model/model_series{$series_id}.json?v=26";
        $model_list = json_decode($this->snoopyFetch($url), true);
        if(!empty($model_list)) {
            foreach ($model_list as $one_model) {
                if($one_model['model_name'] == $model || $this->modelNameSuit($model, $one_model['model_name'])) {

                    return $one_model['model_id'];
                }
            }
        }

        return null;
    }

    /**
     * model name if suit or not
     * @param $gpjModel
     * @param $che300Model
     * @return bool
     */
    private function modelNameSuit($gpjModel, $che300Model)
    {
        $gpjModel = parent::modelNameAdjustment($gpjModel);
        $che300Model = parent::modelNameAdjustment($che300Model);

        $gpjModelElements = array_values(array_filter(explode(' ', strtoupper($gpjModel))));
        $che300ModelElements = array_values(array_filter(explode(' ', strtoupper($che300Model))));

        $intersect = array_values(array_intersect($gpjModelElements, $che300ModelElements));
        if($intersect == $gpjModelElements || $intersect == $che300ModelElements) {

            return true;
        }

        return false;
    }

    
    /**
     * query che300 province and city id
     * @param $city
     * @return array
     */
    private function getProvinceCityId($city)
    {
        $url = "http://meta.che300.com/location/all_city.json";
        $city_array = json_decode($this->snoopyFetch($url), true);
        if(!empty($city_array)) {
            foreach ($city_array as $one_city) {
                if($one_city['city_name'] == $city) {

                    return [$one_city['prov_id'], $one_city['city_id']];
                }
            }
        }

        return [];
    }

    /**
     * grab the deal price by page
     * @param $url$che300ModelElements
     * @return array
     */
    private function getDealPrice($url)
    {
        $results = $this->snoopyFetch($url);
        preg_match('/class="dealer_price"[\s\S]+?<p>￥(.*?)<\/p>/', $results, $normal_dealer_price_match);
        if(isset($normal_dealer_price_match[1])) {
            $normal_dealer_price = $normal_dealer_price_match[1];

            preg_match('/class="car_price_num01">(.*?)<\/span>/', $results, $low_dealer_price_match);
            if(isset($low_dealer_price_match[1])) {
                $low_dealer_price = $low_dealer_price_match[1];

                preg_match('/class="car_price_num02">(.*?)<\/span>/', $results, $high_dealer_price_match);
                if(isset($high_dealer_price_match[1])) {
                    $high_dealer_price = $high_dealer_price_match[1];

                    return compact('normal_dealer_price', 'low_dealer_price', 'high_dealer_price');
                }
            }
        }

        return null;
    }

}
