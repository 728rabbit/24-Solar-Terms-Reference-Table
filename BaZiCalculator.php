<?php
/*
八字計算 | 天干地支
$bazi = new BaZiCalculator('your_xml_folder_location');
$result = $bazi->calculate('2026-02-06 13:40:50');
*/

namespace App\Libs\calendar;

class BaZiCalculator {
    private $stems = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    private $branches = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    private $xmlFolder = '';
    private $hkoLunar = [];
    private $solarTerms = [];
    private $lunarMonths = array('', '正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二');
    private $lunarDays = array('', '初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十', '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十', '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十', '三十一');

    public function __construct($sourceFolder) {
        $this->xmlFolder = $sourceFolder;
    }

    // 計算年干支
    private function getYearGanzhi($dateTime){
        // 轉換成秒
        $dateTime = strtotime($dateTime);
        
        // 讀取年份
        $year = intval(date('Y', $dateTime));
        
        // 比較當年立春時間，立春之前減一年
        $lichun = $this->findSolarTermTime($year,'立春');
        if($dateTime < $lichun){
            $year--;
        }
        $index = ($year - 4) % 60;
        return $this->stems[$index % 10] . $this->branches[$index % 12];
    }

    // 計算月支
    private function getMonthBranch($dateTime){
        // 轉換成秒
        $dateTime = strtotime($dateTime);
        
        // 讀取年份
        $year = intval(date('Y',$dateTime));
        
        // 節氣對應的地支
        $termToBranch = [
            '立春' => '寅', '驚蟄' => '卯', '清明' => '辰',
            '立夏' => '巳', '芒種' => '午', '小暑' => '未',
            '立秋' => '申', '白露' => '酉', '寒露' => '戌',
            '立冬' => '亥', '大雪' => '子', '小寒' => '丑'
        ];
        
        // 節氣順序（從一年的開始小寒算起）
        $termOrder = ['小寒', '立春', '驚蟄', '清明', '立夏', '芒種', 
                      '小暑', '立秋', '白露', '寒露', '立冬', '大雪'];
        
        // 獲取當年小寒
        $xiaohan = $this->findSolarTermTime($year, '小寒');
        if ($xiaohan == false) {
            // 如果沒有節氣數據，用月份估算
            $month = date('n', $dateTime);
            $map = [1=>'丑', 2=>'寅', 3=>'卯', 4=>'辰', 5=>'巳', 6=>'午',
                    7=>'未', 8=>'申', 9=>'酉', 10=>'戌', 11=>'亥', 12=>'子'];
            return $map[$month];
        }
        
        // 如果在小寒之前，屬於上一年的月份
        if ($dateTime < $xiaohan) {
            // 獲取上一年的節氣來判斷
            $prevYear = $year - 1;
            $prevDaxue = $this->findSolarTermTime($prevYear, '大雪');
            
            // 大雪後是子月
            if ($prevDaxue != false && $dateTime >= $prevDaxue) {
                return '子';
            }
            // 否則大概是亥月
            return '亥';
        }
        
        // 遍歷當年的節氣
        $prevTerm = '小寒';
        $prevTime = $xiaohan;
        foreach ($termOrder as $term) {
            if ($term == '小寒') { 
                continue;
            }
            $termTime = $this->findSolarTermTime($year, $term);
            if ($termTime == false) { 
                continue; 
            }
            if ($dateTime < $termTime) {
                return $termToBranch[$prevTerm];
            }
            $prevTerm = $term;
            $prevTime = $termTime;
        }
        
        // 在大雪之後，屬於當年的子月
        return '子';
    }

    // 計算月干
    private function getMonthStem($yearStem, $monthBranch){
        // 月份對應表（正月寅月為0，二月卯月為1，依此類推）
        $branchOrder = [
            '寅' => 0, '卯' => 1, '辰' => 2, '巳' => 3, '午' => 4, '未' => 5,
            '申' => 6, '酉' => 7, '戌' => 8, '亥' => 9, '子' => 10, '丑' => 11
        ];
        
        // 五虎遁月干口訣：
        // 甲己之年丙作首，乙庚之年戊為頭
        // 丙辛必定尋庚起，丁壬壬位順行流
        // 戊癸何方發，甲寅之上好追求 
        $map = [
            '甲' => '丙', '己' => '丙',
            '乙' => '戊', '庚' => '戊',
            '丙' => '庚', '辛' => '庚',
            '丁' => '壬', '壬' => '壬',
            '戊' => '甲', '癸' => '甲'
        ];
        
        // 獲取正月（寅月）的天干
        $firstMonthStem = $map[$yearStem];
        $firstMonthStemIndex = array_search($firstMonthStem, $this->stems);
        
        // 計算當前月份相對於正月的偏移量
        $monthOffset = $branchOrder[$monthBranch];
        
        // 計算當前月份的天干
        $monthStemIndex = ($firstMonthStemIndex + $monthOffset) % 10;
        
        return $this->stems[$monthStemIndex];
    }

