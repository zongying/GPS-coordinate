<?php
class GPS {
    private $PI = 3.14159265358979324;
    private $x_pi = 0;
 
    public function __construct()
    {
        $this->x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    }
    //WGS-84 to GCJ-02
    public function gcj_encrypt($wgsLat, $wgsLon) {
        if ($this->outOfChina($wgsLat, $wgsLon))
            return array('lat' => $wgsLat, 'lon' => $wgsLon);
 
        $d = $this->delta($wgsLat, $wgsLon);
        return array('lat' => $wgsLat + $d['lat'],'lon' => $wgsLon + $d['lon']);
    }
    //GCJ-02 to WGS-84
    public function gcj_decrypt($gcjLat, $gcjLon) {
        if ($this->outOfChina($gcjLat, $gcjLon))
            return array('lat' => $gcjLat, 'lon' => $gcjLon);
         
        $d = $this->delta($gcjLat, $gcjLon);
        return array('lat' => $gcjLat - $d['lat'], 'lon' => $gcjLon - $d['lon']);
    }
    //GCJ-02 to WGS-84 exactly
    public function gcj_decrypt_exact($gcjLat, $gcjLon) {
        $initDelta = 0.01;
        $threshold = 0.000000001;
        $dLat = $initDelta; $dLon = $initDelta;
        $mLat = $gcjLat - $dLat; $mLon = $gcjLon - $dLon;
        $pLat = $gcjLat + $dLat; $pLon = $gcjLon + $dLon;
        $wgsLat = 0; $wgsLon = 0; $i = 0;
        while (TRUE) {
            $wgsLat = ($mLat + $pLat) / 2;
            $wgsLon = ($mLon + $pLon) / 2;
            $tmp = $this->gcj_encrypt($wgsLat, $wgsLon);
            $dLat = $tmp['lat'] - $gcjLat;
            $dLon = $tmp['lon'] - $gcjLon;
            if ((abs($dLat) < $threshold) && (abs($dLon) < $threshold))
                break;
 
            if ($dLat > 0) $pLat = $wgsLat; else $mLat = $wgsLat;
            if ($dLon > 0) $pLon = $wgsLon; else $mLon = $wgsLon;
 
            if (++$i > 10000) break;
        }
        //console.log(i);
        return array('lat' => $wgsLat, 'lon'=> $wgsLon);
    }
    //GCJ-02 to BD-09
    public function bd_encrypt($gcjLat, $gcjLon) {
        $x = $gcjLon; $y = $gcjLat;  
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $this->x_pi);  
        $theta = atan2($y, $x) + 0.000003 * cos($x * $this->x_pi);  
        $bdLon = $z * cos($theta) + 0.0065;  
        $bdLat = $z * sin($theta) + 0.006; 
        return array('lat' => $bdLat,'lon' => $bdLon);
    }
    //BD-09 to GCJ-02
    public function bd_decrypt($bdLat, $bdLon)
    {
        $x = $bdLon - 0.0065; $y = $bdLat - 0.006;  
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $this->x_pi);  
        $theta = atan2($y, $x) - 0.000003 * cos($x * $this->x_pi);  
        $gcjLon = $z * cos($theta);  
        $gcjLat = $z * sin($theta);
        return array('lat' => $gcjLat, 'lon' => $gcjLon);
    }
    //WGS-84 to Web mercator
    //$mercatorLat -> y $mercatorLon -> x
    public function mercator_encrypt($wgsLat, $wgsLon)
    {
        $x = $wgsLon * 20037508.34 / 180.;
        $y = log(tan((90. + $wgsLat) * $this->PI / 360.)) / ($this->PI / 180.);
        $y = $y * 20037508.34 / 180.;
        return array('lat' => $y, 'lon' => $x);
        /*
        if ((abs($wgsLon) > 180 || abs($wgsLat) > 90))
            return NULL;
        $x = 6378137.0 * $wgsLon * 0.017453292519943295;
        $a = $wgsLat * 0.017453292519943295;
        $y = 3189068.5 * log((1.0 + sin($a)) / (1.0 - sin($a)));
        return array('lat' => $y, 'lon' => $x);
        //*/
    }
    // Web mercator to WGS-84
    // $mercatorLat -> y $mercatorLon -> x
    public function mercator_decrypt($mercatorLat, $mercatorLon)
    {
        $x = $mercatorLon / 20037508.34 * 180.;
        $y = $mercatorLat / 20037508.34 * 180.;
        $y = 180 / $this->PI * (2 * atan(exp($y * $this->PI / 180.)) - $this->PI / 2);
        return array('lat' => $y, 'lon' => $x);
        /*
        if (abs($mercatorLon) < 180 && abs($mercatorLat) < 90)
            return NULL;
        if ((abs($mercatorLon) > 20037508.3427892) || (abs($mercatorLat) > 20037508.3427892))
            return NULL;
        $a = $mercatorLon / 6378137.0 * 57.295779513082323;
        $x = $a - (floor((($a + 180.0) / 360.0)) * 360.0);
        $y = (1.5707963267948966 - (2.0 * atan(exp((-1.0 * $mercatorLat) / 6378137.0)))) * 57.295779513082323;
        return array('lat' => $y, 'lon' => $x);
        //*/
    }
    // two point's distance
    public function distance($latA, $lonA, $latB, $lonB)
    {
        $earthR = 6371000.;
        $x = cos($latA * $this->PI / 180.) * cos($latB * $this->PI / 180.) * cos(($lonA - $lonB) * $this->PI / 180);
        $y = sin($latA * $this->PI / 180.) * sin($latB * $this->PI / 180.);
        $s = $x + $y;
        if ($s > 1) $s = 1;
        if ($s < -1) $s = -1;
        $alpha = acos($s);
        $distance = $alpha * $earthR;
        return $distance;
    }
 
    private function delta($lat, $lon)
    {
        // Krasovsky 1940
        //
        // a = 6378245.0, 1/f = 298.3
        // b = a * (1 - f)
        // ee = (a^2 - b^2) / a^2;
        $a = 6378245.0;//  a: 卫星椭球坐标投影到平面地图坐标系的投影因子。
        $ee = 0.00669342162296594323;//  ee: 椭球的偏心率。
        $dLat = $this->transformLat($lon - 105.0, $lat - 35.0);
        $dLon = $this->transformLon($lon - 105.0, $lat - 35.0);
        $radLat = $lat / 180.0 * $this->PI;
        $magic = sin($radLat);
        $magic = 1 - $ee * $magic * $magic;
        $sqrtMagic = sqrt($magic);
        $dLat = ($dLat * 180.0) / (($a * (1 - $ee)) / ($magic * $sqrtMagic) * $this->PI);
        $dLon = ($dLon * 180.0) / ($a / $sqrtMagic * cos($radLat) * $this->PI);
        return array('lat' => $dLat, 'lon' => $dLon);
    }
 
    private function outOfChina($lat, $lon)
    {
        if ($lon < 72.004 || $lon > 137.8347)
            return TRUE;
        if ($lat < 0.8293 || $lat > 55.8271)
            return TRUE;
        return FALSE;
    }
 
    private function transformLat($x, $y) {
        $ret = -100.0 + 2.0 * $x + 3.0 * $y + 0.2 * $y * $y + 0.1 * $x * $y + 0.2 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($y * $this->PI) + 40.0 * sin($y / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($y / 12.0 * $this->PI) + 320 * sin($y * $this->PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
 
    private function transformLon($x, $y) {
        $ret = 300.0 + $x + 2.0 * $y + 0.1 * $x * $x + 0.1 * $x * $y + 0.1 * sqrt(abs($x));
        $ret += (20.0 * sin(6.0 * $x * $this->PI) + 20.0 * sin(2.0 * $x * $this->PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($x * $this->PI) + 40.0 * sin($x / 3.0 * $this->PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($x / 12.0 * $this->PI) + 300.0 * sin($x / 30.0 * $this->PI)) * 2.0 / 3.0;
        return $ret;
    }
}