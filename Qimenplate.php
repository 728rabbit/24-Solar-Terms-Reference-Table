<?php
namespace App\Http\Controllers\Web;

class Qimenplate {
    
    protected $_ganzhiLib;
    protected $_biziLib;
    
    protected $_ganzhiData;
    protected $_plateResult = 
    [
        'datetime'              =>  '',
        'time_zone'             =>  'hong_kong',
        'datetime_hk'           =>  '',
        'lunar_shengxiao'       =>  '',
        'lunar_year'            =>  0,
        'lunar_month'           =>  0,
        'lunar_day'             =>  0,
        'lunar_year_chinese'    => '',
        'lunar_month_chinese'   => '',
        'lunar_day_chinese'     => '',
        'ganzhi_year'           =>  '',
        'ganzhi_month'          =>  '',
        'ganzhi_day'            =>  '',
        'ganzhi_hour'           =>  '',
        
        'san_yuan_method'       =>  '',
        'san_yuan_remark'       =>  [],
        
        'dun_index'             =>  0,   // 1.陽 or 2.陰
        'dun_number'            =>  0,   // 局數
        'lead'                  =>  '',  // 旬首
        'zhi_ori_index'         =>  '',  // 原值符/使宮位
        'zhi_fu'                =>  '',  // 值符
        'zhi_shi'               =>  '',  // 值使宮位,
        'zhi_fu_index'          =>  '',  // 值符宮位
        'zhi_shi_index'         =>  '',
        
        // 九宮格
        'grid'                  =>
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
        
        'kong_wang'             =>  [],
        'yi_ma'                 =>  [], 
        
        'highlight_method'      =>  'self',    // 自占
        'highlight_type'        =>  'event',   // 事情
        'highlight_index_1'     =>  'day',     // 日天干
        'highlight_index_2'     =>  'hour',    // 時天干
        'highlight_transform'   =>  0,         // 陰陽轉換
        'highlight_grid'        =>  [],
        'highlight_grid_shift'  =>  0,
        
        'good_bad_references'   =>  []
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
    
    // 宮逼
    protected $_gongBiFixed =
    [
        4   => ['生', '死'],
        9   => ['開', '驚'],
        2   => ['休'],
        
        3   => ['生', '死'],
        7   => ['傷', '杜'],
        
        8   => ['休'],
        1   => ['景'],
        6   => ['傷', '杜']
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
    
    public function doAnalyze($currentDateTime, $options = []) {
        ini_set('max_execution_time', 0);
        
        /****************** 主要排盤功能 ******************/
        // 天干地支
        $this->getDanZhi($currentDateTime, (!empty($options['zone'])?$options['zone']:'hong_kong'));
        
        // 定局
        $this->setDunIndex((!empty($options['method'])?$options['method']:3));
        
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
        
        // 宮逼
        $this->setGongBi();
        
        // 擊刑
        $this->setJiXing();
        
        // 入墓
        $this->setRuMu();

        // 隱干
        $this->setYinGan();
        
        /****************** 額外轉宮功能 ******************/
        // 1. 拆補 | 2. 置閏 | 3. 陰盤 
        $this->_plateResult['highlight_method'] = (!empty($options['highlight_method'])?$options['highlight_method']:'self');
        
        // 問事類型
        $this->_plateResult['highlight_type'] = (!empty($options['highlight_type'])?$options['highlight_type']:'event');
        
        // 參照天干
        $this->_plateResult['highlight_index_1'] = (!empty($options['highlight_index_1'])?$options['highlight_index_1']:'day');
        $this->_plateResult['highlight_index_2'] = (!empty($options['highlight_index_2'])?$options['highlight_index_2']:'hour');
        
        // 是否陰陽轉換
        $this->_plateResult['highlight_transform'] = (!empty($options['highlight_transform'])?1:0);

        // 轉宮
        $this->setHighlight();
 
        /****************** 額外吉凶格 ******************/
        $this->analyzeGoodBad();

        // 返回結果
        return $this->_plateResult;
    }

    // 計算天干地支
    private function getDanZhi($currentDateTime, $zone = 'hong_kong') {
        $this->_ganzhiLib = (new \App\Libs\calendar\GanZhi());
        $this->_ganzhiData = $this->_ganzhiLib->convert($currentDateTime, $zone);
        
        //dump($this->_ganzhiData);
        
        // overwirte if need
        if(true) {
            $this->_biziLib = (new \App\Libs\calendar\BaZiCalculator(storage_path()));
            $baziResult = $this->_biziLib->calculate($currentDateTime, $zone);
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
                    $this->_ganzhiData['jieqi_table'] = $allSTS;
                    $this->_ganzhiData['jieqi_range'] = $this->getSolarTermsRange($this->_ganzhiData['datetime_hk'], $allSTS); 
                }
            }
        }
        
        //dump($this->_ganzhiData);
        
        $this->_plateResult['datetime'] = $currentDateTime;
        $this->_plateResult['time_zone'] = $zone;
        $this->_plateResult['datetime_hk'] = $this->_ganzhiData['datetime_hk'];
        $this->_plateResult['lunar_shengxiao'] = $this->_ganzhiData['lunar_shengxiao'];
        $this->_plateResult['lunar_year'] = $this->_ganzhiData['lunar_year'];
        $this->_plateResult['lunar_month'] = $this->_ganzhiData['lunar_month'];
        $this->_plateResult['lunar_day'] = $this->_ganzhiData['lunar_day'];
        $this->_plateResult['lunar_year_chinese'] = $this->_ganzhiData['lunar_year_chinese'];
        $this->_plateResult['lunar_month_chinese'] = $this->_ganzhiData['lunar_month_chinese'];
        $this->_plateResult['lunar_day_chinese'] = $this->_ganzhiData['lunar_day_chinese'];
        $this->_plateResult['ganzhi_year'] = $this->_ganzhiData['ganzhi_year'];
        $this->_plateResult['ganzhi_month'] = $this->_ganzhiData['ganzhi_month'];
        $this->_plateResult['ganzhi_day'] = $this->_ganzhiData['ganzhi_day'];
        $this->_plateResult['ganzhi_hour'] = $this->_ganzhiData['ganzhi_hour'];
    }
    
