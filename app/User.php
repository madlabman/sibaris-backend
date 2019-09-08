<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Ramsey\Uuid\Uuid;

/**
 * Class User
 * Фреймворк автоматически построит запросы к таблице `users`.
 * @package App
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * Атрибуты, которые можно заполнять массово, например, при предварительной загрузке в БД пользователей.
     *
     * @var array
     */
    protected $fillable = [
        'login',
    ];

    /**
     * Атрибуты, которые скрываются из вывода.
     *
     * @var array
     */
    protected $hidden = [
        'api_token',
        'password',
    ];

    // Хук на создание новой модели
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->api_token = (string) Uuid::uuid4(); // Автоматически создаем токен при добавлении пользователя
        });
    }

    /**
     * Поле position возвращает объект GeoPosition.
     *
     * @return GeoPosition
     */
    public function getPositionAttribute()
    {
        return new GeoPosition($this->accuracy, $this->latitude, $this->longitude);
    }

    // Хук на установку пароля пользователя
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = app('hash')->make($value);    // Хешируем пароль
    }

    // Получаем токены пользователя по полю user_id в таблице google_tokens.
    public function tokens()
    {
        return $this->hasMany(GoogleToken::class, 'user_id');
    }
}
