# TendersConsole
Парсинг страницы сайта «РУ-ТРЕЙД»: https://com.ru-trade24.ru/Home/Trades (вывод информации в консоль)
## Инструкция по развёртыванию:
1. Прежде чем скачивать проект, убедитесь, что у вас установлены PHP и MySQL.
2. Перейдите на страницу репозитория https://github.com/AnnaSpirina/TendersConsole/
3. Нажмите на зелёную кнопку "Code".
4. Выберите "Download ZIP".
5. Разархивируйте загруженный файл в нужную папку на вашем компьютере.
6. Не забудьте создать базу данных `tenders` и таблицы в ней `tenders` и `tender_documents`, например, в **MySQL Workbench**
```
CREATE DATABASE TENDERS;
USE tenders;

CREATE TABLE tenders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(255),
    organizer VARCHAR(255),
    link VARCHAR(255),
    start_time DATETIME
);

CREATE TABLE tender_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tender_id INT,
    file_name VARCHAR(255),
    file_link VARCHAR(255),
    FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
);
```
7. Откройте файл tenders.php и измените настройки к БД в соответствие с вашими данными:
```
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "tenders";
```
9. Запустите выполнение файла tender.php.

## Описание функционала
После запуска файла вы увидете в консоли информацию о каждом тендере, имеющим статус "Идёт приём заявок": номер процедуры, организатор, ссылка на страницу процедуры, дату и время начала подачи заявок, документацию к этому аукциону - названия файлов и ссылки на них.
![image](https://github.com/user-attachments/assets/16f54549-a481-4a40-8dd2-64048abf2870)

## Используемые технологии
1. PHP
2. MySQLi
3. DOMDocument
4. DOMXPath
5. Regular Expressions
