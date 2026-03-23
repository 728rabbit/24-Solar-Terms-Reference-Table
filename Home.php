<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Home extends WebController {
    
    protected $_ganzhiLib;
    protected $_ganzhiData;
    protected $_palaceResult = 
    [
        'datetime'     =>  '',
        'time_zone'         =>  'hong_kong',
        'datetime_hk'       =>  '',
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
        'lead'              =>  '',  // 旬首
        'zhi_ori_index'     =>  '',  // 原值符/使宮位
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
        
        'kong_wang'          =>  [],
        'yi_ma'              =>  []
    ];

    // 1. 陽： 冬至 -> 夏至
    // 2. 陰： 夏至 -> 冬至
    protected $_yyDunIndex = 0;
    protected $_yyDunNumber = 0;
    
    // 24節氣三元表
    protected $_jieqiSanYuanTable = [
        '冬至' => [1, 7, 4], '小寒' => [2, 8, 5], '大寒' => [3, 9, 6],
        '立春' => [8, 5, 2], '雨水' => [9, 6, 3], '驚蟄' => [1, 7, 4],
        '春分' => [3, 9, 6], '清明' => [4, 1, 7], '穀雨' => [5, 2, 8],
        '立夏' => [4, 1, 7], '小滿' => [5, 2, 8], '芒種' => [6, 3, 9],
        '夏至' => [9, 3, 6], '小暑' => [8, 2, 5], '大暑' => [7, 1, 4],
        '立秋' => [2, 5, 8], '處暑' => [1, 4, 7], '白露' => [9, 3, 6],
        '秋分' => [7, 1, 4], '寒露' => [6, 9, 3], '霜降' => [5, 8, 2],
        '立冬' => [6, 9, 3], '小雪' => [5, 8, 2], '大雪' => [4, 7, 1]
    ];
    
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
    
    // 洛書宮序 順 + 逆
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

    // 神
    protected $_eightShen = [1 => '符', 2 => '蛇', 3 => '陰', 4 => '合', 5 => '虎', 6 => '武', 7 => '地', 8 => '天'];
    
    // 星(禽不在内)
    protected $_niceStar = [1 => '蓬', 2 => '任', 3 => '衝', 4 => '輔', 5 => '英', 6 => '芮', 7 => '柱', 8 => '心'];

    // 門
    protected $_eightGate = [1 => '休', 2 => '生', 3 => '傷', 4 => '杜', 5 => '景', 6 => '死', 7 => '驚', 8 => '開'];
    
    // 門迫
    protected $_menpoFixed =
    [
        4   => ['開', '驚'],
        9   => ['休'],
        2   => ['傷', '杜'],
        
        3   => ['開', '驚'],
        7   => ['景'],
        
        8   => ['傷', '杜'],
        1   => ['生', '死'],
        6   => ['景']
    ];
    
    // 擊刑
    protected $_jixingFixed =
    [
        4   => ['壬', '癸'],
        9   => ['辛'],
        2   => ['己'],
        
        3   => ['戊'],
        
        8   => ['庚']
    ];
    
    // 入墓
    protected $_rumuFixed = 
    [
        4   => ['辛', '壬'],
        2   => ['甲', '癸'],

        8   => ['丁', '己', '庚'],
        6   => ['乙', '丙', '戊']
    ];

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
        //$testDateTime = '2020-07-29 17:50:00'; // 隱干有問題
        //$testDateTime = '2033-11-23 07:01:00'; // 農曆問題

        //$testDateTime = '2033-09-11 05:38:00'; // 農曆問題
        //$testDateTime = '2030-12-06 05:56:00'; // 隱干有問題
        //$testDateTime = '2017-11-22 18:40:00'; // 隱干有問題 
        //$testDateTime = '2024-08-10 05:30:00'; // 隱干有問題 
        //$testDateTime = '2015-05-22 19:22:00'; // 隱干有問題 
  
        if(!empty($_GET['date'])) {
            $testDateTime = $_GET['date'];
        }
        
        $method = $this->getParamValue('method', 3);
        $this->startProcess($testDateTime, $method);
        
        //dump($this->_palaceResult);
        
        echo '<p style="padding:0;margin:0;">陽曆: '.$this->_palaceResult['datetime_hk'].'</p>';
        echo '<p style="padding:0;margin:0;">農曆: '.implode(' - ', [$this->_palaceResult['lunar_year_chinese'], $this->_palaceResult['lunar_month_chinese'], $this->_palaceResult['lunar_day_chinese']]).'</p>';
        echo '<p style="padding:0;margin:0;">干支: '.implode(' - ', [$this->_palaceResult['ganzhi_year'], $this->_palaceResult['ganzhi_month'], $this->_palaceResult['ganzhi_day'], $this->_palaceResult['ganzhi_hour']]).'</p>';
        
        echo '<p style="padding:0;margin:0;">盤局: '.(($this->_palaceResult['dun_index'] == 1)?'陽':'陰').' '.$this->_palaceResult['dun_number'].' 局</p>';
        echo '<p style="padding:0;margin:0;">旬首: '.$this->_palaceResult['lead'].'</p>';
        echo '<p style="padding:0;margin:0;">值符: 天'.$this->_palaceResult['zhi_fu'].' '.$this->_palaceResult['zhi_fu_index'].'宮</p>';
        echo '<p style="padding:0;margin:0;">值使: '.$this->_palaceResult['zhi_shi'].'門 '.$this->_palaceResult['zhi_shi_index'].'宮</p>';
        
        echo '<div style="width:600px;">';
        foreach ($this->_palaceResult['grid'] as $grid) {
            echo '<div style="position:relative;display:inline-block;width:28%;padding:10px;border:2px solid #ddd">';
            
            echo $grid['index'].' | '.$grid['name'];
            echo '<br/>';
            echo '<br/>';
            echo '隱干： '.(!empty($grid['yin_gan'])?$grid['yin_gan']:'');
            echo '<br/>';
            echo '<br/>';
            echo '神： '.(!empty($grid['shen_alias'])?$grid['shen_alias']:$grid['shen']);
            echo '<br/>';
            echo '星： '.(!empty($grid['star_alias'])?$grid['star_alias']:$grid['star']);
            echo '<br/>';
            echo '門： '.(!empty($grid['gate_alias'])?$grid['gate_alias']:$grid['gate']).(!empty($grid['men_po'])?' (門迫)':'');
            echo '<br/>';
            echo '<br/>';
            echo '天： '.(!empty($grid['tian_alias'])?$grid['tian_alias']:$grid['tian']).(!empty($grid['ji_xing_tian'])?' (擊刑)':'').(!empty($grid['ru_mu_tian'])?' (入墓)':'');
            echo '<br/>';
            echo '地： '.(!empty($grid['earth_alias'])?$grid['earth_alias']:$grid['earth']).(!empty($grid['ji_xing_earth'])?' (擊刑)':'').(!empty($grid['ru_mu_earth'])?' (入墓)':'');
            
            echo ((!empty($this->_palaceResult['kong_wang']) && in_array($grid['index'], $this->_palaceResult['kong_wang']))?'<div style="position:absolute;top:0px;right:30px;background:pink;">空</div>':'');
            
            echo ((!empty($this->_palaceResult['yi_ma']) && in_array($grid['index'], $this->_palaceResult['yi_ma']))?'<div style="position:absolute;top:0px;right:10px;background:yellow;">馬</div>':'');

           
            
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
    protected function startProcess($currentDateTime, $method = 1) {
        // 天干地支
        $this->getDanZhi($currentDateTime);
        
        // 定局
        $this->setDunIndex($method);
        
        // 佈地盤
        $this->setEarth();
        
        // 旬首
        $this->setLead();
        
        // 值符 + 值使
        $this->setZhiFuShi();
        
        // 佈天盤
        $this->setTian();

        // 八門
        $this->setGate();
        
        // 九星
        $this->setStar();
        
        // 八神
        $this->setShen();
        
        // 值符 + 值使 所在宮位
        $this->setZhiFuShiIndex();
        
        // 空亡
        $this->setKongWang();
        
        // 驛馬
        $this->setYiMa();
        
        // 門迫
        $this->setMenPo();
        
        // 擊刑
        $this->setJiXing();
        
        // 入墓
        $this->setRuMu();
        
        // 隱干
        $this->setYinGan();
    }

    protected function getDanZhi($currentDateTime) {
        $this->_ganzhiData = $this->_ganzhiLib->convert($currentDateTime);
        
        //dump($this->_ganzhiData);
        
        // overwirte if need
        if(true) {
            $biziLib = (new \App\Libs\calendar\BaZiCalculator(storage_path()));
            $baziResult = $biziLib->calculate($currentDateTime, $this->getParamValue('time_zone', 'hong_kong'));
            if(!empty($baziResult)) {
                $this->_ganzhiData['ganzhi_year'] = $baziResult['ganzhi_year'];
                $this->_ganzhiData['ganzhi_month'] = $baziResult['ganzhi_month'];
                $this->_ganzhiData['ganzhi_day'] = $baziResult['ganzhi_day'];
                $this->_ganzhiData['ganzhi_hour'] = $baziResult['ganzhi_hour'];
                
                $listSolarTerms = $baziResult['jieqi_table'];
                if(!empty($listSolarTerms)) {
                    // 上一年冬至
                    $this->_ganzhiData['jieqi_dongzhi_last_year'] = $listSolarTerms[(date('Y', strtotime($currentDateTime)) - 1)]['冬至'];

                    // 本年夏至
                    $this->_ganzhiData['jieqi_xiazhi'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['夏至'];

                    // 本年冬至
                    $this->_ganzhiData['jieqi_dongzhi_this_year'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['冬至'];

                    $allSTS = [];
                    foreach ($listSolarTerms as $year => $st) {
                        foreach ($st as $name => $time) {
                            $allSTS[$year.'_'.$name] = $time;
                        }
                    }
                    foreach ($allSTS as $stName => $stDateTime) {
                        if(strtotime($this->_ganzhiData['datetime_hk']) <= strtotime($stDateTime)) {
                            $this->_ganzhiData['current_jieqi'] = ['name' => $stName, 'datetime' => $stDateTime];
                            break;
                        }
                    }
                }
            }
        }
        
        $this->_palaceResult['datetime'] = $currentDateTime;
        $this->_palaceResult['time_zone'] = $this->getParamValue('time_zone', 'hong_kong');
        $this->_palaceResult['datetime_hk'] = $this->_ganzhiData['datetime_hk'];
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

    protected function setDunIndex($method) {
        $currentDateTime = $this->_ganzhiData['datetime_hk'];
        $jieqiXiazhi = $this->_ganzhiData['jieqi_xiazhi']; 
        $jieqiDongzhiThisYear = $this->_ganzhiData['jieqi_dongzhi_this_year'];
        
        // 夏至 ~ 冬至 時間内，為陰
        if((strtotime($currentDateTime) >= strtotime($jieqiXiazhi)) && (strtotime($currentDateTime) < strtotime($jieqiDongzhiThisYear))) {
            $this->_yyDunIndex = 2;
        }
        else {
            $this->_yyDunIndex = 1;
        }
        
        // 1. 陽盤拆補 | 2. 陽盤置閏 | 3. 陰盤
        if($method == 1) {
            $this->calculateChaiBuMethod();
        }
        elseif($method == 2) {
            $this->calculateZhiRunMethod();
        }
        else {
            $this->calculateYinPanMethod();
        }
        
        $this->_palaceResult['dun_index'] = $this->_yyDunIndex;
        $this->_palaceResult['dun_number'] = $this->_yyDunNumber;
    }
    
    /**
    * 拆補法計算局數
    * 核心規則：嚴格按照節氣，節氣一到立即換局，不考慮符頭
    */
    private function calculateChaiBuMethod() {
        // 獲取當前節氣
        $currentJieqi = $this->_ganzhiData['current_jieqi'];
        if(empty($currentJieqi['name'])) {
            // 如果無法獲取節氣，降級使用置閏法
            $this->calculateZhiRunMethod();
            return;
        }

        $jieqiName = preg_replace('/^\d+_/u', '', $currentJieqi['name']);
        $jieqiStartTime = $currentJieqi['datetime'];

        // 24節氣三元表
        $this->_jieqiSanYuanTable = [
            '冬至' => [1, 7, 4], '小寒' => [2, 8, 5], '大寒' => [3, 9, 6],
            '立春' => [8, 5, 2], '雨水' => [9, 6, 3], '驚蟄' => [1, 7, 4],
            '春分' => [3, 9, 6], '清明' => [4, 1, 7], '穀雨' => [5, 2, 8],
            '立夏' => [4, 1, 7], '小滿' => [5, 2, 8], '芒種' => [6, 3, 9],
            '夏至' => [9, 3, 6], '小暑' => [8, 2, 5], '大暑' => [7, 1, 4],
            '立秋' => [2, 5, 8], '處暑' => [1, 4, 7], '白露' => [9, 3, 6],
            '秋分' => [7, 1, 4], '寒露' => [6, 9, 3], '霜降' => [5, 8, 2],
            '立冬' => [6, 9, 3], '小雪' => [5, 8, 2], '大雪' => [4, 7, 1]
        ];

        // 計算當前日期距離節氣開始的天數（從0開始）
        $currentTimestamp = strtotime($this->_ganzhiData['datetime_hk']);
        $jieqiStartTimestamp = strtotime($jieqiStartTime);
        $daysDiff = floor(($currentTimestamp - $jieqiStartTimestamp) / 86400);

        // 拆補法核心：根據天數確定上、中、下元
        // 0-4天：上元，5-9天：中元，10-14天：下元
        if($daysDiff < 0) {
            // 如果當前時間在節氣開始之前，說明應該使用上一個節氣的下元
            $prevJieqi = $this->getPrevJieqi($jieqiName);
            if($prevJieqi && isset($this->_jieqiSanYuanTable[$prevJieqi])) {
                // 使用上一個節氣的下元（索引2）
                $this->_yyDunNumber = $this->_jieqiSanYuanTable[$prevJieqi][2];
                $this->_palaceResult['dun_term'] = $prevJieqi;
                $this->_palaceResult['dun_yuan'] = 3; // 下元
            } else {
                // 降級使用置閏法
                $this->calculateZhiRunMethod();
            }
            return;
        }

        // 正常情況：在節氣範圍內
        if($daysDiff >= 0 && $daysDiff <= 14) {
            $yuanIndex = floor($daysDiff / 5);
            // 確保索引在 0-2 範圍內
            $yuanIndex = min(2, max(0, $yuanIndex));
            $this->_yyDunNumber = $this->_jieqiSanYuanTable[$jieqiName][$yuanIndex];
            $this->_palaceResult['dun_term'] = $jieqiName;
            $this->_palaceResult['dun_yuan'] = $yuanIndex + 1; // 1:上元, 2:中元, 3:下元
            $this->_palaceResult['dun_days_diff'] = $daysDiff;
            return;
        }

        // 如果超過14天，說明應該使用下一個節氣
        if($daysDiff > 14) {
            $nextJieqi = $this->getNextJieqi($jieqiName);
            if($nextJieqi && isset($this->_jieqiSanYuanTable[$nextJieqi])) {
                // 使用下一個節氣的上元（索引0）
                $this->_yyDunNumber = $this->_jieqiSanYuanTable[$nextJieqi][0];
                $this->_palaceResult['dun_term'] = $nextJieqi;
                $this->_palaceResult['dun_yuan'] = 1; // 上元
            } else {
                $this->calculateZhiRunMethod();
            }
            return;
        }
    }

    /*
    * 置閏法（查表法）
    * 核心規則：根據符頭（甲己日）定元，直接查表
    */
    private function calculateZhiRunMethod() {
        // 日干支到元索引的映射表（符頭定局）
        $ganZhiToYuanMap = [
            // 上元（0）
            '甲子' => 0, '乙丑' => 0, '丙寅' => 0, '丁卯' => 0, '戊辰' => 0,
            '己卯' => 0, '庚辰' => 0, '辛巳' => 0, '壬午' => 0, '癸未' => 0,
            '甲午' => 0, '乙未' => 0, '丙申' => 0, '丁酉' => 0, '戊戌' => 0,
            '己酉' => 0, '庚戌' => 0, '辛亥' => 0, '壬子' => 0, '癸丑' => 0,
            // 中元（1）
            '己巳' => 1, '庚午' => 1, '辛未' => 1, '壬申' => 1, '癸酉' => 1,
            '甲申' => 1, '乙酉' => 1, '丙戌' => 1, '丁亥' => 1, '戊子' => 1,
            '己亥' => 1, '庚子' => 1, '辛丑' => 1, '壬寅' => 1, '癸卯' => 1,
            '甲寅' => 1, '乙卯' => 1, '丙辰' => 1, '丁巳' => 1, '戊午' => 1,
            // 下元（2）
            '甲戌' => 2, '乙亥' => 2, '丙子' => 2, '丁丑' => 2, '戊寅' => 2,
            '己丑' => 2, '庚寅' => 2, '辛卯' => 2, '壬辰' => 2, '癸巳' => 2,
            '甲辰' => 2, '乙巳' => 2, '丙午' => 2, '丁未' => 2, '戊申' => 2,
            '己未' => 2, '庚申' => 2, '辛酉' => 2, '壬戌' => 2, '癸亥' => 2
        ];

        // 獲取當前節氣
        $currentJieqi = $this->_ganzhiData['current_jieqi'];
        if(empty($currentJieqi['name'])) {
            // 如果無法獲取節氣，使用陰盤
            $this->calculateYinPanMethod();
            return;
        }
        $jieqiName = preg_replace('/^\d+_/u', '', $currentJieqi['name']);

        // 根據日干支查詢元索引
        $ganzhiDay = $this->_ganzhiData['ganzhi_day'];
        $yuanIndex = isset($ganZhiToYuanMap[$ganzhiDay]) ? $ganZhiToYuanMap[$ganzhiDay] : 0;

        // 獲取局數
        if(isset($this->_jieqiSanYuanTable[$jieqiName])) {
            $this->_yyDunNumber = $this->_jieqiSanYuanTable[$jieqiName][$yuanIndex];
            $this->_palaceResult['dun_term'] = $jieqiName;
            $this->_palaceResult['dun_yuan'] = $yuanIndex + 1;
            $this->_palaceResult['dun_futou'] = $ganzhiDay;
        } else {
            // 降級使用陰盤
            $this->calculateYinPanMethod();
        }
    }

    /*
    * 陰盤計算方法（原有邏輯）
    * 取局數方法：年支序數 + 舊曆月數 + 舊曆日數 + 時支序數，總數以 9 除之，取餘數。 其餘數必少於 9，整除作 9 數。
    */
    private function calculateYinPanMethod() {
        $ganzhiYear = mb_substr($this->_ganzhiData['ganzhi_year'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if(md5(trim($ganzhiYear)) == md5(trim($diZhi))) {
                $ganzhiYear = (int)$diZhiKey;
                break;
            }
        }

        $lunarMonth = (int)$this->_ganzhiData['lunar_month'];
        $lunarDay = (int)$this->_ganzhiData['lunar_day'];

        $ganzhiHour = mb_substr($this->_ganzhiData['ganzhi_hour'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if(md5(trim($ganzhiHour)) == md5(trim($diZhi))) {
                $ganzhiHour = (int)$diZhiKey;
                break;
            }
        }

        $this->_yyDunNumber = ($ganzhiYear + abs($lunarMonth) + $lunarDay + $ganzhiHour) % 9;
        if($this->_yyDunNumber == 0) {
            $this->_yyDunNumber = 9;
        }

        $this->_palaceResult['dun_method'] = '陰盤';
    }

    /*
    * 獲取下一個節氣
    * @param string $currentJieqi 當前節氣名稱
    * @return string|null
    */
    private function getNextJieqi($currentJieqi) {
        $jieqiOrder = [
            '冬至', '小寒', '大寒', '立春', '雨水', '驚蟄', 
            '春分', '清明', '穀雨', '立夏', '小滿', '芒種',
            '夏至', '小暑', '大暑', '立秋', '處暑', '白露',
            '秋分', '寒露', '霜降', '立冬', '小雪', '大雪'
        ];

        $index = array_search($currentJieqi, $jieqiOrder);
        if($index !== false && isset($jieqiOrder[$index + 1])) {
            return $jieqiOrder[$index + 1];
        }
        return null;
    }

    /**
     * 獲取上一個節氣
     * @param string $currentJieqi 當前節氣名稱
     * @return string|null
     */
    private function getPrevJieqi($currentJieqi) {
        $jieqiOrder = [
            '冬至', '小寒', '大寒', '立春', '雨水', '驚蟄', 
            '春分', '清明', '穀雨', '立夏', '小滿', '芒種',
            '夏至', '小暑', '大暑', '立秋', '處暑', '白露',
            '秋分', '寒露', '霜降', '立冬', '小雪', '大雪'
        ];

        $index = array_search($currentJieqi, $jieqiOrder);
        if($index !== false && isset($jieqiOrder[$index - 1])) {
            return $jieqiOrder[$index - 1];
        }
        return null;
    }

    protected function setEarth() {
        // 以 “局數” 開始點，按洛書宮序(陽順陰逆)， 排 “戊己庚申壬癸丁丙乙”
        $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), $this->_yyDunNumber));
        foreach ($this->_sixYiThreeQi as $sixThreeKey => $sixThree) {
            $palaceIndex = $circlePattern[$sixThreeKey];
            $this->_palaceResult['grid'][$palaceIndex]['earth'] = $sixThree;
        }
        
        // 5.中宮 合并到 2.坤 
        $this->_palaceResult['grid'][2]['earth_alias'] =  ($this->_palaceResult['grid'][2]['earth'].$this->_palaceResult['grid'][5]['earth']);
    }
    
    protected function setLead() {
        // “旬首” = “時天干” 所在的 “六十甲子” 索引頭 
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
            foreach ($jiazhi as $child) {
                if(md5(trim($ganzhiHour)) == md5(trim($child))) {
                    $this->_palaceResult['lead'] = $jiazhiKey;
                    break;
                }
            }
            if(!empty($this->_palaceResult['lead'])) {
                break;
            }
        }
    }
    
    protected function setZhiFuShi() {
        // 根據 “旬首” 確定原宮位對應的 符 + 使
        // 例如 “甲午辛”， “旬首” 為 “辛”， 落在 4.巽宮， 其對應原宮位則為 “天輔” + “杜門”
        $lastChar = mb_substr($this->_palaceResult['lead'], -1);
        $zhiFuShiIndex = 0;
        foreach ($this->_palaceResult['grid'] as $grid) {
            if(md5(trim($lastChar)) == md5(trim($grid['earth']))) {
                $zhiFuShiIndex = $grid['index']; 
                break;
            }
        }
        if(!empty($zhiFuShiIndex)) {
            $this->_palaceResult['zhi_ori_index'] = $zhiFuShiIndex;
            $this->_palaceResult['zhi_fu'] = $this->_startAndGateOri[$zhiFuShiIndex]['star'];
            $this->_palaceResult['zhi_shi'] = $this->_startAndGateOri[$zhiFuShiIndex]['gate'];
            
            // 落在 5.中宮， 看 2.坤宮對應的原門
            if(empty($this->_palaceResult['zhi_shi'])) {
                $this->_palaceResult['zhi_shi'] = '死';
            }
        }
    }

    protected function setTian() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $headGridIndex = $findResult[0];
        $ganHourGridIndex = $findResult[1];

        if($headGridIndex > 0 && $ganHourGridIndex > 0) {
            // 以 “旬首” 開始位置， 順時針獲取地盤 => “新順序地盤”
            $headCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $headGridIndex));
            $earthArr = [];
            foreach ($headCirclePattern as $circleValue) {
                $earthArr[] = implode('|', array_filter([
                    $this->_palaceResult['grid'][$circleValue]['earth'],
                    (!empty($this->_palaceResult['grid'][$circleValue]['earth_alias'])?$this->_palaceResult['grid'][$circleValue]['earth_alias']:'')
                ]));
            }

            // “時天干” 地盤所在宮位開始， 順時針繞圈排 “新順序地盤”
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
    
    protected function setGate() {
        // 原 “值使(門)” 所在宮位
        $zhishiGridIndex = $this->_palaceResult['zhi_ori_index'];
        
        if($zhishiGridIndex > 0) {
            // 尋找 “時天干” 在 “六十甲子” 位置
            $shiftIndex = 0;
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                foreach ($jiazhi as $jiazhiChildKey => $child) {
                    if(md5(trim($this->_palaceResult['ganzhi_hour'])) == md5(trim($child))) {
                        $shiftIndex = ($jiazhiChildKey + 1);
                        break;
                    }
                }
                if(!empty($shiftIndex)) {
                    break;
                }
            }
            
            // 由原 “值使(門)” 所在宮位開始， 按洛書宮序(陽順陰逆)
            $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), $zhishiGridIndex));
            
            // 為了方便計算，延長一段
            foreach ($circlePattern as $circleKey => $circleValue) {
                $circlePattern[$circleKey+9] = $circleValue;
            }
            
            // 落在 5.中宮， 看 2.坤
            $shiftGridIndex = $circlePattern[$shiftIndex];
            if((int)$shiftGridIndex == 5) {
                $shiftGridIndex = 2;
            }
            
            // 以 “值時” 開始， 重新順序八門 => “新順序八門”
            $revisedEightGate = $this->arrayReIndex($this->arrayCircle($this->_eightGate, $this->_palaceResult['zhi_shi'])); 
            
            // 以“平移位” 開始， 順時針繞圈排 “新順序八門”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $shiftGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['gate'] = $revisedEightGate[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_palaceResult['grid'][5]['gate'] = '';
    }
    
    protected function setStar() {
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
            
            // 以 “值符” 開始， 重新順序九星 => “新順序九星”
            $revisedNiceStar = $this->arrayReIndex($this->arrayCircle($this->_niceStar, $findZhiFu));
            
            // 由 “時天干” 在地盤所在宮位開始，順時針繞圈排 “新順序八門”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['star'] = $revisedNiceStar[$circleIndex];
                if(md5(trim($revisedNiceStar[$circleIndex])) == md5(trim('芮'))) {
                    $dependIndex = $circleValue;
                }
            }
        }

        // 5.中宮 默認為“禽”
        $this->_palaceResult['grid'][5]['star'] = '禽';
        
        // “天禽星” 寄宮 “天芮星”
        if(!empty($dependIndex)) {
            $this->_palaceResult['grid'][$dependIndex]['star_alias'] = '芮禽';
        }
    }
    
    protected function setShen() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $ganHourGridIndex = $findResult[1];
        
        if($ganHourGridIndex > 0) {
            // 由 “時天干” 在地盤所在宮位開始， 順(陽)/逆(陰)時針繞圈排八神
            if((int)$this->_yyDunIndex == 1) {
                $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            }
            else {
                $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircleReverse, $ganHourGridIndex));
            }
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['shen'] = $this->_eightShen[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_palaceResult['grid'][5]['shen'] = '';
    }
    
    protected function setZhiFuShiIndex() {
        foreach ($this->_palaceResult['grid'] as $grid) {
            $findZhiFu = $this->_palaceResult['zhi_fu'];
            if($findZhiFu == '禽') {
                $findZhiFu = '芮';
            }
            if(md5(trim($findZhiFu)) == md5(trim($grid['star']))) {
                $this->_palaceResult['zhi_fu_index'] = $grid['index'];
                break;
            }
        }
        
        // 比較 值符地盤 和 “時天干” 是否有偏移
        $ganzhiHour = trim($this->_ganzhiData['ganzhi_hour']);
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            $newGanHour = '';
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                if(md5(trim($ganzhiHour)) == md5(mb_substr(trim($jiazhiKey), 0, 2))) {
                    $newGanHour = mb_substr($jiazhiKey, -1);
                    break;
                }
            }
            $ganzhiHour = $newGanHour;
        }
        if(!empty($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias'])) {
            if(md5(mb_substr($ganzhiHour, 0, 1)) == md5(mb_substr(trim($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias']), -1))) {  
               $this->_palaceResult['zhi_fu_index'] = 5;
            }
        }
        
        // 值使所在宮位
        foreach ($this->_palaceResult['grid'] as $grid) {
            $findZhiShi = $this->_palaceResult['zhi_shi'];
            if(md5(trim($findZhiShi)) == md5(trim($grid['gate']))) {
                $this->_palaceResult['zhi_shi_index'] = $grid['index'];
                break;
            }
        }
    }
    
    protected function setKongWang() {
        // 根據 “旬首” 確定其 “空亡”
        $kongWang = $this->_sixtyJiaziKongWang[$this->_palaceResult['lead']];
        
        // 速查固定時辰對照表
        foreach (mb_str_split($kongWang) as $char) {
            foreach ($this->_shiChenFixed as $shiChenKey => $shiChen) {
                if(!empty($shiChen) && in_array($char, $shiChen)) {
                    $this->_palaceResult['kong_wang'][] = $shiChenKey;
                }
            }
        }
        $this->_palaceResult['kong_wang'] = array_unique($this->_palaceResult['kong_wang']);
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
                    $this->_palaceResult['yi_ma'][] = $shiChenKey;
                }
            }
        }
        $this->_palaceResult['yi_ma'] = array_unique($this->_palaceResult['yi_ma']);
    }
    
    protected function setMenPo() {
        // 速查門迫對照表
        foreach ($this->_palaceResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_menpoFixed[$grid['index']])) {
                if(in_array($grid['gate'], $this->_menpoFixed[$grid['index']])) {
                    $this->_palaceResult['grid'][$gridKey]['men_po'] = $grid['gate'];
                }
            }
        }
    }
    
    protected function setJiXing() {
        // 速查擊刑對照表
        foreach ($this->_palaceResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_jixingFixed[$grid['index']])) {
                foreach (['earth', 'earth_alias', 'tian', 'tian_alias'] as $findIndex) {
                    if(!empty($grid[$findIndex])) {
                        $arr = array_unique(array_filter(mb_str_split($grid[$findIndex])));
                        foreach ($arr as $char) {
                            if(in_array($char, $this->_jixingFixed[$grid['index']])) {
                                if(in_array($findIndex, ['earth', 'earth_alias'])) {
                                    $this->_palaceResult['grid'][$gridKey]['ji_xing_earth'][] = $char;
                                }
                                else {
                                    $this->_palaceResult['grid'][$gridKey]['ji_xing_tian'][] = $char;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    protected function setRuMu() {
        // 速查入墓對照表
        foreach ($this->_palaceResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_rumuFixed[$grid['index']])) {
                foreach (['earth', 'earth_alias', 'tian', 'tian_alias'] as $findIndex) {
                    if(!empty($grid[$findIndex])) {
                        $arr = array_unique(array_filter(mb_str_split($grid[$findIndex])));
                        foreach ($arr as $char) {
                            if(in_array($char, $this->_rumuFixed[$grid['index']])) {
                                if(in_array($findIndex, ['earth', 'earth_alias'])) {
                                    $this->_palaceResult['grid'][$gridKey]['ru_mu_earth'][] = $char;
                                }
                                else {
                                    $this->_palaceResult['grid'][$gridKey]['ru_mu_tian'][] = $char;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    protected function setYinGan() {
        // 固定時辰
        foreach ($this->_palaceResult['grid'] as $gird) {
            $this->_palaceResult['grid'][$gird['index']]['shi_chen'] = $this->_shiChenFixed[$gird['index']];
        }

        $ganzhiHour = trim($this->_ganzhiData['ganzhi_hour']);
        // 遇 “甲”， 中宮開始， 按洛書宮序(陽順陰逆)， 排 “旬首” 開始的 “戊己庚申壬癸丁丙乙”
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            // 根據 “旬首”， 重新順序 “戊己庚申壬癸丁丙乙”
            $lastChar = mb_substr($this->_palaceResult['lead'], -1);
            $revisedSixYiThreeQi = $this->arrayReIndex($this->arrayCircle($this->_sixYiThreeQi, $lastChar));
            
            // 洛書宮序, 陽順陰逆
            $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), 5));
            foreach ($circlePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['yin_gan'] = $revisedSixYiThreeQi[$circleIndex];
            }
        }
        else {
            // “旬首” & “時天干” 開始位置
            $findResult = $this->findHeadGanHourGridIndex('tian');
            $ganHourGridIndex = $findResult[1];
            
            // 以 “時天干” 開始位置， 順時針獲取天盤 => “新順序天盤”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            $tianArr = [];
            foreach ($ganHourCirclePattern as $circleValue) {
                $tianArr[] =  (!empty($this->_palaceResult['grid'][$circleValue]['tian_alias'])?$this->_palaceResult['grid'][$circleValue]['tian_alias']:$this->_palaceResult['grid'][$circleValue]['tian']);
            }

            // “值使門落宮” 開始， 依 “新順序天盤” 順時針繞圈
            $loop = 0;
            $circlePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, (((int)$this->_palaceResult['zhi_shi_index'] == 5)?2:(int)$this->_palaceResult['zhi_shi_index'])));
            foreach ($circlePattern as $circleIndex => $circleValue) {
                $this->_palaceResult['grid'][$circleValue]['yin_gan'] = $tianArr[$loop];
                $loop++;
            }
        }
    }

    private function findHeadGanHourGridIndex($palaceIndex = 'earth') {
        // 尋找“旬首”在地盤或天盤所在宮位， 如原本落在 5.中宮，則尋找偏移後的宮位
        $headGridIndex = 0;
        $lastChar = trim(mb_substr($this->_palaceResult['lead'], -1));
        foreach ($this->_palaceResult['grid'] as $grid) {
            if(md5(trim($lastChar)) == md5(mb_substr(trim($grid[$palaceIndex]), -1))) {
                $headGridIndex = $grid['index'];
            } 
        }
        if(in_array((int)$headGridIndex, [0, 5])) {
            foreach ($this->_palaceResult['grid'] as $grid) {
                if(!empty($grid[$palaceIndex.'_alias'])) {
                    if(((int)$headGridIndex == 5)) {
                        if(md5($lastChar) == md5(mb_substr(trim($grid[$palaceIndex.'_alias']), -1))) {
                            $headGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if(md5($lastChar) == md5(mb_substr(trim($grid[$palaceIndex.'_alias']), 0, 1))) {
                            $headGridIndex = $grid['index'];
                            break;
                        }
                    }
                }
            }
        }

        // 尋找“時天干”在地盤或天盤所在宮位， 如原本落在 5.中宮，則尋找偏移後的宮位
        /*
        特殊説明：
        - 甲子時用戊
        - 甲戌時用己
        - 甲申時用庚
        - 甲午時用辛
        - 甲辰時用壬
        - 甲寅時用癸
        */
        $ganHourGridIndex = 0;
        $ganzhiHour = trim($this->_ganzhiData['ganzhi_hour']);
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            $newGanHour = '';
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                if(md5(trim($ganzhiHour)) == md5(mb_substr(trim($jiazhiKey), 0, 2))) {
                    $newGanHour = mb_substr($jiazhiKey, -1);
                    break;
                }
            }
            $ganzhiHour = $newGanHour;
        }
        foreach ($this->_palaceResult['grid'] as $grid) {
            if(md5(mb_substr($ganzhiHour, 0, 1)) == md5(mb_substr(trim($grid[$palaceIndex]), -1))) {
                $ganHourGridIndex = $grid['index'];
            } 
        }
        
        if(in_array((int)$ganHourGridIndex, [0, 5])) {
            foreach ($this->_palaceResult['grid'] as $grid) {
                if(!empty($grid[$palaceIndex.'_alias'])) {
                    if(((int)$ganHourGridIndex == 5)) {
                        if(md5(mb_substr($ganzhiHour, 0, 1)) == md5(mb_substr(trim($grid[$palaceIndex.'_alias']), -1))) {
                            $ganHourGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if(md5(mb_substr($ganzhiHour, 0, 1)) == md5(mb_substr(trim($grid[$palaceIndex.'_alias']), 0, 1))) {
                            $ganHourGridIndex = $grid['index'];
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
                if(trim($find) == trim($value)) {
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
