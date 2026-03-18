
# 24-Solar-Terms-Reference-Table
二十四節氣簡單對照表，包含年份、節氣名稱與時間點，適用於一般參考或日曆類應用。

閲覽URL：
https://728rabbit.github.io/24-Solar-Terms-Reference-Table/

### 額外説明：
 
solarterms/1969.xml ~ solarterms/2101.xml: 年節氣 XML 檔案

hkolunar/1970.xml ~ hkolunar/2100.xml: 新舊歷 XML 檔案

BaZiCalculator.php: 計算 1970 ~ 2100 之間的八字 (年月日時的天干地支)

    // 使用方法：
    $bazi = new BaZiCalculator('your_solarterms_folder_location');
    $result = $bazi->calculate('2026-02-06 13:40:50');
