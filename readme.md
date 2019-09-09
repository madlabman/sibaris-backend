## Предварительные требования

1. PHP >= 7.1.3
1.1 `pdo_mysql` (раскомментировать в файле `php.ini`)
2. [Composer](https://getcomposer.org/)
3. MySQL >= 5.6 && < 8

## Запуск приложения

1. Скопировать файл `.env.example` в файл `.env`
2. Заполнить данные в файле `.env`
3. Выполнить команду `composer install` - установит все необходимые зависимости
3. Выполнить команду `php artisan migrate` - создаст структуру БД
4. Выполнить команду `php -S 127.0.0.1:8000 -t public/` - встроенный в PHP веб-сервер "смотрящий" в директорию `public`

## Общие замечания

1. Пути прописываются в `routes/web.php`
2. Основной контроллер `App\Http\Controllers\UserController`
3. Токен API генерируется автоматически, см. `App\User`
4. По поводу сущностей во фреймворке (моделей) смотреть [здесь](https://laravel.com/docs/5.8/eloquent)

## Отправка пуш-уведомлений с ссылкой

Для отправки реализована [команда](https://laravel.com/docs/5.8/artisan#writing-commands) `\App\Console\Commands\SendPush`

Вызываем команду: `php artisan push:send <user_id> <uri>`

Также в документации приведен пример по вызову команды из кода.
