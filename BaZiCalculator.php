/*
八字計算 | 天干地支
$bazi = new BaZiCalculator();
$result = $bazi->calculate('2026-02-06 13:40:50');
*/
class BaZiCalculator {

    private $stems = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    private $branches = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];

    private $solarTerms;

    public function __construct() {
        $url = "https://728rabbit.github.io/24-Solar-Terms-Reference-Table/24solarterms.json";
        $json = file_get_contents($url);
        $this->solarTerms = json_decode($json,true);
    }

    /* ---------------- 年干支 ---------------- */

    private function getYearGanzhi($date){

        $year = intval(date('Y',$date));

        $lichun = $this->getSolarTermTime($year,'立春');

        if($date < $lichun){
            $year--;
        }

        $index = ($year - 4) % 60;

        return $this->stems[$index % 10] . $this->branches[$index % 12];
    }

    /* ---------------- 月支 ---------------- */
    private function getMonthBranch($date){
        $year = intval(date('Y',$date));
        
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
            $month = date('n', $date);
            $map = [1=>'丑', 2=>'寅', 3=>'卯', 4=>'辰', 5=>'巳', 6=>'午',
                    7=>'未', 8=>'申', 9=>'酉', 10=>'戌', 11=>'亥', 12=>'子'];
            return $map[$month];
        }
        
        // 如果在小寒之前，屬於上一年的月份
        if ($date < $xiaohan) {
            // 獲取上一年的節氣來判斷
            $prevYear = $year - 1;
            $prevDaxue = $this->getSolarTermTime($prevYear, '大雪');
            
            if ($prevDaxue !== false && $date >= $prevDaxue) {
                return '子';  // 大雪後是子月
            }
            return '亥';  // 否則大概是亥月
        }
        
        // 遍歷當年的節氣
        $prevTerm = '小寒';
        $prevTime = $xiaohan;
        
        foreach ($termOrder as $term) {
            if ($term == '小寒') continue;
            
            $termTime = $this->getSolarTermTime($year, $term);
            if ($termTime === false) continue;
            
            if ($date < $termTime) {
                return $termToBranch[$prevTerm];
            }
            $prevTerm = $term;
            $prevTime = $termTime;
        }
        
        // 在大雪之後，屬於下一年的子月？不對，應該還是當年的子月
        return '子';
    }

    /* ---------------- 月干 ---------------- */

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

    /* ---------------- 日干支 ---------------- */

    private function getDayGanzhi($date){

        $jd = gregoriantojd(
            date('m',$date),
            date('d',$date),
            date('Y',$date)
        );

        $index = ($jd + 49) % 60;

        return $this->stems[$index%10] . $this->branches[$index%12];
    }

    /* ---------------- 時干支 ---------------- */
    private function getHourGanzhi($date, $dayStem){
        $hour = intval(date('H', $date));
        $minute = intval(date('i', $date));

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
    
    
    /* ---------------- 節氣時間 ---------------- */

    private function getSolarTermTime($year,$name){
        return !empty($this->solarTerms[$year][$name])?strtotime($this->solarTerms[$year][$name]):null;
    }

    /* ---------------- 主函數 ---------------- */

    public function calculate($datetime){

        $date = strtotime($datetime);

        $yearGZ = $this->getYearGanzhi($date);
        $yearStem = mb_substr($yearGZ,0,1);

        $monthBranch = $this->getMonthBranch($date);
        $monthStem = $this->getMonthStem($yearStem, $monthBranch);
        $monthGZ = $monthStem.$monthBranch;

        $dayGZ = $this->getDayGanzhi($date);
        $dayStem = mb_substr($dayGZ,0,1);
        $hourGZ = $this->getHourGanzhi($date,$dayStem);
   
        return [
            'year'=>$yearGZ,
            'month'=>$monthGZ,
            'day'=>$dayGZ,
            'hour'=>$hourGZ
        ];
    }
}
