/**
 * 八字計算 | 天干地支
 * 瀏覽器端版本 - 支援讀取 XML
 * 
 * 使用方式：
 * const bazi = new BaZiCalculator('/path/to/xml/folder');
 * const result = await bazi.calculate('2026-02-06 13:40:50');
 */

class BaZiCalculator {
    constructor(sourceFolder) {
        this.stems = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        this.branches = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        this.xmlFolder = sourceFolder;
        this.hkoLunar = {};
        this.solarTerms = {};
        this.lunarMonths = ['', '正', '二', '三', '四', '五', '六', '七', '八', '九', '十', '十一', '十二'];
        this.lunarDays = ['', '初一', '初二', '初三', '初四', '初五', '初六', '初七', '初八', '初九', '初十', 
                         '十一', '十二', '十三', '十四', '十五', '十六', '十七', '十八', '十九', '二十', 
                         '廿一', '廿二', '廿三', '廿四', '廿五', '廿六', '廿七', '廿八', '廿九', '三十', '三十一'];
        this.isLoading = false;
        this.loadQueue = [];
    }

    // 載入 XML 文件
    async loadXMLFile(filePath) {
        try {
            const response = await fetch(filePath);
            const xmlText = await response.text();
            const parser = new DOMParser();
            return parser.parseFromString(xmlText, 'text/xml');
        } catch (error) {
            console.error(`Error loading XML file ${filePath}:`, error);
            return null;
        }
    }

    // 載入節氣數據
    async loadSolarTermsData(year) {
        const filePath = `${this.xmlFolder}/solarterms/${year}.xml`;
        const xmlDoc = await this.loadXMLFile(filePath);
        
        if (!xmlDoc) return false;

        const yearData = {};
        const terms = xmlDoc.getElementsByTagName('term');
        
        for (let term of terms) {
            const name = term.getElementsByTagName('name')[0]?.textContent;
            const date = term.getElementsByTagName('date')[0]?.textContent;
            if (name && date) {
                yearData[name] = date;
            }
        }
        
        this.solarTerms[year] = yearData;
        return true;
    }

    // 載入農曆數據
    async loadLunarData(year) {
        const filePath = `${this.xmlFolder}/hkolunar/${year}.xml`;
        const xmlDoc = await this.loadXMLFile(filePath);
        
        if (!xmlDoc) return false;

        const root = xmlDoc.documentElement;
        const yearGanzhi = root.getAttribute('yearganzhi') || '';
        const zodiac = root.getAttribute('zodiac') || '';
        
        const days = xmlDoc.getElementsByTagName('day');
        const yearData = {
            yearGanzhi,
            zodiac,
            days: []
        };
        
        for (let day of days) {
            const date = day.getElementsByTagName('date')[0]?.textContent;
            const week = day.getElementsByTagName('week')[0]?.textContent;
            const dayText = day.getElementsByTagName('day')[0]?.textContent;
            const solarterm = day.getElementsByTagName('solarterm')[0]?.textContent;
            
            if (date) {
                // 解析日期
                const cleanDate = date.replace(/[年月日]/g, '-').replace(/-$/, '');
                const dateParts = cleanDate.split('-').map(p => p.padStart(2, '0'));
                const formattedDate = dateParts.join('-');
                
                this.hkoLunar[formattedDate] = {
                    year: year,
                    year_chinese: '',
                    month_chinese: '',
                    day_chinese: dayText || '',
                    week: week || '',
                    solar_term: solarterm || '',
                    year_chinese_alias: yearGanzhi,
                    zodiac: zodiac
                };
                
                yearData.days.push({
                    date: formattedDate,
                    day: dayText,
                    week: week,
                    solarterm: solarterm
                });
            }
        }
        
        return yearData;
    }

    // 批量載入所需的數據
    async loadRequiredData(selectedYear) {
        const years = [selectedYear - 1, selectedYear, selectedYear + 1];
        const promises = [];
        
        // 載入節氣數據
        for (const year of years) {
            if (!this.solarTerms[year]) {
                promises.push(this.loadSolarTermsData(year));
            }
        }
        
        // 載入農曆數據
        for (const year of years) {
            const yearStr = year.toString();
            let hasData = false;
            
            // 檢查是否已有該年數據
            for (const date in this.hkoLunar) {
                if (date.startsWith(yearStr)) {
                    hasData = true;
                    break;
                }
            }
            
            if (!hasData) {
                promises.push(this.loadLunarData(year));
            }
        }
        
        await Promise.all(promises);
    }

    // 計算年干支
    getYearGanzhi(dateTime) {
        const dateTimeMs = new Date(dateTime).getTime();
        const year = new Date(dateTimeMs).getFullYear();
        
        const lichun = this.findSolarTermTime(year, '立春');
        let adjustedYear = year;
        
        if (lichun && dateTimeMs < lichun) {
            adjustedYear--;
        }
        
        const index = (adjustedYear - 4) % 60;
        return this.stems[index % 10] + this.branches[index % 12];
    }

