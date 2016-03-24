<?php namespace App\Model\Valuation;

use Illuminate\Database\Eloquent\Model;
use Snoopy\Snoopy;

class ValuationModel extends Model {

    protected function modelNameAdjustment($modelName){
        $patterns = [
            '/(手动)(\S)/i',
            '/(自动)(\S)/i',
            '/(手自一体)(\S)/i',
            '/(版)(\S)/i',
            '/(型)(\S)/i',
            '/(\d[LT])([^\/\s])/i',
            //去 ()
            '/\(.*\)/i',
            //去 +
            '/\+.*/i',
            //1.5 -> 1.5L
            '/(\d\.\d)([^TL])/i',
        ];
        $replacements = [
            "\$1 \$2",
            "\$1 \$2",
            "\$1 \$2",
            "\$1 \$2",
            "\$1 \$2",
            "\$1 \$2",
            "",
            "",
            "\$1L \$2",
        ];
        $modelName = preg_replace($patterns, $replacements, $modelName);
//        var_dump(explode(' ', $modelName));
        return $modelName;
	}

    protected  function snoopySubmit($url, $data, $is_proxy=false){
        $snoopy = new Snoopy();
        $snoopy = $this->_proxy($snoopy, $is_proxy);
        $snoopy->submit($url, $data);

        return $snoopy->results;
    }

    protected function snoopyFetch($url, $is_proxy=false){
        $snoopy = new Snoopy();
        $snoopy = $this->_proxy($snoopy, $is_proxy);
        $snoopy->fetch($url);

        return $snoopy->results;
    }

    private function _proxy($snoopy, $is_proxy){
        /*if ($is_proxy)
        {
            $snoopy->proxy_host = "us-il.proxymesh.com";
            $snoopy->proxy_port = "31280";
            $snoopy->proxy_user = "gaoge";
            $snoopy->proxy_pass = "Sw21qazX";
        }*/

        return $snoopy;
    }

}
