## Для чего и как подписывать данные

Протокол передачи данных должен гарантировать, что к нам обращается именно тот сервис, который мы ожидаем.
Для этого API запрос должен содержать подпись как таргета/заголовков, так и тела запроса.

Преследуя эти цели был реализован [Cavage Http Signature](https://tools.ietf.org/html/draft-cavage-http-signatures-10)

### Как подписать запрос
Подпись заключается в следующем:
1. Подписывается тело запроса, всегда. Если это GET запрос, то подписывается пустое тело.
Пример подписи есть в `sign_with_body.php`. Там подписываем с помощью `OpenSslBasedHashAlgorithm`.
Значение подписи кладется в заголовок Digest.
2. Вычисляется заголовок `(request-target)`, который повторяет путь, по которому идет запрос.
Его добавлять в запрос необязательно, он будет вычисляться автоматически из запроса. Используйте HeadersListAccessor при подписи.
3. Вычисляется подпись. Пример есть в `sign.php` `$clerk->sign`.
Обратите внимание, что в подписи обязательно должны использоваться заголовки: `(request-target)`, `date`, `host`, `content-length`, `digest`.
По итогу у вас появляется объект класса `Signature`.
4. Этот объект вы передаете в маршаллер для приведения к строке.
Полученную строку вы передаете в заголовке Signature
5. В заголовок Authorization кладем тип авторизации и строку подписью. Пример есть в `sign.php`
Таким образом, у вас будет три дополнительных заголовка для запроса.

Вот пример заголовков:

```
[Digest] => sha256=7c2ddac128acc82410bd9da745a08f3ce177badb6d31e051df58f3eab2dd5f7c
[Signature] => keyId="rsa_pair-1",algorithm="rsa-sha512",headers="host date content-length x-api-token digest",signature="dERLsePoaVDvFwY47SnHFM3e/vesln6ECgfedBu2kZLfEjRUr4HkYyrP2rVVvPtm4jqfVKDoioUrSMV7U3GAFy2f79p3aoUws3WBq6C4EWyRwuYbvyHDeLb01rxP84936piy6wHl8linig+SNrCkerBTYqBBf70C94Ll5VdaumYFN0FHJ4crgsSoML+hmGMaB9ELYRhJORE4nOJoDQl+n2g4lkfXJ4Fy09Px1J5xBXc2wA03dGcdCrbL2K4lUeDR8qo4ZA+sB2wU/6SAtGx0J/NQQOW/RYKoZGp6elmPGO5J5yRCOO7Mq3TztqcFog3YwlwWSzwRcSa/ddJ09xSUEQ=="
[Authorization] => Signature keyId="rsa_pair-1",algorithm="rsa-sha512",headers="host date content-length x-api-token digest",signature="dERLsePoaVDvFwY47SnHFM3e/vesln6ECgfedBu2kZLfEjRUr4HkYyrP2rVVvPtm4jqfVKDoioUrSMV7U3GAFy2f79p3aoUws3WBq6C4EWyRwuYbvyHDeLb01rxP84936piy6wHl8linig+SNrCkerBTYqBBf70C94Ll5VdaumYFN0FHJ4crgsSoML+hmGMaB9ELYRhJORE4nOJoDQl+n2g4lkfXJ4Fy09Px1J5xBXc2wA03dGcdCrbL2K4lUeDR8qo4ZA+sB2wU/6SAtGx0J/NQQOW/RYKoZGp6elmPGO5J5yRCOO7Mq3TztqcFog3YwlwWSzwRcSa/ddJ09xSUEQ=="
```

### Как проверить запрос

Алгоритм проверки подписи такой:
1. Проверить, что есть заголовки `(request-target)`, `date`, `host`, `content-length`, `digest` и они в единственном числе.
2. Проверить content-length, что там только числа (для GET, HEAD запросов проверки нет)
3. Проверить date, что там валидная дата по любому из форматов: `DATE_RFC822`, `DATE_RFC1123`, `DATE_RFC850`. А также, что разница с текущей датой не более 60 секунд.
4. Проверяем Digest
    ```
    $digest = Digest::fromHeader($rawDigest);
    $digestAlgorithm = $this->digestFactory->make($digest->getAlgorithm());
    $digestAlgorithm->hash($request->getContent())->getHash() === $digest->getHash();
    ```
5. Проверить, что все заголовки, которые участвовали в подписи, есть в запросе.
6. Проверить подпись
    ```
    $verifier->verify($signature, $headersAccessor);
    ```
Пример есть в `examples/verify.php`

#### Проверка digest-а 
1. Делаем объект дайджеста `$digest = Digest::fromHeader($requestHeaders[HeadersEnum::HEADER_DIGEST])`
2. Делаем фабрику алгоритмов подписи `$digestAlgorithmsFactory = new OpenSslAlgorithmFactory()`
3. И получаем с ее помощью алгоритм подписи `$digestAlgorithm = $digestAlgorithmsFactory->make($digest->getAlgorithm())`
4. Получаем хэш теха запроса `$realHash = $digestAlgorithm->hash($requestBody)->getHash()`
5. Сравниваем хэши `$realHash === $digest->getHash()`

#### Проверка подписи
1. Обертку для заголовков запроса `$headersAccessor = new HeadersListAccessor($requestHeaders, $requestMethod, $requestUri);`
2. Делаем маршалер (сериализатор подписи) `$marshaller = new DraftRfcV10Marshaller();`
3. Делаем билдер подписи `$signingStringBuilder = new DraftRfcV10Builder();`
4. Делаем хранилище ключей (например, на основе массива)
     ```
     // Структура массива важна!
    $keyStorage = new ArrayBasedKeyStorage(
        [
            'rsa_pair-1' => [
                'type' => 'pem',
                'is_public' => true,
                'content' => file_get_contents(__DIR__.'/keys/public.pem'),
            ],
        ]
    );
     ```
5. Делаем кей-лоадер и с его помощью делаем провайдера ключей
     ```
    $keyLoader = new OpenSslPemKeyLoader();
    $keyProvider = new DefaultKeyProvider($keyStorage, [$keyLoader]);
     ```
6. Делаем фабрику алгоритмов `$algorithmFactory = new OpenSslAsymmetricAlgorithmsFactory();`
7. Собираем это все в верифаер подписи `$verifier = new DefaultVerifier($algorithmFactory, $keyProvider, $signingStringBuilder);`
8. Распаковываем подпись с помощью маршаллера `$signatureToVerify = $marshaller->unmarshall($signatureFromHeader);`
9. Проверяем подпись верифаером `$verifier->verify($signatureToVerify, $headersAccessor)`

Подробный пример есть в `examples/verify_with_body.php`

### Что нужно реализовать на стороне сервиса

Все необходимые классы есть в библиотеке.
Нужно модифицировать http клиент, чтобы все запросы к API подписывались.

Для корректной работы библиотеки нужны только пара private/public ключей.
- Private ключ нужен для подписи запросов
- Public ключ нужен для проверки этой подписи

В целях тестирования можно использовать ключ из `examples/keys`. Однако, нужно помнить, что в продакшене использовать тестовые ключи нельзя. А также, что ключи - крайне уязвимая информация и должны храниться в надежном месте.

Если необходимо, можно сгенерировать новую пару ключей командами:
```
openssl genrsa -aes-256-cbc -out private.pem 2048
openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```

### Предназначение классов

- `OpenSslBasedHashAlgorithm` - алгоритм для получения хэша из тела запроса, для дайджеста
- `HeadersListAccessor` - обертка над заголовками запроса
- `DraftRfcV10Builder` - алгоритм преобразования заголовков в строку для подписи
- `OpenSslPemPrivateKey` - обертка над приватным ключом для подписи заголовков
- `OpenSslBasedAsymmetricAlgorithm` - алгоритм подписи заголовков
- `DefaultClerk` - класс, который подписывает строку заголовков переданным алгоритмом
- `DraftRfcV10Marshaller` - сериализатор для получившейся подписи заголовоков
- `HeadersEnum` - перечисление используемых дополнительных заголовков запроса