    // 計算月支
    getMonthBranch(dateTime) {
        const dateTimeMs = new Date(dateTime).getTime();
        const year = new Date(dateTimeMs).getFullYear();
        
        const termToBranch = {
            '立春': '寅', '驚蟄': '卯', '清明': '辰',
            '立夏': '巳', '芒種': '午', '小暑': '未',
            '立秋': '申', '白露': '酉', '寒露': '戌',
            '立冬': '亥', '大雪': '子', '小寒': '丑'
        };
        
        const termOrder = ['小寒', '立春', '驚蟄', '清明', '立夏', '芒種', 
                          '小暑', '立秋', '白露', '寒露', '立冬', '大雪'];
        
        const xiaohan = this.findSolarTermTime(year, '小寒');
        if (!xiaohan) {
            const month = new Date(dateTimeMs).getMonth() + 1;
            const map = {1: '丑', 2: '寅', 3: '卯', 4: '辰', 5: '巳', 6: '午',
                        7: '未', 8: '申', 9: '酉', 10: '戌', 11: '亥', 12: '子'};
            return map[month];
        }
        
        if (dateTimeMs < xiaohan) {
            const prevYear = year - 1;
            const prevDaxue = this.findSolarTermTime(prevYear, '大雪');
            
            if (prevDaxue && dateTimeMs >= prevDaxue) {
                return '子';
            }
            return '亥';
        }
        
        let prevTerm = '小寒';
        for (const term of termOrder) {
            if (term === '小寒') continue;
            
            const termTime = this.findSolarTermTime(year, term);
            if (!termTime) continue;
            
            if (dateTimeMs < termTime) {
                return termToBranch[prevTerm];
            }
            prevTerm = term;
        }
        
        return '子';
    }

    // 計算月干
    getMonthStem(yearStem, monthBranch) {
        const branchOrder = {
            '寅': 0, '卯': 1, '辰': 2, '巳': 3, '午': 4, '未': 5,
            '申': 6, '酉': 7, '戌': 8, '亥': 9, '子': 10, '丑': 11
        };
        
        const map = {
            '甲': '丙', '己': '丙',
            '乙': '戊', '庚': '戊',
            '丙': '庚', '辛': '庚',
            '丁': '壬', '壬': '壬',
            '戊': '甲', '癸': '甲'
        };
        
        const firstMonthStem = map[yearStem];
        const firstMonthStemIndex = this.stems.indexOf(firstMonthStem);
        const monthOffset = branchOrder[monthBranch];
        const monthStemIndex = (firstMonthStemIndex + monthOffset) % 10;
        
        return this.stems[monthStemIndex];
    }

    // 計算日干支
    getDayGanzhi(dateTime) {
        const date = new Date(dateTime);
        const jd = this.gregorianToJulian(
            date.getFullYear(),
            date.getMonth() + 1,
            date.getDate()
        );
        
        const index = (Math.floor(jd) + 49) % 60;
        return this.stems[index % 10] + this.branches[index % 12];
    }

    // 格里高利曆轉儒略日
    gregorianToJulian(year, month, day) {
        if (month < 3) {
            year -= 1;
            month += 12;
        }
        
        const A = Math.floor(year / 100);
        const B = 2 - A + Math.floor(A / 4);
        
        return Math.floor(365.25 * (year + 4716)) + 
               Math.floor(30.6001 * (month + 1)) + 
               day + B - 1524.5;
    }

    // 計算時干支
    getHourGanzhi(dateTime, dayStem) {
        const date = new Date(dateTime);
        const hour = date.getHours();
        
        let branchIndex;
        if (hour === 23) {
            branchIndex = 0;
        } else {
            branchIndex = Math.floor((hour + 1) / 2) % 12;
        }
        const branch = this.branches[branchIndex];

        const midnightStemMap = {
            '甲': '甲', '己': '甲',
            '乙': '丙', '庚': '丙',
            '丙': '戊', '辛': '戊',
            '丁': '庚', '壬': '庚',
            '戊': '壬', '癸': '壬'
        };

        let actualDayStem = dayStem;
        if (hour === 23) {
            const dayStemIndex = this.stems.indexOf(dayStem);
            const nextDayStemIndex = (dayStemIndex + 1) % 10;
            actualDayStem = this.stems[nextDayStemIndex];
        }

        const midnightStem = midnightStemMap[actualDayStem];
        const midnightStemIndex = this.stems.indexOf(midnightStem);
        const stemIndex = (midnightStemIndex + branchIndex) % 10;
        const stem = this.stems[stemIndex];

        return stem + branch;
    }

    // 某年節氣時間
    findSolarTermTime(year, name) {
        return this.solarTerms[year] && this.solarTerms[year][name] 
            ? new Date(this.solarTerms[year][name]).getTime() 
            : null;
    }

