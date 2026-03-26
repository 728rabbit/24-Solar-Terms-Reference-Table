<?php
namespace App\Http\Controllers\Web;

use App\Http\Controllers\WebController;

class Home extends WebController {
    
    protected $_ganzhiLib;
    protected $_biziLib;
    
    protected $_ganzhiData;
    protected $_palaceResult = 
    [
        'datetime'          =>  '',
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
        
        'san_yuan_method'   =>  '',
        'san_yuan_chaibu'   =>  [],
        
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
    
    
    // 日干支到元索引的映射表（符頭定局）
    protected $_ganZhiToYuanMap = [
        // 上元
        11 => ['甲子', '乙丑', '丙寅', '丁卯', '戊辰'],
        12 => ['己卯', '庚辰', '辛巳', '壬午', '癸未'],
        13 => ['甲午', '乙未', '丙申', '丁酉', '戊戌'],
        14 => ['己酉', '庚戌', '辛亥', '壬子', '癸丑'],

        // 中元
        21 => ['己巳', '庚午', '辛未', '壬申', '癸酉'],
        22 => ['甲申', '乙酉', '丙戌', '丁亥', '戊子'],
        23 => ['己亥', '庚子', '辛丑', '壬寅', '癸卯'],
        24 => ['甲寅', '乙卯', '丙辰', '丁巳', '戊午'],

        // 下元
        31 => ['甲戌', '乙亥', '丙子', '丁丑', '戊寅'],
        32 => ['己丑', '庚寅', '辛卯', '壬辰', '癸巳'],
        33 => ['甲辰', '乙巳', '丙午', '丁未', '戊申'],
        34 => ['己未', '庚申', '辛酉', '壬戌', '癸亥']
    ];

    // 24 節氣三元表
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
    protected $_zhiRunJieQi = ['大雪', '芒種'];  // 可置閏的節氣
    protected $_zhiRunStartDate = null;         // 置閏開始日期
    protected $_zhiRunEndDate = null;           // 置閏結束日期
    
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
    
    protected $_rumuFixedMin = 
    [
        8   => ['丁'],
        6   => ['乙', '丙']
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
        
        //$testDateTime = '2001-11-28 05:39:00'  // 拆補 + 置閏

  
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
        if(!empty($this->_palaceResult['san_yuan_chaibu'])) {
            echo '<p style="padding:0;margin:0;">'.$this->_palaceResult['san_yuan_method'].' | '.$this->_palaceResult['san_yuan_chaibu'].'</p>';
        }
        
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
    
    // 排盤
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
            $this->_biziLib = (new \App\Libs\calendar\BaZiCalculator(storage_path()));
            $baziResult = $this->_biziLib->calculate($currentDateTime, $this->getParamValue('time_zone', 'hong_kong'));
            if(!empty($baziResult)) {
                //dump($baziResult);
                
                $this->_ganzhiData['ganzhi_year'] = $baziResult['ganzhi_year'];
                $this->_ganzhiData['ganzhi_month'] = $baziResult['ganzhi_month'];
                $this->_ganzhiData['ganzhi_day'] = $baziResult['ganzhi_day'];
                $this->_ganzhiData['ganzhi_hour'] = $baziResult['ganzhi_hour'];
                
                $this->_ganzhiData['lunar_shengxiao'] = $baziResult['lunar']['zodiac'];
                $this->_ganzhiData['lunar_year'] = $baziResult['lunar']['year'];
                $this->_ganzhiData['lunar_month'] = (!empty($baziResult['lunar']['is_leap'])?($baziResult['lunar']['month']*-1):$baziResult['lunar']['month']);
                $this->_ganzhiData['lunar_day'] = $baziResult['lunar']['day'];
                $this->_ganzhiData['lunar_year_chinese'] = $baziResult['lunar']['year_chinese'];
                $this->_ganzhiData['lunar_month_chinese'] = $baziResult['lunar']['month_chinese'];
                $this->_ganzhiData['lunar_day_chinese'] = $baziResult['lunar']['day_chinese'];
                
                $listSolarTerms = $baziResult['jieqi_table'];
                if(!empty($listSolarTerms)) {
                    // 本年夏至
                    $this->_ganzhiData['jieqi_xiazhi'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['夏至'];

                    // 本年冬至
                    $this->_ganzhiData['jieqi_dongzhi'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['冬至']; 
                    
                    // 本年芒種
                    $this->_ganzhiData['jieqi_mangzhong'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['芒種'];
                    
                    // 本年大雪
                    $this->_ganzhiData['jieqi_daxue'] = $listSolarTerms[date('Y', strtotime($currentDateTime))]['大雪'];

                    // 所在前後節氣資料
                    $allSTS = [];
                    foreach ($listSolarTerms as $year => $st) {
                        foreach ($st as $name => $time) {
                            $allSTS[$year.'_'.$name] = $time;
                        }
                    }
                    //dump($allSTS);
                    $this->_ganzhiData['jieqi_range'] = $this->getSolarTermsRange($this->_ganzhiData['datetime_hk'], $allSTS); 
                }
            }
        }
        
        //dump($this->_ganzhiData);
        
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
    
    private function getSolarTermsRange($targetDate, $solarTermsArray) {
        $targetTimestamp = strtotime($targetDate);
        $currentTerm = null;
        $previousTerm = null;
        $nextTerm = null;

        // Convert array to structure with timestamps for easier sorting
        $terms = [];
        foreach ($solarTermsArray as $name => $date) {
            $terms[] = [
                'name' => $name,
                'datetime' => $date,
                'timestamp' => strtotime($date)
            ];
        }

        // Sort by timestamp to ensure correct order
        usort($terms, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        // Find current term (last term before or at target time)
        for ($i = 0; $i < count($terms); $i++) {
            if ($terms[$i]['timestamp'] <= $targetTimestamp) {
                $currentTerm = $terms[$i];
            } else {
                break;
            }
        }

        // If no current term found, use the first term
        if ($currentTerm == null && count($terms) > 0) {
            $currentTerm = $terms[0];
        }

        // Find current term index
        $currentIndex = null;
        foreach ($terms as $index => $term) {
            if ($term['name'] == $currentTerm['name'] && 
                $term['datetime'] == $currentTerm['datetime']) {
                $currentIndex = $index;
                break;
            }
        }

        // Get previous and next terms
        if ($currentIndex !== null) {
            if (isset($terms[$currentIndex - 1])) {
                $previousTerm = $terms[$currentIndex - 1];
            }

            if (isset($terms[$currentIndex + 1])) {
                $nextTerm = $terms[$currentIndex + 1];
            }
        }

        return 
        [    
            'previous'  =>  $previousTerm,
            'current'   =>  $currentTerm,
            'next'      =>  $nextTerm
        ];
    }

    protected function setDunIndex($method) {
        $currentDateTime = $this->_ganzhiData['datetime_hk'];
        $jieqiXiazhi = $this->_ganzhiData['jieqi_xiazhi']; 
        $jieqiDongzhi = $this->_ganzhiData['jieqi_dongzhi'];
        
        // 夏至 ~ 冬至 時間内，為陰
        if((strtotime($currentDateTime) >= strtotime($jieqiXiazhi)) && (strtotime($currentDateTime) < strtotime($jieqiDongzhi))) {
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
    
    // 拆補法： 從交節時間算起至下一個交節時間為止一律使用本節氣三元起局用事，就是說一個節氣之內不得混雜使用其它節氣的局象起局。
    private function calculateChaiBuMethod() {
        $this->_palaceResult['san_yuan_method'] = 'chaibu';
        $this->_yyDunNumber = 9;
        if(!empty($this->_ganzhiData['jieqi_range'])) {
            $currentJieqiName = explode('_', $this->_ganzhiData['jieqi_range']['current']['name']);
            
            // 根據日天干，查找其索引
            $sanYuanIndex = 0;
            foreach ($this->_ganZhiToYuanMap as $key => $arrValues) {
                if(in_array($this->_ganzhiData['ganzhi_day'], $arrValues)) {
                    if(in_array($key, [21, 22, 23, 24])) {
                        $sanYuanIndex = 1;
                    }
                    else if(in_array($key, [31, 32, 33, 34])) {
                        $sanYuanIndex = 2;
                    }
                    break;
                }
            }
            
            $sanYuanOrderNumber = [0 => '上', 1 => '中', 2 => '下'];
            $this->_palaceResult['san_yuan_chaibu'] = implode('-', $currentJieqiName).'-'.$sanYuanOrderNumber[$sanYuanIndex];
            $this->_yyDunNumber = $this->_jieqiSanYuanTable[$currentJieqiName[1]][$sanYuanIndex];
        }
    }

    // 置閏法
    private function calculateZhiRunMethod() {
        $this->_palaceResult['san_yuan_method'] = 'zhirun'; 
    }

    // 陰盤 - 取局數方法：年支序數 + 舊曆月數 + 舊曆日數 + 時支序數，總數以 9 除之，取餘數。 其餘數必少於 9，整除作 9 數。
    private function calculateYinPanMethod() {
        $this->_palaceResult['san_yuan_method'] = 'yinpan';
        
        $ganzhiYear = mb_substr($this->_ganzhiData['ganzhi_year'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if($ganzhiYear == $diZhi) {
                $ganzhiYear = (int)$diZhiKey;
                break;
            }
        }

        $lunarMonth = (int)$this->_ganzhiData['lunar_month'];
        $lunarDay = (int)$this->_ganzhiData['lunar_day'];

        $ganzhiHour = mb_substr($this->_ganzhiData['ganzhi_hour'], -1);
        foreach ($this->_twelveDiZhi as $diZhiKey => $diZhi) {
            if($ganzhiHour == $diZhi) {
                $ganzhiHour = (int)$diZhiKey;
                break;
            }
        }

        $this->_yyDunNumber = ($ganzhiYear + abs($lunarMonth) + $lunarDay + $ganzhiHour) % 9;
        if($this->_yyDunNumber == 0) {
            $this->_yyDunNumber = 9;
        }
    }

    
    // 排九宮格
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
                if($ganzhiHour == $child) {
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
            if($lastChar == $grid['earth']) {
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
                    if($this->_palaceResult['ganzhi_hour'] == $child) {
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
                if($revisedNiceStar[$circleIndex] == '芮') {
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
            if($findZhiFu == $grid['star']) {
                $this->_palaceResult['zhi_fu_index'] = $grid['index'];
                break;
            }
        }
        
        // 比較 值符地盤 和 “時天干” 是否有偏移
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            $newGanHour = '';
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                if($ganzhiHour == mb_substr($jiazhiKey, 0, 2)) {
                    $newGanHour = mb_substr($jiazhiKey, -1);
                    break;
                }
            }
            $ganzhiHour = $newGanHour;
        }
        if(!empty($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias'])) {
            if(mb_substr($ganzhiHour, 0, 1) == mb_substr($this->_palaceResult['grid'][$this->_palaceResult['zhi_fu_index']]['earth_alias'], -1)) {  
               $this->_palaceResult['zhi_fu_index'] = 5;
            }
        }
        
        // 值使所在宮位
        foreach ($this->_palaceResult['grid'] as $grid) {
            $findZhiShi = $this->_palaceResult['zhi_shi'];
            if($findZhiShi == $grid['gate']) {
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
            // 陰盤全入墓， 陽盤只有三奇入墓
            $rumuFixedMap = ($this->_palaceResult['san_yuan_method'] == 'zhirun')?$this->_rumuFixed:$this->_rumuFixedMin;
            if(!empty($rumuFixedMap[$grid['index']])) {
                foreach (['earth', 'earth_alias', 'tian', 'tian_alias'] as $findIndex) {
                    if(!empty($grid[$findIndex])) {
                        $arr = array_unique(array_filter(mb_str_split($grid[$findIndex])));
                        foreach ($arr as $char) {
                            if(in_array($char, $rumuFixedMap[$grid['index']])) {
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

        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
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
        $lastChar = mb_substr($this->_palaceResult['lead'], -1);
        foreach ($this->_palaceResult['grid'] as $grid) {
            if($lastChar == mb_substr($grid[$palaceIndex], -1)) {
                $headGridIndex = $grid['index'];
            } 
        }
        if(in_array((int)$headGridIndex, [0, 5])) {
            foreach ($this->_palaceResult['grid'] as $grid) {
                if(!empty($grid[$palaceIndex.'_alias'])) {
                    if(((int)$headGridIndex == 5)) {
                        if($lastChar == mb_substr($grid[$palaceIndex.'_alias'], -1)) {
                            $headGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if($lastChar == mb_substr($grid[$palaceIndex.'_alias'], 0, 1)) {
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
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            $newGanHour = '';
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                if($ganzhiHour == mb_substr($jiazhiKey, 0, 2)) {
                    $newGanHour = mb_substr($jiazhiKey, -1);
                    break;
                }
            }
            $ganzhiHour = $newGanHour;
        }
        foreach ($this->_palaceResult['grid'] as $grid) {
            if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$palaceIndex], -1)) {
                $ganHourGridIndex = $grid['index'];
            } 
        }
        
        if(in_array((int)$ganHourGridIndex, [0, 5])) {
            foreach ($this->_palaceResult['grid'] as $grid) {
                if(!empty($grid[$palaceIndex.'_alias'])) {
                    if(((int)$ganHourGridIndex == 5)) {
                        if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$palaceIndex.'_alias'], -1)) {
                            $ganHourGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$palaceIndex.'_alias'], 0, 1)) {
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
