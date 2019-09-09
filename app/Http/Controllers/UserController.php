<?php

namespace App\Http\Controllers;

use App\GoogleToken;
use App\User;
use Illuminate\Http\Request;

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
                    return $this->successResponse(['token' => $user->api_token]);   // Отдаем токен пользователю
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
}