    // 新曆轉農曆
    solarToLunar(dateTime, zone = 'hong_kong') {
        const hktTime = this.convert2HKT(dateTime, zone);
        const dateTimeMs = new Date(hktTime).getTime();
        const dateStr = this.formatDate(dateTimeMs);
        
        if (!this.hkoLunar[dateStr]) {
            return null;
        }
        
        const lunarData = {...this.hkoLunar[dateStr]};
        
        // 尋找農曆月份
        let monthChinese = '';
        for (const [lunarDate, data] of Object.entries(this.hkoLunar)) {
            if (new Date(lunarDate).getTime() <= dateTimeMs) {
                const monthMatch = data.day_chinese?.match(/(.*)月$/);
                if (monthMatch) {
                    monthChinese = monthMatch[1].trim();
                }
            }
        }
        
        lunarData.month_chinese = monthChinese;
        
        // 中文日期轉換為數字
        const monthNumber = this.lunarMonths.indexOf(
            (lunarData.month_chinese || '').replace('閏', '')
        );
        lunarData.month = monthNumber > 0 ? monthNumber : 0;
        
        // 處理特殊情況
        if (dateTimeMs < new Date('1970-01-08').getTime()) {
            lunarData.month = 11;
            lunarData.month_chinese = '十一';
        }
        
        // 處理月份第一天
        const monthMatch = lunarData.day_chinese?.match(/(.*)月$/);
        if (monthMatch) {
            lunarData.day_chinese = '初一';
        }
        
        const dayNumber = this.lunarDays.indexOf(lunarData.day_chinese || '');
        lunarData.day = dayNumber > 0 ? dayNumber : 0;
        
        // 處理閏月
        const leapMatch = (lunarData.month_chinese || '').match(/閏/);
        lunarData.is_leap = leapMatch ? 1 : 0;
        
        // 月份別名
        if (lunarData.month_chinese === '十一') {
            lunarData.month_chinese_alias = '冬';
        } else if (lunarData.month_chinese === '十二') {
            lunarData.month_chinese_alias = '腊';
        }
        
        // 年份中文
        lunarData.year_chinese = this.yearToChineseDigits(lunarData.year);

        return lunarData;
    }

    formatDate(timestamp) {
        const date = new Date(timestamp);
        return date.getFullYear() + '-' + 
               String(date.getMonth() + 1).padStart(2, '0') + '-' + 
               String(date.getDate()).padStart(2, '0');
    }

    yearToChineseDigits(year) {
        const map = {
            '0': '零', '1': '一', '2': '二', '3': '三', '4': '四',
            '5': '五', '6': '六', '7': '七', '8': '八', '9': '九'
        };
        return String(year).split('').map(d => map[d]).join('');
    }

    // 主函數
    async calculate(dateTime, zone = 'hong_kong') {
        const selectedYear = new Date(dateTime).getFullYear();
        
        if (selectedYear < 1970 || selectedYear > 2100) {
            throw new Error('Year must be between 1970 and 2100');
        }
        
        // 載入所需數據
        await this.loadRequiredData(selectedYear);
        
        // 農曆
        const lunar = this.solarToLunar(dateTime, zone);

        // 年干支
        const yearGZ = this.getYearGanzhi(dateTime);
        const yearStem = yearGZ.charAt(0);

        // 月干支
        const monthBranch = this.getMonthBranch(dateTime);
        const monthStem = this.getMonthStem(yearStem, monthBranch);
        const monthGZ = monthStem + monthBranch;

        // 日干支
        const dayGZ = this.getDayGanzhi(dateTime);
        const dayStem = dayGZ.charAt(0);

        // 時干支
        const hourGZ = this.getHourGanzhi(dateTime, dayStem);

        // 結果
        return {
            datetime: dateTime,
            time_zone: zone,
            datetime_hk: this.convert2HKT(dateTime, zone),
            lunar: lunar,
            ganzhi_year: yearGZ,
            ganzhi_month: monthGZ,
            ganzhi_day: dayGZ,
            ganzhi_hour: hourGZ,
            jieqi_table: this.solarTerms
        };
    }

    convert2HKT(dateTime, zone) {
        const timezoneMap = {
            'hong_kong': 'Asia/Hong_Kong',
            'beijing': 'Asia/Shanghai',
            'taipei': 'Asia/Taipei',
            'tokyo': 'Asia/Tokyo',
            'seoul': 'Asia/Seoul',
            'singapore': 'Asia/Singapore',
            'sydney': 'Australia/Sydney',
            'london': 'Europe/London',
            'new_york': 'America/New_York',
            'los_angeles': 'America/Los_Angeles'
        };

        try {
            const date = new Date(dateTime);
            return date.toLocaleString('en-CA', { 
                timeZone: 'Asia/Hong_Kong',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).replace(',', '');
        } catch (e) {
            console.error('Timezone conversion error:', e);
            return dateTime;
        }
    }
}
