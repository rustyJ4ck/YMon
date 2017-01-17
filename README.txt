Скрипт мониторинга цен с яндекс маркета.

Смотрит файлы в папке sheets (*.xlsx) и читает из них список товаров.

Для каждого товара должен быть указан идентификатор (http://market.yandex.ru/product/{8229520}/)

Далее с маркета берется средняя цена и таблица обновляется.

Обновлятор можно повесить на крон/планировщик.

Для папки cache требуются права на запись.


Пример вывода:

>php ymon.php                                    

[0.0000] XLS.read: sheets/example.xlsx   
[2.8592] .. BEKO CS 328020             19040.00                         
[2.8612] .. Zanussi ZEV 6140 XBA       14990.00                         
[2.8632] .. BEKO OIC 22102 X           14847.00                         
[2.8652] .. LG F-80C3LD                20437.50                         
[2.8652] Updated rows: 1                                                
[2.8662] XLS.Save: sheets/example.xlsx   

Описание на русском
http://www.skillz.ru/dev/php/article-script_monitoringa_cen_yandex_marketa.html 
[Пример](http://wb.4zu.ru/)
[Пример2](http://hard.4zu.ru/)


