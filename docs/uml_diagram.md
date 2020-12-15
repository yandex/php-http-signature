# Uml диаграммы для data flow

Ресурс для генерации диаграмм:
[www.planttext.com](https://www.planttext.com)

Код для генерации диаграмм:

```
@startuml

title Подпись запроса

start 
:Формирование запроса (headers + body);
:Формирование подписи тела запроса: digest;
:Формирование подписи заголовков: signature;
:Отправка запроса в сервис;
stop

@enduml


@startuml

title Проверка запроса

start
:Request;
if (Есть заголовки signature и digest) then (no)
    stop
endif
if (Signature валиден) then (no)
    stop
endif
if (Digest валиден) then (no)
    stop
endif
:Обработка запроса;
stop

@enduml
```