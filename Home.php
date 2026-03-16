<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Home extends WebController {
    
    protected $_ganzhiLib;
    protected $_ganzhiData;
    protected $_palaceResult = 
    [
        'calc_datetime'     =>  '',
        'time_zone'         =>  'hong_kong',
        'hk_datetime'       =>  '',
        'lunar_shengxiao'   =>  '',
        'lunar_year'        =>  0,
        'lunar_month'       =>  0,
        'lunar_day'         =>  0,
        'lunar_year_chinese'    => '',
        'lunar_month_chinese'   => '',
        'lunar_day_chinese'     => '',
        'ganzhi_year'       =>  '',
        'ganzhi_month'      =>  '',
        'ganzhi_day'        =>  '',
        'ganzhi_hour'       =>  '',
        
        'dun_index'         =>  0,   // 1.陽 or 2.陰
        'dun_number'        =>  0,   // 局數
        'header'            =>  '',  // 旬首
        'zhi_fu'            =>  '',  // 值符
        'zhi_shi'           =>  '',  // 值使宮位,
        'zhi_fu_index'      =>  '',  // 值符宮位
        'zhi_shi_index'     =>  '',
        
        // 九宮格
        'grid'              =>
        [
            4 => ['index' => 4, 'name' => '巽', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            9 => ['index' => 9, 'name' => '離', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            2 => ['index' => 2, 'name' => '坤', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],

            3 => ['index' => 3, 'name' => '震', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            5 => ['index' => 5, 'name' => '中', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            7 => ['index' => 7, 'name' => '兌', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],

            8 => ['index' => 8, 'name' => '艮', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            1 => ['index' => 1, 'name' => '坎', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => ''],
            6 => ['index' => 6, 'name' => '乾', 'shen' => '', 'star' => '', 'gate' => '', 'tian' => '', 'earth' => '']
        ],
        
        'kongwang'          =>  [],
        'yima'              =>  []
    ];

    // 1. 陽： 冬至 -> 夏至
    // 2. 陰： 夏至 -> 冬至
    protected $_yyDunIndex = 0;
    protected $_yyDunNumber = 0;
    
    // 12 地支
    protected $_twelveDiZhi = 
    [
        1   =>  '子',   // 23:00 - 01:00	
        2   =>  '丑',   // 01:00 - 03:00
        3   =>  '寅',   // 03:00 - 05:00
        4   =>  '卯',   // 05:00 - 07:00
        5   =>  '辰',   // 07:00 - 09:00
        6   =>  '巳',   // 09:00 - 11:00

        7   =>  '午',   // 11:00 - 13:00
        8   =>  '未',   // 13:00 - 15:00
        9   =>  '申',   // 15:00 - 17:00
        10  =>  '酉',   // 17:00 - 19:00
        11  =>  '戌',   // 19:00 - 21:00
        12  =>  '亥'    // 21:00 - 23:00
    ];
    
    // 6 奇 + 3 儀
    protected $_sixYiThreeQi = [
        1   =>  '戊', 
        2   =>  '己', 
        3   =>  '庚', 
        4   =>  '辛', 
        5   =>  '壬', 
        6   =>  '癸',
        7   =>  '丁',
        8   =>  '丙', 
        9   =>  '乙'
    ];
    
    // 順 + 逆時針
    protected $_ascPattern = [1, 2, 3, 4, 5, 6, 7, 8, 9];
    protected $_descPattern = [9, 8, 7, 6, 5, 4, 3, 2, 1];
    
    // 六十甲子
    /*
    特殊説明：
    - 甲子時用戊
    - 甲戌時用己
    - 甲申時用庚
    - 甲午時用辛
    - 甲辰時用壬
    - 甲寅時用癸
    */
    protected $_sixtyJiazi = 
    [
        '甲子戊' => ['甲子', '乙丑', '丙寅', '丁卯', '戊辰', '己巳', '庚午', '辛未', '壬申', '癸酉'],
        '甲戌己' => ['甲戌', '乙亥', '丙子', '丁丑', '戊寅', '己卯', '庚辰', '辛巳', '壬午', '癸未'],
        '甲申庚' => ['甲申', '乙酉', '丙戌', '丁亥', '戊子', '己丑', '庚寅', '辛卯', '壬辰', '癸巳'],
        '甲午辛' => ['甲午', '乙未', '丙申', '丁酉', '戊戌', '己亥', '庚子', '辛丑', '壬寅', '癸卯'],
        '甲辰壬' => ['甲辰', '乙巳', '丙午', '丁未', '戊申', '己酉', '庚戌', '辛亥', '壬子', '癸丑'],
        '甲寅癸' => ['甲寅', '乙卯', '丙辰', '丁巳', '戊午', '己未', '庚申', '辛酉', '壬戌', '癸亥']
    ];

    protected $_sixtyJiaziKongWang = 
    [
        '甲子戊' => '戌亥',
        '甲戌己' => '申酉',
        '甲申庚' => '午未',
        '甲午辛' => '辰巳',
        '甲辰壬' => '寅卯',
        '甲寅癸' => '子丑'
    ];
    
    protected $_shiChenFixed = 
    [
        4   => ['辰', '巳'],
        9   => ['午'],
        2   => ['未', '申'],
        
        3   => ['卯'],
        5   => [],
        7   => ['酉'],
        
        8   => ['寅', '丑'],
        1   => ['子'],
        6   => ['亥', '戌']
    ];


    // 九星八門(原盤)
    protected $_startAndGateOri = [
        4 => ['star' => '輔', 'gate' => '杜'],
        9 => ['star' => '英', 'gate' => '景'],
        2 => ['star' => '芮', 'gate' => '死'],

        3 => ['star' => '衝', 'gate' => '傷'],
        5 => ['star' => '禽', 'gate' => ''],
        7 => ['star' => '柱', 'gate' => '驚'],

        8 => ['star' => '任', 'gate' => '生'],
        1 => ['star' => '蓬', 'gate' => '休'],
        6 => ['star' => '心', 'gate' => '開']
    ];
    
    // 九宮格外圍圈，順時針/逆時針
    protected $_gridCircle = [4, 9, 2, 7, 6, 1, 8, 3];
    protected $_gridCircleReverse = [4, 3, 8, 1, 6, 7, 2, 9];
    protected $_isCenterOri = false;

    // 神
    protected $_eightShen = [1 => '符', 2 => '蛇', 3 => '陰', 4 => '合', 5 => '虎', 6 => '武', 7 => '地', 8 => '天'];
    
    // 星(禽不在内)
    protected $_niceStar = [1 => '蓬', 2 => '任', 3 => '衝', 4 => '輔', 5 => '英', 6 => '芮', 7 => '柱', 8 => '心'];

    // 門
    protected $_eightGate = [1 => '休', 2 => '生', 3 => '傷', 4 => '杜', 5 => '景', 6 => '死', 7 => '驚', 8 => '開'];
    

    public function __construct($data) {
        parent::__construct($data);
        
        // Point to the correct class and function
        $this->_currentRouter['class'] = 'home';
        $this->_currentRouter['function'] = 'index';
        
        $this->_ganzhiLib = (new \App\Libs\calendar\GanZhi());
    }

    public function index() {
        ini_set('max_execution_time', 0);
        
        // Get Page data
        $targetPage = $this->loadModel('page')->getByID(1, $this->_currentLangIndex);

        $testDateTime = $this->randomDate('1990-01-01', '2040-01-01');
        // 特殊case
        //$testDateTime = '1990-07-01 10:08:00'; // 閏問題?
        //$testDateTime = '2004-03-04 06:52:00';
        //$testDateTime = '2026-12-06 15:25:00';
        //$testDateTime = '2000-12-24 06:19:00';
        //$testDateTime = '2026-07-06 09:06:00';
        //$testDateTime = '2023-06-25 09:40:00';
        //$testDateTime = '2037-04-29 06:22:00';
        //$testDateTime = '1990-07-16 13:55:00';
        //$testDateTime = '1999-11-30 19:16:00';
        //$testDateTime = '2015-05-02 12:09:00';
        //$testDateTime = '2014-03-10 06:53:00';
        $this->startYinProcess($testDateTime);
        
        echo '<p style="padding:0;margin:0;">陽曆: '.$this->_palaceResult['hk_datetime'].'</p>';
        echo '<p style="padding:0;margin:0;">農曆: '.implode(' - ', [$this->_palaceResult['lunar_year_chinese'], $this->_palaceResult['lunar_month_chinese'], $this->_palaceResult['lunar_day_chinese']]).'</p>';
        echo '<p style="padding:0;margin:0;">干支: '.implode(' - ', [$this->_palaceResult['ganzhi_year'], $this->_palaceResult['ganzhi_month'], $this->_palaceResult['ganzhi_day'], $this->_palaceResult['ganzhi_hour']]).'</p>';
        
        echo '<p style="padding:0;margin:0;">盤局: '.(($this->_palaceResult['dun_index'] == 1)?'陽':'陰').' '.$this->_palaceResult['dun_number'].' 局</p>';
        echo '<p style="padding:0;margin:0;">旬首: '.$this->_palaceResult['header'].'</p>';
        echo '<p style="padding:0;margin:0;">值符: 天'.$this->_palaceResult['zhi_fu'].' '.$this->_palaceResult['zhi_fu_index'].'宮</p>';
        echo '<p style="padding:0;margin:0;">值使: '.$this->_palaceResult['zhi_shi'].'門 '.$this->_palaceResult['zhi_shi_index'].'宮</p>';
        
        echo '<div style="width:600px;">';
        foreach ($this->_palaceResult['grid'] as $grid) {
            echo '<div style="position:relative;display:inline-block;width:28%;padding:10px;border:2px solid #ddd">';
            
            echo $grid['index'];
            echo '<br/>';
            echo '宮： '.$grid['name'];
            echo '<br/>';
            echo '<br/>';
            echo '神： '.(!empty($grid['shen_alias'])?$grid['shen_alias']:$grid['shen']);
            echo '<br/>';
            echo '星： '.(!empty($grid['star_alias'])?$grid['star_alias']:$grid['star']);
            echo '<br/>';
            echo '門： '.(!empty($grid['gate_alias'])?$grid['gate_alias']:$grid['gate']);
            echo '<br/>';
            echo '<br/>';
            echo '天： '.(!empty($grid['tian_alias'])?$grid['tian_alias']:$grid['tian']);
            echo '<br/>';
            echo '地： '.(!empty($grid['earth_alias'])?$grid['earth_alias']:$grid['earth']);
            
            echo ((!empty($this->_palaceResult['kongwang']) && in_array($grid['index'], $this->_palaceResult['kongwang']))?'<div style="position:absolute;top:0px;right:30px;background:pink;">空</div>':'');
            
            echo ((!empty($this->_palaceResult['yima']) && in_array($grid['index'], $this->_palaceResult['yima']))?'<div style="position:absolute;top:0px;right:10px;background:yellow;">馬</div>':'');

            echo '</div>';
        }
        echo '</div>';
        
        die();
        // Load view
        return $this->pageData(
        [
            'target_page'   =>  $targetPage
        ])->pageView('home');
    }
   
    // 排陰盤
    protected function startYinProcess($currentDateTime) {
        // 天干地支
        $this->getDanZhi($currentDateTime);
        
        // 定局
        $this->setYinDunIndex();
        
        // 佈地盤
        $this->setYinEarth();
        
        // 旬首
        $this->setYinHeader();
        
        // 值符 + 值使
        $this->setYinZhiFuShi();
        
        // 佈天盤
        $this->setYinTian();
        
        // 八門
        $this->setYinGate();
        
        // 九星
        $this->setYinStar();
        
        // 八神
        $this->setYinShen();
        
        // 值符 + 值使 所在宮位
        $this->setYinZhiFuShiIndex();
        
        // 空亡
        $this->setKongWang();
        
        // 驛馬
        $this->setYiMa();
    }

    protected function getDanZhi($currentDateTime) {
        $this->_ganzhiData = $this->_ganzhiLib->convert($currentDateTime);
        
        // overwirte if need
        if(true) {
            $biziLib = (new \App\Libs\calendar\BaZiCalculator(storage_path('solarterms')));
            $baziResult = $biziLib->calculate($currentDateTime);
            if(!empty($baziResult)) {
                $listSolarTerms = $biziLib->getListSolarTerms();
                
                dump($listSolarTerms);
                
                $this->_ganzhiData['ganzhi_year'] = $baziResult['year'];
                $this->_ganzhiData['ganzhi_month'] = $baziResult['month'];
                $this->_ganzhiData['ganzhi_day'] = $baziResult['day'];
                $this->_ganzhiData['ganzhi_hour'] = $baziResult['hour'];
                
                // 上一年冬至
                $this->_ganzhiData['jieqi_dongzhi_last_year'] = $listSolarTerms[(date('Y', strtotime($currentDateTime)) - 1)]['冬至'];
                
                // 本年夏至
                $this->_ganzhiData['jieqi_xiazhi'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['夏至'];
                
                // 本年冬至
                $this->_ganzhiData['jieqi_dongzhi_this_year'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['冬至'];
            }
        }
        
        dump($this->_ganzhiData);
        
        $this->_palaceResult['calc_datetime'] = $currentDateTime;
        $this->_palaceResult['time_zone'] = $this->getParamValue('time_zone', 'hong_kong');
        $this->_palaceResult['hk_datetime'] = $this->_ganzhiData['hk_datetime'];
        $this->_palaceResult['lunar_shengxiao'] = $this->_ganzhiData['lunar_shengxiao'];
        $this->_palaceResult['lunar_year'] = $this->_ganzhiData['lunar_year'];
        $this->_palaceResult['lunar_month'] = $this->_ganzhiData['lunar_month'];
        $this->_palaceResult['lunar_day'] = $this->_ganzhiData['lunar_day'];
        $this->_palaceResult['lunar_year_chinese'] = $this->_ganzhiData['lunar_year_chinese'];
        $this->_palaceResult['lunar_month_chinese'] = $this->_ganzhiData['lunar_month_chinese'];
        $this->_palaceResult['lunar_day_chinese'] = $this->_ganzhiData['lunar_day_chinese'];
        $this->_palaceResult['ganzhi_year'] = $this->_ganzhiData['ganzhi_year'];
        $this->_palaceResult['ganzhi_month'] = $this->_ganzhiData['ganzhi_month'];
        $this->_palaceResult['ganzhi_day'] = $this->_ganzhiData['ganzhi_day'];
        $this->_palaceResult['ganzhi_hour'] = $this->_ganzhiData['ganzhi_hour'];
    }

    protected function setYinDunIndex() {
        $currentDateTime = $this->_ganzhiData['hk_datetime'];
        $jieqiXiazhi = $this->_ganzhiData['jieqi_xiazhi']; 
        $jieqiDongzhiThisYear = $this->_ganzhiData['jieqi_dongzhi_this_year'];

        // 夏至 ~ 冬至 時間内，為陰
        if((strtotime($currentDateTime) >= strtotime($jieqiXiazhi)) && (strtotime($currentDateTime) < strtotime($jieqiDongzhiThisYear))) {
            $this->_yyDunIndex = 2;
        }
        else {
            $this->_yyDunIndex = 1;
        }
        
        $ganzhiYear = mb_substr($this->_ganzhiData['ganzhi_year'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if(md5(trim($ganzhiYear)) === md5(trim($diZhi))) {
                $ganzhiYear = (int)$diZhiKey;
            }
        }
        $lunarMonth = (int)$this->_ganzhiData['lunar_month'];
        $lunarDay = (int)$this->_ganzhiData['lunar_day'];
        $ganzhiHour = mb_substr($this->_ganzhiData['ganzhi_hour'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if(md5(trim($ganzhiHour)) === md5(trim($diZhi))) {
                $ganzhiHour = (int)$diZhiKey;
            }
        }
        
        // 判斷是否閏月（負數即為閏月）
        $isLeapMonth = ($lunarMonth < 0);
        if($isLeapMonth) {
            // 取絕對值得到實際的月數（-5 變成 5）
            $actualMonth = abs($lunarMonth);
            if($lunarDay <= 15) {
                // 閏五月初九：上半月，沿用上個月（五月）的月數
                $lunarMonth = $actualMonth; // 5
            } else {
                // 下半月，用本月（六月）的月數
                $lunarMonth = $actualMonth + 1; // 6
                if($lunarMonth > 12) $lunarMonth = 1;
            }
        } else {
            // 非閏月，保持原樣
            $lunarMonth = abs($lunarMonth); // 確保為正數
        }
        
        $this->_yyDunNumber = (($ganzhiYear + $lunarMonth + $lunarDay + $ganzhiHour)%9);
        if($this->_yyDunNumber === 0) {
            $this->_yyDunNumber = 9;
        }
        
        $this->_palaceResult['dun_index'] = $this->_yyDunIndex;
        $this->_palaceResult['dun_number'] = $this->_yyDunNumber;
    }
    
    protected function setYinEarth() {
        $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), $this->_yyDunNumber));
        foreach ($this->_sixYiThreeQi as $sixThreeKey => $sixThree) {
            $palaceIndex = $circlePattern[$sixThreeKey];
            $this->_palaceResult['grid'][$palaceIndex]['earth'] = $sixThree;
        }
        
        // 5.中宮 合并到 2.坤 
        $this->_palaceResult['grid'][2]['earth_alias'] =  ($this->_palaceResult['grid'][2]['earth'].$this->_palaceResult['grid'][5]['earth']);
    }
    
    protected function setYinHeader() {
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
            foreach ($jiazhi as $child) {
                if(md5(trim($ganzhiHour)) === md5(trim($child))) {
                    $this->_palaceResult['header'] = $jiazhiKey;
                    break;
                }
            }
            if(!empty($this->_palaceResult['header'])) {
                break;
            }
        }
    }
    
    protected function setYinZhiFuShi() {
        // 根據 “旬首” 確定原宮位對應的 符 + 使
        // 例如 “甲午辛”， “旬首”為“辛”， 落在 4.巽宮， 其對應原宮位則為 “天輔” + “杜門”
        $lastChar = mb_substr($this->_palaceResult['header'], -1);
        $zhiFuShiIndex = 0;
        foreach ($this->_palaceResult['grid'] as $grid) {
            if(md5(trim($lastChar)) === md5(trim($grid['earth']))) {
                $zhiFuShiIndex = $grid['index']; 
                break;
            }
        }
        if(!empty($zhiFuShiIndex)) {
            $this->_palaceResult['zhi_fu'] = $this->_startAndGateOri[$zhiFuShiIndex]['star'];
            $this->_palaceResult['zhi_shi'] = $this->_startAndGateOri[$zhiFuShiIndex]['gate'];
            // 落在 5.中宮， 看 2.坤宮對應的原門
            if(empty($this->_palaceResult['zhi_shi'])) {
                $this->_palaceResult['zhi_shi'] = '死';
            }
        }
    }

    protected function setYinTian() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $headGridIndex = $findResult[0];
        $ganHourGridIndex = $findResult[1];
        
        // 由 “時天干” 在地盤所在宮格位置開始， 順時針平移 “旬首” 為開始點的地盤
        if($headGridIndex > 0 && $ganHourGridIndex > 0) {
            // 5.中宮 偏移到 2.坤宮
            if((int)$headGridIndex === 5) {
                $headGridIndex = 2;
                $this->_isCenterOri = true;
            }
            
            if((int)$ganHourGridIndex === 5) {
                $ganHourGridIndex = 2;
                $this->_isCenterOri = true;
            }
    
            $headCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $headGridIndex));
            $earthArr = [];
            foreach ($headCirclePattern as $circleValue) {
                $earthArr[] = implode('|', array_filter([
                    $this->_palaceResult['grid'][$circleValue]['earth'],
                    (!empty($this->_palaceResult['grid'][$circleValue]['earth_alias'])?$this->_palaceResult['grid'][$circleValue]['earth_alias']:'')
                ]));
            }

            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            $loop = 0;
            foreach ($ganHourCirclePattern as $circleValue) {
                $earthValue = explode('|', $earthArr[$loop]);
                $this->_palaceResult['grid'][$circleValue]['tian'] = $earthValue[0];
                if(!empty($earthValue[1])) {
                    $this->_palaceResult['grid'][$circleValue]['tian_alias'] = $earthValue[1];
                }
                $loop++;
            }
        }
        
        // 複製地盤 5.中宮 到 天盤 5.中宮
        $this->_palaceResult['grid'][5]['tian'] = $this->_palaceResult['grid'][5]['earth'];
    }
    
    protected function setYinGate() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $headGridIndex = $findResult[0];

        // “值使” 所在的原門
        $oriGateIndex = 0;
        foreach ($this->_startAndGateOri as $oriKey => $ori) {
            if(md5(trim($this->_palaceResult['zhi_shi'])) === md5(trim($ori['gate']))) {
                $oriGateIndex = $oriKey; 
                break;
            }
        }

        if($headGridIndex > 0 && $oriGateIndex > 0) {
            // 由 “旬首” 在地盤所在宮格位置開始， 順(陽)/逆(陰)時針排時辰
            $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), $headGridIndex));
            
            // 為了方便計算，延長一段
            foreach ($circlePattern as $circleKey => $circleValue) {
                $circlePattern[$circleKey+9] = $circleValue;
            }

            // 尋找 “時天干” 在 “六十甲子” 位置
            $shiftIndex = 0;
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                foreach ($jiazhi as $jiazhiChildKey => $child) {
                    if(md5(trim($this->_palaceResult['ganzhi_hour'])) === md5(trim($child))) {
                        $shiftIndex = ($jiazhiChildKey + 1);
                        break;
                    }
                }
                if(!empty($shiftIndex)) {
                    break;
                }
            }
            
            // 例如： “辛卯” 時， 尋找到其在 “六十甲子” 第8號位置, 對應九宮格位置為 9， 
            // 則由 9 開始，九宮格外圍圈，順時針排
            // 落在 5.中宮， 看 2.坤
            $shiftGridIndex = $circlePattern[$shiftIndex];
            if((int)$shiftGridIndex === 5) {
                $shiftGridIndex = 2;
            }
            $revisedEightGate = $this->arrayReIndex($this->arrayCircle($this->_eightGate, $this->_palaceResult['zhi_shi']));  
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $shiftGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['gate'] = $revisedEightGate[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_palaceResult['grid'][5]['gate'] = '';
    }
    
    protected function setYinStar() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $ganHourGridIndex = $findResult[1];

        $dependIndex = 0;
        if($ganHourGridIndex > 0) {
            $findZhiFu = $this->_palaceResult['zhi_fu'];
            // “天禽星” 寄宮 “天芮星”
            if($findZhiFu == '禽') {
                $findZhiFu = '芮';
            }
            
            // 由 “時天干” 在地盤所在宮格位置開始， 順時針排九星
            $revisedNiceStar = $this->arrayReIndex($this->arrayCircle($this->_niceStar, $findZhiFu));  
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['star'] = $revisedNiceStar[$circleIndex];
                if(md5(trim($revisedNiceStar[$circleIndex])) === md5(trim('芮'))) {
                    $dependIndex = $circleValue;
                }
            }
        }
        
        // 5.中宮 默認為“禽”
        $this->_palaceResult['grid'][5]['star'] = '禽';
        
        // “天禽星” 寄宮 “天芮星”
        $this->_palaceResult['grid'][$dependIndex]['star_alias'] = '芮禽';
    }
    
    protected function setYinShen() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex('tian');
        $headGridIndex = $findResult[0];

        if($headGridIndex > 0) {
            // 由 “旬首” 在天盤所在宮格位置開始， 順(陽)/逆(陰)時針排八神
            if((int)$this->_yyDunIndex === 1) {
                $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $headGridIndex));
            }
            else {
                $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircleReverse, $headGridIndex));
            }
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['shen'] = $this->_eightShen[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_palaceResult['grid'][5]['shen'] = '';
    }
    
    protected function setYinZhiFuShiIndex() {
        // 值符所在宮位
        foreach ($this->_palaceResult['grid'] as $grid) {
            $findZhiFu = $this->_palaceResult['zhi_fu'];
            // “天禽星” 寄宮 “天芮星”
            if($findZhiFu == '禽') {
                $findZhiFu = '芮';
            }
            if(md5(trim($findZhiFu)) === md5(trim($grid['star']))) {
                $this->_palaceResult['zhi_fu_index'] = $grid['index'];
                break;
            }
        }
        // 如果值符所在的地盤是偏移後的宮位，需要尋找其原始宮位
        if(!empty($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias'])) {
            $lastChar = mb_substr($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias'], -1);
            foreach ($this->_palaceResult['grid'] as $grid) {
                if(md5(trim($lastChar)) === md5(trim($grid['earth']))) {
                    $this->_palaceResult['zhi_fu_index'] = $grid['index'];
                }
            }
        }
        
        // 值使所在宮位
        foreach ($this->_palaceResult['grid'] as $grid) {
            $findZhiShi = $this->_palaceResult['zhi_shi'];
            if(md5(trim($findZhiShi)) === md5(trim($grid['gate']))) {
                $this->_palaceResult['zhi_shi_index'] = $grid['index'];
                break;
            }
        }
    }
    
    protected function setKongWang() {
        // 根據 “旬首” 確定其 “空亡”
        $kongWang = $this->_sixtyJiaziKongWang[$this->_palaceResult['header']];
        
        // 速查固定時辰對照表
        foreach (mb_str_split($kongWang) as $char) {
            foreach ($this->_shiChenFixed as $shiChenKey => $shiChen) {
                if(!empty($shiChen) && in_array($char, $shiChen)) {
                    $this->_palaceResult['kongwang'][] = $shiChenKey;
                }
            }
        }
        $this->_palaceResult['kongwang'] = array_unique($this->_palaceResult['kongwang']);
    }

    protected function setYiMa() {
        /* 驛馬速查表
        申子辰 時 → 寅
        寅午戌 時 → 申
        巳酉丑 時 → 亥
        亥卯未 時 → 巳 */
        
        // 根據 “時支” 確定其 “驛馬”
        $lastChar = mb_substr($this->_palaceResult['ganzhi_hour'], -1);
        
        // 速查固定時辰對照表
        $char = '';
        $yiMaFixed = 
        [
            '寅' => ['申', '子', '辰'],
            '申' => ['寅', '午', '戌'],
            '亥' => ['巳', '酉', '丑'],
            '巳' => ['亥', '卯', '未'],
        ];
        foreach ($yiMaFixed as $yimaKey => $yiMa) {
            if(in_array($lastChar, $yiMa)) {
                $char = $yimaKey;
            }
        }

        // 速查固定時辰對照表
        if(!empty($char)) {
            foreach ($this->_shiChenFixed as $shiChenKey => $shiChen) {
                if(!empty($shiChen) && in_array($char, $shiChen)) {
                    $this->_palaceResult['yima'][] = $shiChenKey;
                }
            }
        }
        $this->_palaceResult['yima'] = array_unique($this->_palaceResult['yima']);
    }

    private function findHeadGanHourGridIndex($palaceIndex = 'earth') {
        // 旬首
        $headGridIndex = 0;
        $lastChar = mb_substr($this->_palaceResult['header'], -1);

        // 尋找“旬首”在地盤或天盤所在宮格位置， 如原本落在 5.中宮，則尋找偏移後的宮位
        foreach ($this->_palaceResult['grid'] as $grid) {
            if($this->_isCenterOri && !empty($grid[$palaceIndex.'_alias'])) {
                if(md5(trim($lastChar)) === md5(trim(mb_substr($grid[$palaceIndex.'_alias'], -1)))) {
                    $headGridIndex = $grid['index'];
                    break;
                }
            }
            else {
                if(md5(trim($lastChar)) === md5(trim($grid[$palaceIndex]))) {
                    $headGridIndex = $grid['index'];
                    if(!$this->_isCenterOri) {
                        break;
                    }
                }
            }
        }
        
        // 尋找“時天干”在地盤或天盤所在宮格位置， 如原本落在 5.中宮，則尋找偏移後的宮位
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        $ganHourGridIndex = 0;
        foreach ($this->_palaceResult['grid'] as $grid) {
            if($this->_isCenterOri && !empty($grid[$palaceIndex.'_alias'])) {
                if(md5(trim(mb_substr($ganzhiHour, 0, 1))) === md5(trim(mb_substr($grid[$palaceIndex.'_alias'], -1)))) {
                    $ganHourGridIndex = $grid['index'];
                    break;
                }
            }
            else {
                if(md5(trim(mb_substr($ganzhiHour, 0, 1))) === md5(trim($grid[$palaceIndex]))) {
                    $ganHourGridIndex = $grid['index'];
                    if(!$this->_isCenterOri) {
                        break;
                    }
                }
            }
        }
        /*
        特殊説明：
        - 甲子時用戊
        - 甲戌時用己
        - 甲申時用庚
        - 甲午時用辛
        - 甲辰時用壬
        - 甲寅時用癸
        */
        if($ganHourGridIndex == 0) {
            $newGanHour = '';
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                if(md5(trim($ganzhiHour)) === md5(trim(mb_substr($jiazhiKey, 0, 2)))) {
                    $newGanHour = mb_substr($jiazhiKey, -1);
                    break;
                }
            }
            
            // 尋找修正後 “時天干”在地盤或天盤所在宮格位置， 如原本落在 5.中宮，則尋找偏移後的宮位
            foreach ($this->_palaceResult['grid'] as $grid) {
                if($this->_isCenterOri && !empty($grid[$palaceIndex.'_alias'])) {
                    if(md5(trim(mb_substr($newGanHour, 0, 1))) === md5(trim(mb_substr($grid[$palaceIndex.'_alias'], -1)))) {
                        $ganHourGridIndex = $grid['index'];
                        break;
                    }
                }
                else {
                    if(md5(trim(mb_substr($newGanHour, 0, 1))) === md5(trim($grid[$palaceIndex]))) {
                        $ganHourGridIndex = $grid['index'];
                        if(!$this->_isCenterOri) {
                            break;
                        }
                    }
                }
            }
        }

        return [$headGridIndex, $ganHourGridIndex];
    }
    
    // 其他輔助功能
    private function randomDate($startDate = '1970-01-01', $endDate = '2100-12-31') {
        // 轉換為時間戳
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        // 隨機產生一個時間戳
        $randomTimestamp = rand($start, $end);

        // 格式化輸出
        return date('Y-m-d H:i', $randomTimestamp).':00';
    }
    
    public function arrayReIndex($arr = [], $start_index = 1) {
        $start_index = max(1, $start_index);
        $new_arr = [];
        if(!empty($arr)) {
            foreach ($arr as $value) {
                $new_arr[$start_index] = $value;
                $start_index++;
            }
        }
        return $new_arr;
    }
    
    public function arrayFindIndex($arr = [], $find = '') {
        $find_key = 0;
        if(!empty($arr) && !empty($find)) {
            foreach ($arr as $key => $value) {
                if(trim($find) === trim($value)) {
                    $find_key = $key;
                    break;
                }
            }
            
        }
        
        return $find_key;
    }
    
    public function arrayCircle($arr = [], $break_point = 1) {
        $find = false;
        $head = [];
        $end = [];
        if(!empty($arr) && !empty($break_point)) {
            foreach ($arr as $key => $value) {
                if($value == $break_point) {
                    $find = true;
                }
                if($find) {
                    $head[] = $value;
                }
                else {
                    $end[] = $value;
                }
            }
            return array_merge($head, $end);
        }
        
        return $arr;
    } 
}
