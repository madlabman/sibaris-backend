<?php

namespace App\Http\Controllers;

use App\GoogleToken;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\ServiceAccount;

class UserController extends Controller
{
    // Определение констант-статусов
    const USER_EXIST = 1;
    const USER_NOT_FOUND = 2;
    const NOT_ENOUGH_DATA = 4;
    const WRONG_PASSWORD = 8;

    public function __construct()
    {
        // Определяем методы, доступные только по api_token
        $this->middleware('auth', ['only' => [
            'refreshPosition',
            'refreshGoogleToken',
            'getUserInfo',
        ]]);
    }

    /**
     * Метод регистрации пользователей.
     *
     * @param Request $request - обертка для данных запроса
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function signUp(Request $request)
    {
        // Проверяем, что поля переданы и не пусты
        if ($request->filled(['login', 'password', 'name', 'email'])) {
            $user = User::where('login', $request->input('login'))->first();
            if (empty($user)) {
                $user = new User();
                $user->login = $request->input('login');
                $user->password = $request->input('password');
                $user->name = $request->input('name');
                $user->email = $request->input('email');
                $user->save();
                return $this->successResponse(['token' => $user->api_token]);
            } else {
                // Вернуть ошибку - пользователь существует
                return $this->errorResponse(['error' => self::USER_EXIST], 409);
            }
        }
        // Вернуть ошибку - не все поля заданы
        return $this->errorResponse(['error' => self::NOT_ENOUGH_DATA], 400);
    }

    /**
     * Вход пользователя по логину и паролю.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function signIn(Request $request)
    {
        if ($request->filled(['login', 'password'])) {
            $user = User::where([
                'login' => $request->input('login'),
            ])->first();
            if (!empty($user)) {
                if (app('hash')->check($request->input('password'), $user->password)) {
                    return $this->successResponse([
                        'id' => $user->id,
                        'token' => $user->api_token
                    ]); // Отдаем токен и id пользователю
                }
                // Вернуть ошибку - неверный пароль
                return $this->errorResponse(['error' => self::WRONG_PASSWORD], 403);
            }
            // Вернуть ошибку - пользователь не найден
            return $this->errorResponse(['error' => self::USER_NOT_FOUND], 404);
        }
        // Вернуть ошибку - не все поля заданы
        return $this->errorResponse(['error' => self::NOT_ENOUGH_DATA], 400);
    }

    /**
     * Метод обновления геопозиции пользователя.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function refreshPosition(Request $request)
    {
        if ($request->filled(['latitude', 'longitude', 'accuracy'])) {
            $user = app('auth')->user();
            if (!empty($user)) {
                $user->accuracy = $request->input('accuracy');
                $user->latitude = $request->input('latitude');
                $user->longitude = $request->input('longitude');
                $user->save();
                return $this->successResponse([]);
            } else {
                // Вернуть ошибку - пользователь не найден
                return $this->errorResponse(['error' => self::USER_NOT_FOUND], 404);
            }
        }
        // Вернуть ошибку - не все поля заданы
        return $this->errorResponse(['error' => self::NOT_ENOUGH_DATA], 400);
    }

    /**
     * Метод получения токена Firebase.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function refreshGoogleToken(Request $request)
    {
        if ($request->filled('new_token')) {    // Наличие нового токена обязательно в любом случае
            if ($request->filled('old_token')) {
                // Обновить запись в базе
                GoogleToken::updateOrCreate(
                    ['token' => $request->input('old_token')],  // Условие поиска
                    [
                        'user_id' => app('auth')->user()->id,
                        'token' => $request->input('new_token'),
                    ]   // Данные для обновления
                );
            } else {
                // Создать новую запись для пользователя
                /** @var $token GoogleToken */
                $token = GoogleToken::updateOrCreate(['token' => $request->input('new_token')]);
                app('auth')->user()->tokens()->save($token);    // Связываем токен и пользователя
            }
            // Вернуть положительный статус
            return $this->successResponse([]);
        }
        // Вернуть ошибку - не все поля заданы
        return $this->errorResponse(['error' => self::NOT_ENOUGH_DATA], 400);
    }

    /**
     * Возвращает данные пользователя.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function getUserInfo(Request $request)
    {
        $user = app('auth')->user();
        if (!empty($user)) {
            return $this->successResponse($user->toArray());
        }
        // Вернуть ошибку - пользователь не найден
        return $this->errorResponse(['error' => self::USER_NOT_FOUND], 404);
    }

    /**
     * Метод, позволяющий отправку пуш-уведомления пользователям.
     *
     * Параметры запроса:
     * message - текст сообщения
     * subject - тема сообщения
     * created_low - нижняя граница даты регистрации в одном из стандартных форматов
     * created_high - верхняя граница даты регистрации в одном из стандартных форматов
     *
     * @param Request $request
     * @return Response
     */
    public function sendPush(Request $request): Response
    {
        // Никакой авторизации на данный момент не проводим
        // Парсим полученные параметры и пытаемся произвести отправку
        if ($request->filled('message')) {  // В поле message помещаем текст для отправки - обязательное поле
            // Подготавливаем сообщение для отправки
            $serviceAccount = ServiceAccount::fromJsonFile(env('FIREBASE_CREDENTIALS'));
            $firebase = (new Factory)
                ->withServiceAccount($serviceAccount)
                ->create();
            $messaging = $firebase->getMessaging();
            // Создаем сообщение с уведомлением и информацией
            // Ссылку на изображение захардкодил - механизм надо продумать, где и как осуществляется хранение изображений
            $message = CloudMessage::new()
                ->withNotification(
                    Notification::create(
                        $request->input('subject'),
                        $request->input('message'),
                        'https://upload.wikimedia.org/wikipedia/commons/e/ec/RandomBitmap.png')
                )->withData([
                    'uri' => 'https://vk.com',
                ]);
            // Получаем пользователей исходя из параметров запроса
            $users = null;
            if ($request->filled('created_low') && $request->filled('created_high')) {
                $users = User::whereBetween('created_at', [
                    Carbon::parse($request->input('created_low')),
                    Carbon::parse($request->input('created_high'))
                ])->get();
            } else {
                // Забираем всех пользователей
                $users = User::all();
            }
            // Проходим по пользователям
            foreach ($users as $userToSend) {
                // Получаем токены
                $deviceTokens = $userToSend->tokens->pluck('token')->all();
                // Отправляем сообщение
                try {
                    $report = $messaging->sendMulticast($message, $deviceTokens);
                    if ($report->hasFailures() && $report->successes()->count() == 0) {
                        // Попытка отправки состоялась, но ни одного пуша отправить не удалось
                        app('log')->debug('Failed to send push notification to the user with id=' . $userToSend->id);
                    }
                } catch (\Exception $e) {
                    app('log')->debug('Failed to send push notification.');
                    app('log')->debug($e->getMessage());
                }
            }
            // Выкидываем положительный ответ - формально
            return $this->successResponse([]);
        }
        // Во всех остальных случаях - ошибка
        return $this->errorResponse(['error' => self::NOT_ENOUGH_DATA], 400);
    }
}
