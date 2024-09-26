<?php
//Настройки БД
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "tenders";

//Cоединение с БД
$conn = new mysqli($servername, $username, $password, $dbname);

//Очищаем таблицы
$names_tables = ["tenders", "tender_documents"];
foreach ($names_tables as $name_table) {
    $stmt = $conn->prepare("DELETE FROM $name_table");
    $stmt->execute();
}

$html = file_get_contents("https://com.ru-trade24.ru/Home/Trades"); //Получаем содержимое страницы
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXpath($dom);

$trades = $xpath->query('//div[@id="taball"]//div[contains(@class, "row  row--v-offset trade-card")]'); //Извлекаем всех тендеров (все <div> элементы, которые находятся внутри элемента с id="taball" и имеют соответствующий класс)

foreach ($trades as $trade) {
    //Извлекаем информацию о тендере
    $status = $xpath->query('.//div[contains(@class, "trade-card__status")]', $trade); //Статус тендера
    //Проверяем, что статус == "Идёт приём заявок"
    if ($status->length > 0 && strpos($status->item(0)->nodeValue, 'Идет прием заявок') !== false) {

        //Номер процедуры
        //Ищем символ "№", за которым следует любое количество пробелов и одна или несколько цифр
        preg_match('/№\s*(\d+)/', $xpath->query('.//div[contains(@class, "trade-card__type")]', $trade)->item(0)->nodeValue, $matches);
        $procedureNumber = isset($matches[1]) ? $matches[1] : '';
        $info = "Номер процедуры: " . $procedureNumber . "\n";

        //Организатор
        $organizer = $xpath->query('.//div[contains(@class, "trade-card__name")]', $trade)->item(0)->nodeValue;
        $info .= "Организатор: " . $organizer . "\n";

        //Ссылка на страницу процедуры
        $link = 'https://com.ru-trade24.ru' . $xpath->query('.//a', $trade)->item(0)->getAttribute('href');
        $info .= "Ссылка на процедуру: " . $link . "\n";

        $procedureHtml = file_get_contents($link); //Получаем содержимое страницы определённого тендера
        $datePattern = '/Дата и время начала представления заявок на участие в процедуре<\/label>\s*<div class="info__title">(.*?)<\/div>/';
        $docPattern = '/<a class="doc .*?" href="(.*?)">(.*?)<\/a>/';

        //Извлекаем дату и время
        preg_match($datePattern, $procedureHtml, $dateMatches);
        $start_time = trim($dateMatches[1]);
        $info .= "Дата и время начала подачи заявок: " . $start_time . "\n";
        //Меняем формат записи даты и времени
        $date_time = explode(" ", $start_time);
        $date = $date_time[0];
        $time = $date_time[1];
        $date_mas = explode(".", $date);
        $date_str = $date_mas[2] . '-' . $date_mas[1] . '-' . $date_mas[0];
        $time_str = $time . ":00";
        $start_time = $date_str . " " . $time_str;

        //Извлекаем документацию
        $documents = [];
        if (preg_match_all($docPattern, $procedureHtml, $docMatches, PREG_SET_ORDER)) {
            $info .= "Документация к аукциону:" . "\n";
            foreach ($docMatches as $match) {
                $documents[] = [
                    'file_name' => trim($match[2]),
                    'file_link' => 'https://com.ru-trade24.ru' . trim($match[1])
                ];
                $info .= "- " . trim($match[2]) . " - " . "https://com.ru-trade24.ru" . trim($match[1]) . "\n";
            }
        }
        
        echo($info . "\n"); //Вывод информации об одном тенедере

        //Записываем в БД тендеры в таблицу tenders
        $stmt = $conn->prepare("INSERT INTO tenders (number, organizer, link, start_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$procedureNumber, $organizer, $link, $start_time]);
    
        //Записываем в БД документы в таблицу tender_documents
        $tenderId = $conn->insert_id; //Получаем последний добавленный id тендера
        foreach ($documents as $doc) {
            $docStmt = $conn->prepare("INSERT INTO tender_documents (tender_id, file_name, file_link) VALUES (?, ?, ?)");
            $docStmt->execute([$tenderId, $doc['file_name'], $doc['file_link']]);
        }
    }
}
?>