    // 計算日干支
    private function getDayGanzhi($dateTime){
        // 轉換成秒
        $dateTime = strtotime($dateTime);
        
        // 將格利高里曆法的日期轉換為儒略日計數，然後再轉換回格利高里曆法的日期
        $jd = gregoriantojd(
            date('m', $dateTime),
            date('d', $dateTime),
            date('Y', $dateTime)
        );
        $index = ($jd + 49) % 60;

        return $this->stems[$index%10] . $this->branches[$index%12];
    }

    // 計算時干支
    private function getHourGanzhi($dateTime, $dayStem){
        // 轉換成秒
        $dateTime = strtotime($dateTime);
        
        // 讀取時秒
        $hour = intval(date('H', $dateTime));
        $minute = intval(date('i', $dateTime));

        // 計算時支（23:00-00:59為子時）
        if ($hour == 23) {
            $branchIndex = 0; // 子時
        } else {
            $branchIndex = floor(($hour + 1) / 2) % 12;
        }
        $branch = $this->branches[$branchIndex];

        // 五鼠遁時干口訣
        $midnightStemMap = [
            '甲' => '甲', '己' => '甲',
            '乙' => '丙', '庚' => '丙',
            '丙' => '戊', '辛' => '戊',
            '丁' => '庚', '壬' => '庚',
            '戊' => '壬', '癸' => '壬'
        ];

        // 對於23:00-23:59，雖然日干用今天的，但時干要用明天的子時
        // 明天的日干可以通過今天的日干推算：日干每天前進5個索引（因為60/12=5）
        $actualDayStem = $dayStem;
        if ($hour == 23) {
            // 23:00-23:59 屬於明天的子時，需要用明天的日干來推算時干
            $dayStemIndex = array_search($dayStem, $this->stems);
            $nextDayStemIndex = ($dayStemIndex + 1) % 10; // 日干每天前進1位
            $actualDayStem = $this->stems[$nextDayStemIndex];
        }

        $midnightStem = $midnightStemMap[$actualDayStem];
        $midnightStemIndex = array_search($midnightStem, $this->stems);

        // 時干 = 子時天干索引 + 時支索引
        $stemIndex = ($midnightStemIndex + $branchIndex) % 10;
        $stem = $this->stems[$stemIndex];

        return $stem . $branch;
    }
 
    // 某年節氣時間
    private function findSolarTermTime($year, $name){
        return !empty($this->solarTerms[$year][$name])?strtotime($this->solarTerms[$year][$name]):null;
    }
    