    // 根據日期時間，獲得當前所在節氣(包括其前後節氣)
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

    // 根據不同方法來定局
    private function setDunIndex($method) {
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
        
        // 1. 拆補 | 2. 置閏 | 3. 陰盤
        if($method == 1) {
            $this->calculateChaiBuMethod();
        }
        elseif($method == 2) {
            $this->calculateZhiRunMethod($this->_ganzhiData['jieqi_table']);
        }
        else {
            $this->calculateYinPanMethod();
        }
        
        $this->_plateResult['dun_index'] = $this->_yyDunIndex;
        $this->_plateResult['dun_number'] = $this->_yyDunNumber;
    }
    
    // 拆補法： 從交節時間算起至下一個交節時間為止一律使用本節氣三元起局用事，就是說一個節氣之內不得混雜使用其它節氣的局象起局。
    private function calculateChaiBuMethod() {
        $this->_plateResult['san_yuan_method'] = 'chaibu';
        $this->_yyDunNumber = 9;
        if(!empty($this->_ganzhiData['jieqi_range'])) {
            $currentJieqiName = explode('_', $this->_ganzhiData['jieqi_range']['current']['name']);
            if(!empty($currentJieqiName)) {
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
                $this->_plateResult['san_yuan_remark'] = implode('-', $currentJieqiName).'-'.$sanYuanOrderNumber[$sanYuanIndex];
                $this->_yyDunNumber = $this->_jieqiSanYuanTable[$currentJieqiName[1]][$sanYuanIndex];
            }
        }
    }

    // 置閏法：依據 “符頭” 與 “節氣” 的交接狀況來動態定局
    private function calculateZhiRunMethod($allSTS) {
        $this->_plateResult['san_yuan_method'] = 'zhirun';
        
        // 根據日天干，查找其符頭
        /*
        11 => ['甲子', '乙丑', '丙寅', '丁卯', '戊辰'],
        21 => ['己巳', '庚午', '辛未', '壬申', '癸酉'],
        31 => ['甲戌', '乙亥', '丙子', '丁丑', '戊寅'],

        12 => ['己卯', '庚辰', '辛巳', '壬午', '癸未'],
        22 => ['甲申', '乙酉', '丙戌', '丁亥', '戊子'],
        32 => ['己丑', '庚寅', '辛卯', '壬辰', '癸巳'],

        13 => ['甲午', '乙未', '丙申', '丁酉', '戊戌'],
        23 => ['己亥', '庚子', '辛丑', '壬寅', '癸卯'],
        33 => ['甲辰', '乙巳', '丙午', '丁未', '戊申'],

        14 => ['己酉', '庚戌', '辛亥', '壬子', '癸丑'],
        24 => ['甲寅', '乙卯', '丙辰', '丁巳', '戊午'],
        34 => ['己未', '庚申', '辛酉', '壬戌', '癸亥']
        */
        $fuTou = '甲子';
        $fuTouDateDiff = 0;
        foreach ($this->_ganZhiToYuanMap as $key => $arrValues) {
            if(in_array($this->_ganzhiData['ganzhi_day'], $arrValues)) {
                if(in_array($key, [21, 22, 23, 24])) {
                    $fuTou = $this->_ganZhiToYuanMap[(10 + ($key%20))][0];
                    $fuTouDateDiff = array_search($this->_ganzhiData['ganzhi_day'], $arrValues) + 5;
                }
                else if(in_array($key, [31, 32, 33, 34])) {
                    $fuTou = $this->_ganZhiToYuanMap[(10 + ($key%30))][0];
                    $fuTouDateDiff = array_search($this->_ganzhiData['ganzhi_day'], $arrValues) + 10;
                }
                else {
                    $fuTou = $this->_ganZhiToYuanMap[(10 + ($key%10))][0];
                    $fuTouDateDiff = array_search($this->_ganzhiData['ganzhi_day'], $arrValues);
                }
                break;
            }
        }
        $fuTouDate = date('Y-m-d H:i:s', (strtotime($this->_plateResult['datetime_hk']) - $fuTouDateDiff*24*3600));
        dump($fuTou.' => '.$fuTouDateDiff.' => '.$fuTouDate.' | '.$this->_plateResult['datetime_hk']);
        
        // “符頭” 所在 “節氣”
        $currentJieqiName  = [];
        $fuTouJieqiRange = $this->getSolarTermsRange($fuTouDate, $allSTS);
        if($fuTouDateDiff <= 9) {
            $currentJieqiName = explode('_', $fuTouJieqiRange['next']['name']);
        }

        if(!empty($currentJieqiName)) {
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
            $this->_plateResult['san_yuan_remark'] = implode('-', $currentJieqiName).'-'.$sanYuanOrderNumber[$sanYuanIndex].' | '.(($fuTouDateDiff%5) + 1);
            $this->_yyDunNumber = $this->_jieqiSanYuanTable[$currentJieqiName[1]][$sanYuanIndex];


            
        }
        
        dump($fuTouJieqiRange);
        dump($allSTS);
    }

