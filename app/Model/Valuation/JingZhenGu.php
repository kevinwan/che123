<?php namespace App\Model\Valuation;

use Request;
use DB;

class JingZhenGu extends ValuationModel
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
        $brand_id = $this->getBrandId($brand);
        if(!is_null($brand_id)) {
            $series_id = $this->getSeriesId($brand_id, $series, $brand);
            if(!is_null($series_id)) {
                $model_id = $this->getModelId($series_id, $model);
                $province = DB::table('cities')->where('name', $city)->pluck('province');
                $province_id = $this->getProvinceId($province);
                if(!is_null($province_id) && !is_null($model_id)) {
                    $city_id = $this->getCityId($city, $province_id);
                    if(!is_null($city_id)) {
                        $url = "http://www.jingzhengu.com/gujia/style-{$model_id}/".($kilometer*10000)."-{$date}-1/{$province_id}/{$city_id}";
                        $result = $this->getDealPrice($url);
                        if(!is_null($result)) {
                            $result['url'] = $url;

                            return $result;
                        }
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

    private function getBrandId($brand){
        $url = "http://www.jingzhengu.com/Resources/Ajax/defaulthandler.ashx?op=getAppointYearBeforeMake&year=2015";
        $result = json_decode($this->snoopyFetch($url), true);
        $brands_list = $result['MakeList'];
        if(!empty($brands_list)) {
            foreach ($brands_list as $one_brand) {
                if($one_brand['Text'] == $brand) {

                    return $one_brand['Value'];
                }
            }
        }

        return null;
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
        $url = "http://www.jingzhengu.com/Resources/Ajax/defaulthandler.ashx?op=getAppointYearBeforeModel&makeid={$brand_id}&year=2015";
        $result = json_decode($this->snoopyFetch($url), true);
        $series_list = $result['ModelList'];
        if(!empty($series_list)) {
            foreach ($series_list as $one_series) {
                if($one_series['Text'] == $series
                    || $brand. $one_series['Text'] == $series
                    || $one_series['Text'] == $brand. $series
                ) {

                    return $one_series['Value'];
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
        $url = "http://www.jingzhengu.com/Resources/Ajax/defaulthandler.ashx?op=getAppointYearBeforeStyle&modelid={$series_id}&year=2015";
        $result = json_decode($this->snoopyFetch($url), true);
        $model_list = $result['StyleList'];
        if(!empty($model_list)) {
            foreach ($model_list as $one_model) {
                $yearModel = str_replace(" ", "", $one_model['GroupName']);
                if($one_model['Text'] == $model || $this->modelNameSuit($model, $one_model['Text'], $yearModel)) {

                    return $one_model['Value'];
                }
            }
        }

        return null;
    }

    /**
     * model name if suit or not
     * @param $gpjModelName
     * @param $otherModelName
     * @param $otherYearModel
     * @return bool
     */
    private function modelNameSuit($gpjModelName, $otherModelName, $otherYearModel)
    {
        $yearModel = mb_substr($gpjModelName, 0, 5);
        $gpjModelName = mb_substr($gpjModelName, 6);

        $gpjModelName = parent::modelNameAdjustment($gpjModelName);
        $otherModelName = parent::modelNameAdjustment($otherModelName);

        $gpjModelArray = array_filter(explode(' ', strtoupper($gpjModelName)));
        $otherModelArray = array_filter(explode(' ', strtoupper($otherModelName)));

        $intersect = array_values(array_intersect($gpjModelArray, $otherModelArray));
        if(($intersect == $gpjModelArray || $intersect == $otherModelArray) && $yearModel == $otherYearModel) {

            return true;
        }

        return false;
    }

    /**
     * query  province id
     * @param $province
     * @return array
     */
    private function getProvinceId($province)
    {
        $province_url = "http://www.jingzhengu.com/Resources/ajax/getProvCity.ashx?op=1";
        $province_array = explode(',', str_replace("'", "", substr($this->snoopyFetch($province_url), 1, -1)));
        foreach ($province_array as $k => $one_province) {
            if($one_province == $province) {

                return $province_array[$k+1];
            }
        }

        return null;
    }

    private function getCityId($city, $province_id){
        $city_url = "http://www.jingzhengu.com/Resources/ajax/cityjs.ashx?ProvinceId={$province_id}&type=2";
        $result = $this->snoopyFetch($city_url);
        $city_array = explode(',', substr($result, 15, -1));
        foreach ($city_array as $k => $one_city) {
            if($one_city == $city) {

                return $city_array[$k+1];
            }
        }

        return null;
    }

    /**
     * grab the deal price by page
     * @param $url
     * @return array
     */
    private function getDealPrice($url)
    {
        $results = $this->snoopyfetch($url);
        preg_match('/class="zcsscj dvc2bPrice dvpdetail"[\s\S]+?<em>(.*?)<\/em>[\s\S]+?<li class="zactive">￥<em>(.*?)<\/em>[\s\S]+?<em>(.*?)<\/em>/', $results, $dealer_price_match);
        if(isset($dealer_price_match[1]) && isset($dealer_price_match[2]) && isset($dealer_price_match[3])) {
            $high_dealer_price = $dealer_price_match[1].'万';
            $normal_dealer_price = $dealer_price_match[2].'万';
            $low_dealer_price = $dealer_price_match[3].'万';

            return compact('high_dealer_price', 'normal_dealer_price', 'low_dealer_price');
        }

        return null;
    }

}