    // 新曆轉成農曆
    private function solarToLunar($dateTime, $zone = 'hong_kong'){
        // 轉換成香港(8+)時間
        $dateTime = $this->convert2HKT($dateTime, $zone);
        
        // 轉換成秒
        $dateTime = strtotime($dateTime);
        
        // 讀取年份
        $selectedYear = intval(date('Y', $dateTime));
        
        // 獲得年份參照表
        $thisYearGanzhi = '';
        $thisYearZodiac = '';
        $thisYearLunarFirstDate = '';
        foreach ([($selectedYear-1), $selectedYear, ($selectedYear+1)] as $year) {
            $xmlFile = (implode('/', array_filter([$this->xmlFolder, 'hkolunar', $year])).'.xml');
            if(file_exists($xmlFile)) {
                $xmlData = simplexml_load_file($xmlFile);
                if(!empty($xmlData)) {
                     if((int)$selectedYear == (int)$xmlData['year']) {
                        $thisYearGanzhi = (string)$xmlData['yearganzhi'];
                        $thisYearZodiac = (string)$xmlData['zodiac'];
                    }
                    foreach ($xmlData->day as $day) {
                        $index = trim((string)preg_replace('/[年|月|日]/ui', '-', $day->date));
                        $index = array_filter(explode('-', $index));
                        foreach ($index as $key => $value) {
                            $index[$key] = (string) str_pad($value, 2, '0', STR_PAD_LEFT);
                        }
                        
                        if((trim((string)$day->day) == '正月') && ((int)$selectedYear == (int)$year)) {
                            $thisYearLunarFirstDate = implode('-', $index);
                        }
                        
                        if(strtotime(implode('-', $index)) >= ($dateTime-40*24*3600) && strtotime(implode('-', $index)) <= ($dateTime+20*24*3600)) {
                            $this->hkoLunar[implode('-', $index)] = 
                            [
                                'year'          =>  date('Y', $dateTime),
                                'month'         =>  0,
                                'day'           =>  0,
                                'year_chinese'  =>  '',
                                'month_chinese' =>  '',
                                'day_chinese'   =>  trim((string)$day->day),
                                'week'          =>  trim((string)$day->week),
                                'solar_term'    =>  trim((string)$day->solarterm),
                            ];
                        }
                    }
                }
            }
        }
        
        if(!empty($this->hkoLunar)) {
            $findhkoLunar = $this->hkoLunar[date('Y-m-d', $dateTime)];

            // 1.本年正月前為上一年
            if($dateTime < strtotime($thisYearLunarFirstDate)) {
                $findhkoLunar['year'] = ($findhkoLunar['year'] - 1);
            }
            $findhkoLunar['year_chinese'] = $this->yearToChineseDigits($findhkoLunar['year']);
            
            // 2.尋找農曆月份
            foreach ($this->hkoLunar as $lunarDate => $lunar) {
                if(strtotime($lunarDate) <= $dateTime) {
                    preg_match('/(.*)月$/ui', $lunar['day_chinese'], $monthMatch);
                    if(!empty($monthMatch)) {
                        $findhkoLunar['month_chinese'] = trim($monthMatch[1]);
                    }
                }
            }
            
            // 3.中文日期轉換為數字
            $monthNumber = array_search(preg_replace('/閏/ui', '', $findhkoLunar['month_chinese']), $this->lunarMonths);
            $findhkoLunar['month'] = $monthNumber;
            // 1970年01月08日 為 十二月初一
            if($dateTime < strtotime('1970-01-08')) {
                $findhkoLunar['month'] = 11;
                $findhkoLunar['month_chinese'] = '十一';
            }
            
            // 以“月”結尾，為本月第一天，即初一
            preg_match('/(.*)月$/ui', $findhkoLunar['day_chinese'], $monthMatch);
            if($monthMatch) {
                $findhkoLunar['day_chinese'] = '初一';
            }
            $dayNumber = array_search($findhkoLunar['day_chinese'], $this->lunarDays);
            $findhkoLunar['day'] = $dayNumber;
            
            // 4.額外資料
            $findhkoLunar['year_chinese_alias'] = $thisYearGanzhi;
            $findhkoLunar['zodiac'] = $thisYearZodiac;
            
            preg_match('/閏/ui', $findhkoLunar['month_chinese'], $leapMatch);
            $findhkoLunar['is_leap'] = (!empty($leapMatch)?1:0);
            
            if($findhkoLunar['month_chinese'] == '十一') {
                $findhkoLunar['month_chinese_alias'] = '冬';
            }
            else if($findhkoLunar['month_chinese'] == '十二') {
                $findhkoLunar['month_chinese_alias'] = '腊';
            }

            // 結果
            return $findhkoLunar;
        }
        
        return false;
    }
    
    private function yearToChineseDigits($year) {
        $map = [
            '0'=>'零','1'=>'一','2'=>'二','3'=>'三','4'=>'四',
            '5'=>'五','6'=>'六','7'=>'七','8'=>'八','9'=>'九'
        ];

        return implode('', array_map(fn($d) => $map[$d], str_split($year)));
    }

