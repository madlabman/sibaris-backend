<?php


namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\ServiceAccount;

class SendPush extends Command
{
    /**
     * Сигнатура команды.
     * Для запуска вставляем после php artisan <сигнатура>.
     *
     * @var string
     */
    protected $signature = 'push:send {user_id} {uri}';

    /**
     * Описание команды.
     *
     * @var string
     */
    protected $description = 'Send push notification with URI to a user by Firebase Cloud Messaging';

    /**
     * В конструктор прописываем какие-либо инициализации.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Функция обработчик
     */
    public function handle()
    {
        // Подтягиваем пользователя
        $user = User::findOrFail($this->argument('user_id'));
        // Получаем токены
        $deviceTokens = $user->tokens->pluck('token')->all();

        $serviceAccount = ServiceAccount::fromJsonFile(env('FIREBASE_CREDENTIALS'));
        $firebase = (new Factory)
            ->withServiceAccount($serviceAccount)
            ->create();
        $messaging = $firebase->getMessaging();
        // Создаем сообщение с уведомлением и информацией
        $message = CloudMessage::new()
            ->withNotification(Notification::create('Title', 'Body'))
            ->withData(['uri' => $this->argument('uri')]);

        try {
            /** @var \Kreait\Firebase\Messaging\MulticastSendReport $report */
            $report = $messaging->sendMulticast($message, $deviceTokens); // Отправляем сообщение

            // Выводим статистику по отправке
            echo 'Successful sends: ' . $report->successes()->count() . PHP_EOL;
            echo 'Failed sends: ' . $report->failures()->count() . PHP_EOL;

            if ($report->hasFailures()) {
                foreach ($report->failures()->getItems() as $failure) {
                    echo $failure->error()->getMessage() . PHP_EOL;
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

    }
}