    // 陰盤 - 取局數方法：年支序數 + 舊曆月數 + 舊曆日數 + 時支序數，總數以 9 除之，取餘數。 其餘數必少於 9，整除作 9 數。
    private function calculateYinPanMethod() {
        $this->_plateResult['san_yuan_method'] = 'yinpan';
        
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
   
    // 排地盤九宮格
    private function setEarth() {
        // 以 “局數” 開始點，按洛書宮序(陽順陰逆)， 排 “戊己庚辛壬癸丁丙乙”
        $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), $this->_yyDunNumber));
        foreach ($this->_sixYiThreeQi as $sixThreeKey => $sixThree) {
            $plateIndex = $circlePattern[$sixThreeKey];
            $this->_plateResult['grid'][$plateIndex]['earth'] = $sixThree;
        }
        
        // 5.中宮 合并到 2.坤 
        $this->_plateResult['grid'][2]['earth_alias'] =  ($this->_plateResult['grid'][2]['earth'].$this->_plateResult['grid'][5]['earth']);
    }
    
    // 查旬首
    private function setLead() {
        // “旬首” = “時天干” 所在的 “六十甲子” 索引頭 
        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
            foreach ($jiazhi as $child) {
                if($ganzhiHour == $child) {
                    $this->_plateResult['lead'] = $jiazhiKey;
                    break;
                }
            }
            if(!empty($this->_plateResult['lead'])) {
                break;
            }
        }
    }
    
    // 查值符使
    private function setZhiFuShi() {
        // 根據 “旬首” 確定原宮位對應的 符 + 使
        // 例如 “甲午辛”， “旬首” 為 “辛”， 落在 4.巽宮， 其對應原宮位則為 “天輔” + “杜門”
        $lastChar = mb_substr($this->_plateResult['lead'], -1);
        $zhiFuShiIndex = 0;
        foreach ($this->_plateResult['grid'] as $grid) {
            if($lastChar == $grid['earth']) {
                $zhiFuShiIndex = $grid['index']; 
                break;
            }
        }
        if(!empty($zhiFuShiIndex)) {
            $this->_plateResult['zhi_ori_index'] = $zhiFuShiIndex;
            $this->_plateResult['zhi_fu'] = $this->_startAndGateOri[$zhiFuShiIndex]['star'];
            $this->_plateResult['zhi_shi'] = $this->_startAndGateOri[$zhiFuShiIndex]['gate'];
            
            // 落在 5.中宮， 看 2.坤宮對應的原門
            if(empty($this->_plateResult['zhi_shi'])) {
                $this->_plateResult['zhi_shi'] = '死';
            }
        }
    }

    // 排天盤九宮格
    private function setTian() {
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
                    $this->_plateResult['grid'][$circleValue]['earth'],
                    (!empty($this->_plateResult['grid'][$circleValue]['earth_alias'])?$this->_plateResult['grid'][$circleValue]['earth_alias']:'')
                ]));
            }

            // “時天干” 地盤所在宮位開始， 順時針繞圈排 “新順序地盤”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            $loop = 0;
            foreach ($ganHourCirclePattern as $circleValue) {
                $earthValue = explode('|', $earthArr[$loop]);
                $this->_plateResult['grid'][$circleValue]['tian'] = $earthValue[0];
                if(!empty($earthValue[1])) {
                    $this->_plateResult['grid'][$circleValue]['tian_alias'] = $earthValue[1];
                }
                $loop++;
            }
        }
        
        // 複製地盤 5.中宮 到 天盤 5.中宮
        $this->_plateResult['grid'][5]['tian'] = $this->_plateResult['grid'][5]['earth'];
    }
    
    // 排八門
    private function setGate() {
        // 原 “值使(門)” 所在宮位
        $zhishiGridIndex = $this->_plateResult['zhi_ori_index'];
        
        if($zhishiGridIndex > 0) {
            // 尋找 “時天干” 在 “六十甲子” 位置
            $shiftIndex = 0;
            foreach ($this->_sixtyJiazi as $jiazhiKey => $jiazhi) {
                foreach ($jiazhi as $jiazhiChildKey => $child) {
                    if($this->_plateResult['ganzhi_hour'] == $child) {
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
            $revisedEightGate = $this->arrayReIndex($this->arrayCircle($this->_eightGate, $this->_plateResult['zhi_shi'])); 
            
            // 以“平移位” 開始， 順時針繞圈排 “新順序八門”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $shiftGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_plateResult['grid'][$circleValue]['gate'] = $revisedEightGate[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_plateResult['grid'][5]['gate'] = '';
    }
    
    // 排九星
    private function setStar() {
        // “旬首” & “時天干” 開始位置
        $findResult = $this->findHeadGanHourGridIndex();
        $ganHourGridIndex = $findResult[1];

        $dependIndex = 0;
        if($ganHourGridIndex > 0) {
            $findZhiFu = $this->_plateResult['zhi_fu'];
            // “天禽星” 寄宮 “天芮星”
            if($findZhiFu == '禽') {
                $findZhiFu = '芮';
            }
            
            // 以 “值符” 開始， 重新順序九星 => “新順序九星”
            $revisedNiceStar = $this->arrayReIndex($this->arrayCircle($this->_niceStar, $findZhiFu));
            
            // 由 “時天干” 在地盤所在宮位開始，順時針繞圈排 “新順序八門”
            $ganHourCirclePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, $ganHourGridIndex));
            foreach ($ganHourCirclePattern as $circleIndex => $circleValue) {
                $this->_plateResult['grid'][$circleValue]['star'] = $revisedNiceStar[$circleIndex];
                if($revisedNiceStar[$circleIndex] == '芮') {
                    $dependIndex = $circleValue;
                }
            }
        }

        // 5.中宮 默認為“禽”
        $this->_plateResult['grid'][5]['star'] = '禽';
        
        // “天禽星” 寄宮 “天芮星”
        if(!empty($dependIndex)) {
            $this->_plateResult['grid'][$dependIndex]['star_alias'] = '芮禽';
        }
    }
    
    // 排八神
    private function setShen() {
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
                $this->_plateResult['grid'][$circleValue]['shen'] = $this->_eightShen[$circleIndex];
            }
        }
        
        // 5.中宮 默認為空白
        $this->_plateResult['grid'][5]['shen'] = '';
    }
    
    // 查值符使落在什麽宮位
    private function setZhiFuShiIndex() {
        foreach ($this->_plateResult['grid'] as $grid) {
            $findZhiFu = $this->_plateResult['zhi_fu'];
            if($findZhiFu == '禽') {
                $findZhiFu = '芮';
            }
            if($findZhiFu == $grid['star']) {
                $this->_plateResult['zhi_fu_index'] = $grid['index'];
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
        if(!empty($this->_plateResult['grid'][$this->_plateResult['zhi_fu_index']]['earth_alias'])) {
            if(mb_substr($ganzhiHour, 0, 1) == mb_substr($this->_plateResult['grid'][$this->_plateResult['zhi_fu_index']]['earth_alias'], -1)) {  
               $this->_plateResult['zhi_fu_index'] = 5;
            }
        }
        
        // 值使所在宮位
        foreach ($this->_plateResult['grid'] as $grid) {
            $findZhiShi = $this->_plateResult['zhi_shi'];
            if($findZhiShi == $grid['gate']) {
                $this->_plateResult['zhi_shi_index'] = $grid['index'];
                break;
            }
        }
    }
    
    // 查空亡
    private function setKongWang() {
        // 根據 “旬首” 確定其 “空亡”
        $kongWang = $this->_sixtyJiaziKongWang[$this->_plateResult['lead']];
        
        // 速查固定時辰對照表
        foreach (mb_str_split($kongWang) as $char) {
            foreach ($this->_shiChenFixed as $shiChenKey => $shiChen) {
                if(!empty($shiChen) && in_array($char, $shiChen)) {
                    $this->_plateResult['kong_wang'][] = $shiChenKey;
                }
            }
        }
        $this->_plateResult['kong_wang'] = array_unique($this->_plateResult['kong_wang']);
    }

    // 查驛馬
    private function setYiMa() {
        /* 驛馬速查表
        申子辰 時 → 寅
        寅午戌 時 → 申
        巳酉丑 時 → 亥
        亥卯未 時 → 巳 */
        
        // 根據 “時支” 確定其 “驛馬”
        $lastChar = mb_substr($this->_plateResult['ganzhi_hour'], -1);
        
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
                    $this->_plateResult['yi_ma'][] = $shiChenKey;
                }
            }
        }
        $this->_plateResult['yi_ma'] = array_unique($this->_plateResult['yi_ma']);
    }
    
    // 查門迫
    private function setMenPo() {
        // 速查門迫對照表
        foreach ($this->_plateResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_menpoFixed[$grid['index']])) {
                if(in_array($grid['gate'], $this->_menpoFixed[$grid['index']])) {
                    $this->_plateResult['grid'][$gridKey]['men_po'] = $grid['gate'];
                }
            }
        }
    }
    
    // 查宮逼
    private function setGongBi() {
        // 速查宮逼對照表
        foreach ($this->_plateResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_gongBiFixed[$grid['index']])) {
                if(in_array($grid['gate'], $this->_gongBiFixed[$grid['index']])) {
                    $this->_plateResult['grid'][$gridKey]['goog_bi'] = $grid['gate'];
                }
            }
        }
    }
    
    // 查擊刑
    private function setJiXing() {
        // 速查擊刑對照表
        foreach ($this->_plateResult['grid'] as $gridKey => $grid) {
            if(!empty($this->_jixingFixed[$grid['index']])) {
                foreach (['earth', 'earth_alias', 'tian', 'tian_alias'] as $findIndex) {
                    if(!empty($grid[$findIndex])) {
                        $arr = array_unique(array_filter(mb_str_split($grid[$findIndex])));
                        foreach ($arr as $char) {
                            if(in_array($char, $this->_jixingFixed[$grid['index']])) {
                                if(in_array($findIndex, ['earth', 'earth_alias'])) {
                                    $this->_plateResult['grid'][$gridKey]['ji_xing_earth'][] = $char;
                                }
                                else {
                                    $this->_plateResult['grid'][$gridKey]['ji_xing_tian'][] = $char;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 查入墓
    private function setRuMu() {
        // 速查入墓對照表
        foreach ($this->_plateResult['grid'] as $gridKey => $grid) {
            // 陰盤全入墓， 陽盤只有三奇入墓
            $rumuFixedMap = ($this->_plateResult['san_yuan_method'] == 'yinpan')?$this->_rumuFixed:$this->_rumuFixedMin;
            if(!empty($rumuFixedMap[$grid['index']])) {
                foreach (['earth', 'earth_alias', 'tian', 'tian_alias'] as $findIndex) {
                    if(!empty($grid[$findIndex])) {
                        $arr = array_unique(array_filter(mb_str_split($grid[$findIndex])));
                        foreach ($arr as $char) {
                            if(in_array($char, $rumuFixedMap[$grid['index']])) {
                                if(in_array($findIndex, ['earth', 'earth_alias'])) {
                                    $this->_plateResult['grid'][$gridKey]['ru_mu_earth'][] = $char;
                                }
                                else {
                                    $this->_plateResult['grid'][$gridKey]['ru_mu_tian'][] = $char;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 排最外圍隱(天)干
    private function setYinGan() {
        // 固定時辰
        foreach ($this->_plateResult['grid'] as $grid) {
            $this->_plateResult['grid'][$grid['index']]['shi_chen'] = $this->_shiChenFixed[$grid['index']];
        }

        $ganzhiHour = $this->_ganzhiData['ganzhi_hour'];
        // 遇 “甲”， 把“旬首” 放中宮， 按洛書宮序(陽順陰逆)， 排 “旬首” 開始的 “戊己庚辛壬癸丁丙乙”
        if(in_array($ganzhiHour, ['甲子', '甲戌', '甲申', '甲午', '甲辰', '甲寅'])) {
            // 根據 “旬首”， 重新順序 “戊己庚辛壬癸丁丙乙”
            $lastChar = mb_substr($this->_plateResult['lead'], -1);
            $revisedSixYiThreeQi = $this->arrayReIndex($this->arrayCircle($this->_sixYiThreeQi, $lastChar));
            
            // 洛書宮序, 陽順陰逆
            $circlePattern = $this->arrayReIndex($this->arrayCircle((($this->_yyDunIndex == 1)? $this->_ascPattern: $this->_descPattern), 5));
            
            foreach ($circlePattern as $circleIndex => $circleValue) {
                $this->_plateResult['grid'][$circleValue]['yin_gan'] = $revisedSixYiThreeQi[$circleIndex];
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
                $tianArr[] =  (!empty($this->_plateResult['grid'][$circleValue]['tian_alias'])?$this->_plateResult['grid'][$circleValue]['tian_alias']:$this->_plateResult['grid'][$circleValue]['tian']);
            }
            
            // “值使門落宮” 開始， 依 “新順序天盤” 順時針繞圈
            $loop = 0;
            $circlePattern = $this->arrayReIndex($this->arrayCircle($this->_gridCircle, (((int)$this->_plateResult['zhi_shi_index'] == 5)?2:(int)$this->_plateResult['zhi_shi_index'])));
            foreach ($circlePattern as $circleIndex => $circleValue) {
                $this->_plateResult['grid'][$circleValue]['yin_gan'] = $tianArr[$loop];
                $loop++;
            }
        }
    }
    
    // 轉宮
    private function setHighlight() {
        // 標記天干： 
        // 1. 當時人 - 現場使用 “日干”, 隔空使用 “月干”
        // 2. 查詢的事情 - 使用 “時干”
        // 3. 問關係
        //    3.1. 愛情： 天干相合 - 甲己合、乙庚合、丙辛合、丁壬合、戊癸合
        //    3.2. 非愛情： 長輩使用 “年干” | 同輩現場使用 “月干”, 隔空使用 “日干” | 晚輩使用 “時干”
        
        // 天盤干遇「甲」
        // 陽盤用「旬首甲」：甲子（戊）、甲戌（己）、甲申（庚）、甲午（辛）、甲辰（壬）、甲寅（癸）
        // 陰盤用「八神的“符”」
        
        // 尋找 9 宮格位置
        $ganValue1 = $this->_plateResult['ganzhi_'.$this->_plateResult['highlight_index_1']];
        $ganValue1 = mb_substr($ganValue1, 0, 1);
        
        $ganValue2 = $this->_plateResult['ganzhi_'.$this->_plateResult['highlight_index_2']];
        $ganValue2 = mb_substr($ganValue2, 0, 1);
         
        if($this->_plateResult['highlight_type'] == 'love') {
            // 天干相合
            $mixed = [['甲', '己'], ['乙', '庚'], ['丙', '辛'], ['丁', '壬'], ['戊', '癸']];
            
            // 根據組合， 重新定義 $ganValue2
            foreach ($mixed as $pair) {
                if(in_array($ganValue1, $pair)) {
                    $ganValue2 = (($pair[0] == $ganValue1)?$pair[1]:$pair[0]);
                    break;   
                }
            }
        }
        
        if($this->_plateResult['highlight_transform'] == 1) {
            // 陰陽互換
            $mixed = [['甲', '乙'], ['丙', '丁'], ['戊', '己'], ['庚', '辛'], ['壬', '癸']];
            
            // 根據組合， 重新定義 $ganValue2
            foreach ($mixed as $pair) {
                if(in_array($ganValue2, $pair)) {
                    $ganValue2 = (($pair[0] == $ganValue2)?$pair[1]:$pair[0]);
                    break;  
                }
            }
        }
        
        // 輸出結果： 位置 | 天干值 - 0:第一個字 1: 第二個字 2: 八神“符”
        foreach ([$ganValue1, $ganValue2] as $vKey => $ganValue) {
            // 尋找 八神的“符” 在什麽宮位
            if($this->_plateResult['san_yuan_method'] == 'yinpan' && $ganValue == '甲') {
                foreach ($this->_plateResult['grid'] as $key => $grid) {
                    if($grid['shen'] == '符') {
                        $this->_plateResult['grid'][$key]['highlight_'.($vKey+1)] = implode('|', [2, $ganValue]);
                        $this->_plateResult['highlight_grid'][] = implode('|', [$key, $ganValue]);
                        break;
                    }
                }
            }
            else {
                // 旬首
                if($ganValue == '甲') {
                    $ganValue = mb_substr($this->_plateResult['lead'], -1);
                }
                foreach ($this->_plateResult['grid'] as $key => $grid) {
                    if((int)$grid['index'] != 5) {
                        $inCenter = ($ganValue == $this->_plateResult['grid'][5]['tian']);
                        if($inCenter && !empty($grid['tian_alias']) && (mb_substr($grid['tian_alias'], -1) == $ganValue)) {
                            $this->_plateResult['grid'][$key]['highlight_'.($vKey+1)] = implode('|', [1, $ganValue]);
                            $this->_plateResult['highlight_grid'][] = implode('|', [$key, $ganValue]);
                            break;
                        }
                        else if(mb_substr($grid['tian'], -1) == $ganValue) {
                            $this->_plateResult['grid'][$key]['highlight_'.($vKey+1)] = implode('|', [0, $ganValue]);
                            $this->_plateResult['highlight_grid'][] = implode('|', [$key, $ganValue]);
                            break;
                        }
                    }
                }
            }
        }
        
        // 轉宮
        if(!empty($this->_plateResult['highlight_grid'][0]) && !empty($this->_plateResult['highlight_grid'][1])) {
            $first = explode('|', $this->_plateResult['highlight_grid'][0]);
            $second = explode('|', $this->_plateResult['highlight_grid'][1]);
            if(empty($this->_plateResult['kong_wang'])) {
                $this->_plateResult['kong_wang'] = [-1];
            }

            // 問者用神，落宮遇空亡不轉宮
            if(!in_array((int)$first[0], $this->_plateResult['kong_wang'])) {
                // 現場占：深挖轉宮
                $shiftTable = 
                [
                    4 => 7, // 4巽 → 7兌
                    9 => 6, // 9離 → 6乾
                    2 => 4, // 2坤 → 4巽

                    3 => 9, // 3震 → 9離
                    7 => 1, // 7兌 → 1坎

                    8 => 3, // 8艮 → 3震
                    1 => 2, // 1坎 → 2坤
                    6 => 8, // 6乾 → 8艮
                ];

                // 遙空斷：飄移轉宮
                if($this->_plateResult['highlight_method'] != 'self') {
                    $shiftTable = 
                    [
                        4 => 7, // 4巽 → 2坤
                        9 => 6, // 9離 → 3震
                        2 => 4, // 2坤 → 1坎

                        3 => 9, // 3震 → 8艮
                        7 => 1, // 7兌 → 4巽

                        8 => 3, // 8艮 → 6乾
                        1 => 2, // 1坎 → 7兌
                        6 => 8, // 6乾 → 9離
                    ];
                } 
                
                // 問事用神，落宮遇空亡，自動轉宮
                if(in_array((int)$second[0], $this->_plateResult['kong_wang'])) {
                    $this->_plateResult['highlight_grid_shift'] = $shiftTable[$second[0]];
                }
                
                // 同宮轉宮
                if((int)$first[0] == (int)$second[0] && empty($this->_plateResult['highlight_grid_shift'])) {
                    $this->_plateResult['highlight_grid_shift'] = $shiftTable[$second[0]];
                }
            }
        }
    }
    
    // 查吉凶格
    private function analyzeGoodBad() {
        $goodBadReferences = [];
        foreach ($this->_plateResult['grid'] as $grid) {
            if((int)$grid['index'] == 5) {
                continue;
            }
            
            // 初始化
            $gridIndex = $grid['index'];
            if(empty($goodBadReferences[$gridIndex])) {
                $goodBadReferences[$gridIndex] = [];
            }
            
            foreach (['', '_alias'] as $suffix) {
                $gridTian = (!empty($grid['tian'.$suffix])?mb_substr($grid['tian'.$suffix], -1):'#');
                $gridEarth = (!empty($grid['earth'.$suffix])?mb_substr($grid['earth'.$suffix], -1):'#');

                // 青龍返首
                if($gridTian == '戊' && $gridEarth == '丙') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '青龍返首'];
                }

                // 飛鳥跌穴
                if($gridTian == '丙' && $gridEarth == '戊') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '飛鳥跌穴'];
                }

                // 天遁
                if($gridTian == '丙' && $gridEarth == '丁' && in_array($grid['gate'], ['生', '開'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '天遁'];
                }

                // 地遁
                if($gridTian == '乙' && $gridEarth == '己' && $grid['gate'] == '開') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '地遁'];
                }

                // 人遁
                if($gridTian == '丁' && $grid['shen'] == '陰' && $grid['gate'] == '休') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '人遁'];
                }

                // 風遁
                if($gridTian == '乙' && in_array($grid['gate'], ['休', '生', '開']) && $gridIndex == 4) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '風遁'];
                }

                // 雲遁
                if($gridTian == '乙' && $gridEarth == '辛' && in_array($grid['gate'], ['休', '生', '開'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '雲遁'];
                }

                // 龍遁
                if($gridTian == '乙' && in_array($grid['gate'], ['休', '生', '開']) && $gridIndex == 1) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '龍遁'];
                }

                // 虎遁
                if($gridTian == '乙' && $gridEarth == '辛' && $grid['gate'] == '休' && $gridIndex == 8) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '虎遁'];
                }

                // 神遁
                if($gridTian == '丙' && $grid['gate'] == '生' && $grid['shen'] == '天') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '神遁'];
                }

                // 鬼遁
                if($gridTian == '乙' && $grid['gate'] == '杜' && $grid['shen'] == '地') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '鬼遁'];
                }

                // 玉女守門
                if($gridEarth == '丁' && in_array($grid['gate'], ['休', '生', '開']) && in_array($this->_plateResult['ganzhi_hour'], ['庚午','己卯','戊子','丁酉','丙午','乙卯'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '玉女守門'];
                }
                
                // 交泰
                if($gridTian == '乙' && $gridEarth == '丁' && in_array($grid['gate'], ['休', '生', '開'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '交泰'];
                }
                if($gridTian == '丁' && $gridEarth == '丙' && in_array($grid['gate'], ['休', '生', '開'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '交泰'];
                }

                // 三奇得使
                if($gridTian == '乙' && $gridEarth == '己') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三奇得使'];
                }

                if($gridTian == '丁' && $gridEarth == '壬') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三奇得使'];
                }

                // 相儀相合
                $mixed = [['乙', '庚'], ['丙', '辛'], ['丁', '壬'], ['戊', '癸']];
                $search = array_unique([$gridTian, $gridEarth]);
                foreach ($mixed as $pair) {
                    if (empty(array_diff($search, $pair)) && count($search) == count($pair)) {
                        $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '相儀相合'];
                        break;
                    }
                }
                
                // 天運昌氣
                if($gridTian == '丁' && $gridEarth == '乙' && in_array($grid['gate'], ['休', '生', '開'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '天運昌氣'];
                }

                // 三詐五假
                /*
                乙 丙 丁 + 休 生 開 + 陰
                乙 丙 丁 + 休 生 開 + 合
                乙 丙 丁 + 休 生 開 + 地

                乙 丙 丁 + 景 + 天
                乙 丙 丁 + 驚 + 天

                丁 己 癸 + 杜 + 地 陰 合

                丁 己 癸 + 傷 + 地
                丁 己 癸 + 死 + 地
                */
                if(in_array($gridTian, ['乙', '丙',  '丁']) && in_array($grid['gate'], ['休', '生', '開']) && in_array($grid['shen'], ['陰', '合', '地'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三詐五假'];
                }

                if(in_array($gridTian, ['乙', '丙',  '丁']) && in_array($grid['gate'], ['景']) && in_array($grid['shen'], ['天'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三詐五假'];
                }

                if(in_array($gridTian, ['丁', '己',  '癸']) && in_array($grid['gate'], ['杜']) && in_array($grid['shen'], ['地'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三詐五假'];
                }

                if(in_array($gridTian, ['丁', '己',  '癸']) && in_array($grid['gate'], ['傷', '死']) && in_array($grid['shen'], ['合', '地'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三詐五假'];
                }
                
                if(in_array($gridTian, ['壬']) && in_array($grid['gate'], ['驚']) && in_array($grid['shen'], ['天'])) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'good', 'name' => '三詐五假'];
                }


                /*----------------------------------------------------------------*/

                // 青龍逃走
                if($gridTian == '乙' && $gridEarth == '辛') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '青龍逃走'];
                }

                // 白虎猖狂
                if($gridTian == '辛' && $gridEarth == '乙') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '白虎猖狂'];
                }

                // 朱雀投江
                if($gridTian == '丁' && $gridEarth == '癸') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '朱雀投江'];
                }

                // 螣蛇夭矯
                if($gridTian == '癸' && $gridEarth == '丁') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '螣蛇夭矯'];
                }

                // 太白入熒
                if($gridTian == '庚' && $gridEarth == '丙') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '太白入熒'];
                }

                // 熒入太白
                if($gridTian == '丙' && $gridEarth == '庚') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '熒入太白'];
                }

                // 飛干格
                if($gridTian == mb_substr($this->_plateResult['ganzhi_day'], 0, 1) && $gridEarth == '庚') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '飛干格'];
                }

                // 伏干格
                if($gridTian == '庚' && $gridEarth ==mb_substr($this->_plateResult['ganzhi_day'], 0, 1)) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '伏干格'];
                }

                // 大格
                if($gridTian == '庚' && $gridEarth == '癸') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '大格'];
                }

                // 小格
                if($gridTian == '庚' && $gridEarth == '壬') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '小格'];
                }

                // 刑格
                if($gridTian == '庚' && $gridEarth == '己') {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '刑格'];
                }

                // 年月日時格
                if($gridTian == '庚' && mb_substr($this->_plateResult['ganzhi_year'], 0, 1) == $gridEarth) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '年格'];
                }

                if($gridTian == '庚' && mb_substr($this->_plateResult['ganzhi_month'], 0, 1) == $gridEarth) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '月格'];
                }

                if($gridTian == '庚' && mb_substr($this->_plateResult['ganzhi_day'], 0, 1) == $gridEarth) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '日格'];
                }

                if($gridTian == '庚' && mb_substr($this->_plateResult['ganzhi_hour'], 0, 1) == $gridEarth) {
                    $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '時格'];
                }
            }
        }
        
        // 歡怡 
        // 地盤 乙 丙 丁  與 值符星 同宮
        if(in_array($this->_plateResult['grid'][$this->_plateResult['zhi_fu_index']]['earth'], ['乙', '丙',  '丁'])) {
            $goodBadReferences[$this->_plateResult['zhi_fu_index']][] = ['type' => 'good', 'name' => '歡怡'];
        }
        if(!empty($this->_plateResult['grid'][$this->_plateResult['zhi_fu_index']]['earth_alias'])) {
            if(in_array(mb_substr($this->_plateResult['grid'][$this->_plateResult['zhi_fu_index']]['earth_alias'], -1), ['乙', '丙',  '丁'])) {
                $goodBadReferences[$this->_plateResult['zhi_fu_index']][] = ['type' => 'good', 'name' => '歡怡'];
            }
        }
        
        // 三奇升殿 
        // 天盤 乙 在 3 震 | 丙 在 9 離 | 丁 在 7 兌
        foreach (['', '_alias'] as $suffix) {
            if(!empty($this->_plateResult['grid'][3]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][3]['tian'.$suffix], -1) == '乙') {
                $goodBadReferences[3][] = ['type' => 'good', 'name' => '三奇升殿'];
            }
            if(!empty($this->_plateResult['grid'][9]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][9]['tian'.$suffix], -1) == '丙') {
                $goodBadReferences[9][] = ['type' => 'good', 'name' => '三奇升殿'];
            }
            if(!empty($this->_plateResult['grid'][7]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][7]['tian'.$suffix], -1) == '丁') {
                $goodBadReferences[7][] = ['type' => 'good', 'name' => '三奇升殿'];
            }
        }

        // 奇遊祿位 
        // 天盤 乙 在 3 震 | 丙 在 4 巽 | 丁 在 9 離
        foreach (['', '_alias'] as $suffix) {
            if(!empty($this->_plateResult['grid'][3]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][3]['tian'.$suffix], -1) == '乙') {
                $goodBadReferences[3][] = ['type' => 'good', 'name' => '奇遊祿位'];
            }
            if(!empty($this->_plateResult['grid'][4]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][4]['tian'.$suffix], -1) == '丙') {
                $goodBadReferences[4][] = ['type' => 'good', 'name' => '奇遊祿位'];
            }
            if(!empty($this->_plateResult['grid'][9]['tian'.$suffix]) && mb_substr($this->_plateResult['grid'][9]['tian'.$suffix], -1) == '丁') {
                $goodBadReferences[9][] = ['type' => 'good', 'name' => '奇遊祿位'];
            }
        }
        
        // 相佐
        // 乙 丙 丁(地盤) 與 旬首(天盤) 同宮
        $findResult = $this->findHeadGanHourGridIndex('tian');
        $headGridIndex = $findResult[0];
        foreach ($this->_plateResult['grid'] as $key => $grid) {
            if($key == $headGridIndex) {
                foreach (['', '_alias'] as $suffix) {
                    if(!empty($grid['tian'.$suffix]) && in_array(mb_substr($grid['earth'.$suffix], -1), ['乙', '丙',  '丁'])) {
                        $goodBadReferences[$grid['index']][] = ['type' => 'good', 'name' => '相佐'];
                    }
                }
                break;
            }
        }
        
        
        // 其他
        $gridIndex = 99;
        $goodBadReferences[$gridIndex] = [];
        
        // 天顯時格
        $dayGan = mb_substr($this->_plateResult['ganzhi_day'], 0, 1);
        $hourFull = $this->_plateResult['ganzhi_hour'];
        if (($dayGan=='甲'||$dayGan=='己') && $hourFull=='甲子' ||
             ($dayGan=='乙'||$dayGan=='庚') && $hourFull=='甲申' ||
             ($dayGan=='丙'||$dayGan=='辛') && $hourFull=='甲午' ||
             ($dayGan=='丁'||$dayGan=='壬') && $hourFull=='甲辰') {
            $goodBadReferences[$gridIndex][] = ['type'=>'good','name'=>'天顯時格'];
        }
        
        // 天輔吉時
        if (($dayGan=='甲'||$dayGan=='己') && $hourFull=='己巳' ||
            ($dayGan=='乙'||$dayGan=='庚') && $hourFull=='甲申' ||
            ($dayGan=='丙'||$dayGan=='辛') && $hourFull=='甲午' ||
            ($dayGan=='丁'||$dayGan=='壬') && $hourFull=='甲辰' ||
            ($dayGan=='戊'||$dayGan=='癸') && $hourFull=='甲寅') {
           $goodBadReferences[$gridIndex][] = ['type'=>'good','name'=>'天輔吉時'];
       }
        
        
        // 五不遇時(時干克日干), 陽克陽陰克陰
        if($this->isWuBuYuShi(mb_substr($this->_plateResult['ganzhi_day'], 0, 1), mb_substr($this->_plateResult['ganzhi_hour'], 0, 1))) {
            $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '五不遇時'];
        }
        
        // 時干入墓
        // 戊戌, 丙戌, 癸未, 丁丑, 己丑
        if(in_array($this->_plateResult['ganzhi_hour'], ['戊戌','丙戌','癸未','丁丑','己丑'])) {
            $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '時干入墓'];
        }

        // 三奇入墓
        // 乙 入 6 乾宮 | 丙 入 6 乾宮 | 丁 入 8 艮宮
        foreach([6, 8] as $tIndex) {
            $tGrid = $this->_plateResult['grid'][$tIndex];
            
            $rumuArr = [];
            if(!empty($tGrid['ru_mu_tian']) ) {
                $rumuArr = array_merge($rumuArr, $tGrid['ru_mu_tian']);
            }
            if(!empty($tGrid['ru_mu_earth']) ) {
                $rumuArr = array_merge($rumuArr, $tGrid['ru_mu_earth']);
            }
            if($tIndex == 6 && (in_array('乙', $rumuArr) || in_array('丙', $rumuArr))) {
                $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '三奇入墓']; 
            }
            else if($tIndex == 8 && in_array('丁', $rumuArr)) {
                $goodBadReferences[$gridIndex][] = ['type' => 'bad', 'name' => '三奇入墓'];
            }
        }
        
        // 結果
        foreach ($goodBadReferences as $id => &$items) {
            $seenNames = [];
            $uniqueItems = [];

            foreach ($items as $item) {
                if (!in_array($item['name'], $seenNames)) {
                    $seenNames[] = $item['name'];
                    $uniqueItems[] = $item;
                }
            }

            $goodBadReferences[$id] = $uniqueItems;
        }
        
        // Sort
        foreach ($goodBadReferences as &$subArray) {
            usort($subArray, function($a, $b) {
                $lenA = mb_strlen($a['name']); // 使用 mb_strlen 支援中文
                $lenB = mb_strlen($b['name']);

                if ($lenA != $lenB) {
                    return $lenB <=> $lenA;
                }

                return $b['name'] <=> $a['name'];
            });
        }
        
        $this->_plateResult['good_bad_references'] = $goodBadReferences;
    }
    
    // 判斷五不遇時（時干克日干，陽克陽陰克陰）
    private function isWuBuYuShi($dayGan, $hourGan, $hourZhi = null) {
        // 1. 定義標準的日干對應時干
        $wuBuYuMap = [
            '甲' => '庚',
            '乙' => '辛',
            '丙' => '壬',
            '丁' => '癸',
            '戊' => '甲',
            '己' => '乙',
            '庚' => '丙',
            '辛' => '丁',
            '壬' => '戊',
            '癸' => '己'
        ];

        // 2. 如果連時辰的地支也要嚴格比對 (強烈建議)
        $wuBuYuFullMap = [
            '甲' => '庚午',
            '乙' => '辛巳',
            '丙' => '壬辰',
            '丁' => '癸卯',
            '戊' => '甲寅',
            '己' => '乙丑',
            '庚' => '丙子',
            '辛' => '丁酉',
            '壬' => '戊申',
            '癸' => '己未'
        ];

        // 如果你有傳入時辰的地支，做最嚴謹的「干支」完整比對
        if ($hourZhi !== null) {
            return isset($wuBuYuFullMap[$dayGan]) && $wuBuYuFullMap[$dayGan] === ($hourGan . $hourZhi);
        }

        // 如果你只拿得到時干，比對天干即可
        return isset($wuBuYuMap[$dayGan]) && $wuBuYuMap[$dayGan] === $hourGan;
}

    // 輔助功能：查旬首時天干九宮格位置
    private function findHeadGanHourGridIndex($plateIndex = 'earth') {
        // 尋找“旬首”在地盤或天盤所在宮位， 如原本落在 5.中宮，則尋找偏移後的宮位
        $headGridIndex = 0;
        $lastChar = mb_substr($this->_plateResult['lead'], -1);
        foreach ($this->_plateResult['grid'] as $grid) {
            if($lastChar == mb_substr($grid[$plateIndex], -1)) {
                $headGridIndex = $grid['index'];
            } 
        }
        if(in_array((int)$headGridIndex, [0, 5])) {
            foreach ($this->_plateResult['grid'] as $grid) {
                if(!empty($grid[$plateIndex.'_alias'])) {
                    if(((int)$headGridIndex == 5)) {
                        if($lastChar == mb_substr($grid[$plateIndex.'_alias'], -1)) {
                            $headGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if($lastChar == mb_substr($grid[$plateIndex.'_alias'], 0, 1)) {
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
        foreach ($this->_plateResult['grid'] as $grid) {
            if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$plateIndex], -1)) {
                $ganHourGridIndex = $grid['index'];
            } 
        }
        
        if(in_array((int)$ganHourGridIndex, [0, 5])) {
            foreach ($this->_plateResult['grid'] as $grid) {
                if(!empty($grid[$plateIndex.'_alias'])) {
                    if(((int)$ganHourGridIndex == 5)) {
                        if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$plateIndex.'_alias'], -1)) {
                            $ganHourGridIndex = $grid['index'];
                            break;
                        }
                    }
                    else {
                        if(mb_substr($ganzhiHour, 0, 1) == mb_substr($grid[$plateIndex.'_alias'], 0, 1)) {
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