    // 主函數
    public function calculate($dateTime, $zone = 'hong_kong'){
        // 轉換成香港(8+)時間
        $hkDateTime = $this->convert2HKT($dateTime, $zone);
        
        $result = [];
        $selectedYear = intval(date('Y', strtotime($hkDateTime)));
 
        // 限制範圍
        if($selectedYear >= 1970 && $selectedYear <= 2100) {
            // 獲得 24 節氣參考資料
            foreach ([($selectedYear-1), $selectedYear, ($selectedYear+1)] as $year) {
                $xmlFile = (implode('/', array_filter([$this->xmlFolder, 'solarterms', $year])).'.xml');
                if(file_exists($xmlFile)) {
                    $xmlData = simplexml_load_file($xmlFile);
                    if(!empty($xmlData)) {
                        $yearData = [];
                        foreach ($xmlData->term as $term) {
                            $name = (string)$term->name;
                            $date = (string)$term->date;
                            $yearData[$name] = $this->ceilToMinute($date);
                        }
                        $this->solarTerms[$year] = $yearData;
                    }
                }
            }
            
            // 開始計算
            if(!empty($this->solarTerms)) {
                if(!empty($this->solarTerms[($selectedYear-1)]) && !empty($this->solarTerms[$selectedYear]) && !empty($this->solarTerms[($selectedYear+1)])) {
                    $lunar = $this->solarToLunar($hkDateTime);
                    
                    // “23:00:00 ~ 00:00:00” 為下一天開始， 計算天干地支要加 1 小時
                    $ganzhiDateTime = $hkDateTime;
                    if((int)date('H', strtotime($hkDateTime)) == 23) {
                        $ganzhiDateTime = date('Y-m-d H:i:s', strtotime($ganzhiDateTime) + 3600);
                    }

                    // 年干支
                    $yearGZ = $this->getYearGanzhi($ganzhiDateTime);
                    $yearStem = mb_substr($yearGZ, 0, 1);

                    // 月干支
                    $monthBranch = $this->getMonthBranch($ganzhiDateTime);
                    $monthStem = $this->getMonthStem($yearStem, $monthBranch);
                    $monthGZ = $monthStem.$monthBranch;

                    // 日干支
                    $dayGZ = $this->getDayGanzhi($ganzhiDateTime);
                    $dayStem = mb_substr($dayGZ, 0, 1);

                    // 時干支
                    $hourGZ = $this->getHourGanzhi($ganzhiDateTime, $dayStem);

                    // 結果
                    $result =  
                    [
                        'datetime'      =>  $dateTime,
                        'time_zone'     =>  $zone,
                        'datetime_hk'   =>  $hkDateTime,
                        'lunar'         =>  $lunar,
                        'ganzhi_year'   =>  $yearGZ,
                        'ganzhi_month'  =>  $monthGZ,
                        'ganzhi_day'    =>  $dayGZ,
                        'ganzhi_hour'   =>  $hourGZ,
                        'jieqi_table'   =>  $this->solarTerms
                    ];
                }
            }
        }

        return $result;
    }

    private function convert2HKT($dateTime, $zone) {
        $timezoneMap = [
            // 亞洲 / 亞太
            'hong_kong'    => 'Asia/Hong_Kong',   // 香港
            'beijing'      => 'Asia/Shanghai',    // 北京 / 上海
            'taipei'       => 'Asia/Taipei',      // 台北
            'tokyo'        => 'Asia/Tokyo',       // 東京
            'seoul'        => 'Asia/Seoul',       // 首爾
            'singapore'    => 'Asia/Singapore',   // 新加坡
            'bangkok'      => 'Asia/Bangkok',     // 曼谷
            'kolkata'      => 'Asia/Kolkata',     // 加爾各答（印度標準時間）
            'sydney'       => 'Australia/Sydney', // 悉尼

            // 歐洲
            'london'       => 'Europe/London',    // 倫敦
            'paris'        => 'Europe/Paris',     // 巴黎
            'berlin'       => 'Europe/Berlin',    // 柏林
            'moscow'       => 'Europe/Moscow',    // 莫斯科

            // 北美
            'new_york'     => 'America/New_York',     // 紐約
            'los_angeles'  => 'America/Los_Angeles',  // 洛杉磯
            'chicago'      => 'America/Chicago',      // 芝加哥
            'toronto'      => 'America/Toronto',      // 多倫多
            'vancouver'    => 'America/Vancouver',    // 溫哥華

            // 南美（可選）
            'sao_paulo'    => 'America/Sao_Paulo',    // 聖保羅
            'buenos_aires' => 'America/Argentina/Buenos_Aires', // 布宜諾斯艾利斯

            // 大洋洲
            'auckland'     => 'Pacific/Auckland',     // 奧克蘭
        ];

        // 獲取完整的時區名稱
        $fullTimezone = $timezoneMap[strtolower($zone)] ?? $zone;
  
        try {
            $date = new \DateTime($dateTime, new \DateTimeZone($fullTimezone));
            $date->setTimezone(new \DateTimeZone('Asia/Hong_Kong'));
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
    
    private function ceilToMinute($time, $format = 'Y-m-d H:i:s')
    {
        if (is_numeric($time)) {
            $timestamp = $time;
        } else {
            $timestamp = strtotime($time);
        }

        if ($timestamp === false) {
            return false;
        }

        // 如果有秒數（> 0），則進位到下一分鐘
        if (date('s', $timestamp) > 0) {
            $timestamp = strtotime('+1 minute', $timestamp);
        }

        // 將秒數歸零
        $timestamp = strtotime(date('Y-m-d H:i:00', $timestamp));

        return date($format, $timestamp);
    }
}
