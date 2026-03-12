/*
八字計算 | 天干地支
$bazi = new BaZiCalculator('your_xml_folder_location');
$result = $bazi->calculate('2026-02-06 13:40:50');
*/
class BaZiCalculator {
    private $stems = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    private $branches = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    private $solarTermFolder = '';
    private $solarTerms;
    
    public function __construct($sourceFolder) {
        $this->solarTermFolder = $sourceFolder;
    }

    // 計算年干支
    private function getYearGanzhi($datetime){
        $year = intval(date('Y',$datetime));
        $lichun = $this->getSolarTermTime($year,'立春');
        // 比較當年立春時間，立春之前減一年
        if($datetime < $lichun){
            $year--;
        }
        $index = ($year - 4) % 60;
        return $this->stems[$index % 10] . $this->branches[$index % 12];
    }

    // 計算月支
    private function getMonthBranch($datetime){
        $year = intval(date('Y',$datetime));
        
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
        $xiaohan = $this->getSolarTermTime($year, '小寒');
        if ($xiaohan === false) {
            // 如果沒有節氣數據，用月份估算
            $month = date('n', $datetime);
            $map = [1=>'丑', 2=>'寅', 3=>'卯', 4=>'辰', 5=>'巳', 6=>'午',
                    7=>'未', 8=>'申', 9=>'酉', 10=>'戌', 11=>'亥', 12=>'子'];
            return $map[$month];
        }
        
        // 如果在小寒之前，屬於上一年的月份
        if ($datetime < $xiaohan) {
            // 獲取上一年的節氣來判斷
            $prevYear = $year - 1;
            $prevDaxue = $this->getSolarTermTime($prevYear, '大雪');
            
            // 大雪後是子月
            if ($prevDaxue !== false && $datetime >= $prevDaxue) {
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
            $termTime = $this->getSolarTermTime($year, $term);
            if ($termTime === false) { 
                continue; 
            }
            if ($datetime < $termTime) {
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
    private function getDayGanzhi($datetime){
        $jd = gregoriantojd(
            date('m',$datetime),
            date('d',$datetime),
            date('Y',$datetime)
        );
        $index = ($jd + 49) % 60;

        return $this->stems[$index%10] . $this->branches[$index%12];
    }

    // 計算時干支
    private function getHourGanzhi($datetime, $dayStem){
        $hour = intval(date('H', $datetime));
        $minute = intval(date('i', $datetime));

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
    private function getSolarTermTime($year, $name){
        return !empty($this->solarTerms[$year][$name])?strtotime($this->solarTerms[$year][$name]):null;
    }

    // 主函數
    public function calculate($datetime){
        $result = [];
        // 轉換成秒
        $datetime = strtotime($datetime);
        $selectedYear = intval(date('Y', $datetime));
 
        // 限制範圍
        if($selectedYear >= 1970 && $selectedYear <= 2100) {
            // 獲得 24 節氣參考資料
            foreach ([($selectedYear-1), $selectedYear, ($selectedYear+1)] as $year) {
                $xmlFile = (implode('/', array_filter([$this->solarTermFolder, $year])).'.xml');
                if(file_exists($xmlFile)) {
                    $xmlData = simplexml_load_file($xmlFile);
                    if(!empty($xmlData)) {
                        $yearData = [];
                        foreach ($xmlData->term as $term) {
                            $name = (string)$term->name;
                            $date = (string)$term->date;
                            $yearData[$name] = $date;
                        }
                        $this->solarTerms[$year] = $yearData;
                    }
                }
            }
            
            // 開始計算
            if(!empty($this->solarTerms)) {
                if(!empty($this->solarTerms[($selectedYear-1)]) && !empty($this->solarTerms[$selectedYear]) && !empty($this->solarTerms[($selectedYear+1)])) {
                    // 年干支
                    $yearGZ = $this->getYearGanzhi($datetime);
                    $yearStem = mb_substr($yearGZ,0,1);

                    // 月干支
                    $monthBranch = $this->getMonthBranch($datetime);
                    $monthStem = $this->getMonthStem($yearStem, $monthBranch);
                    $monthGZ = $monthStem.$monthBranch;

                    // 日干支
                    $dayGZ = $this->getDayGanzhi($datetime);
                    $dayStem = mb_substr($dayGZ,0,1);

                    // 時干支
                    $hourGZ = $this->getHourGanzhi($datetime,$dayStem);

                    // 結果
                    $result =  
                    [
                        'year'  =>  $yearGZ,
                        'month' =>  $monthGZ,
                        'day'   =>  $dayGZ,
                        'hour'  =>  $hourGZ
                    ];
                }
            }
        }
        
        return $result;
    }
}
