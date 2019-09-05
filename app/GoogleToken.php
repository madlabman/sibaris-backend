<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

/**
 * Class GoogleToken
 * Фреймворк автоматически построит запросы к таблице `google_tokens`.
 * @package App
 */
class GoogleToken extends Model
{
    // Определяем поле, которое сможем установить через self::update.
    protected $fillable = [
        'token',
    ];

    // Получаем пользователя-владельца токена
    public function user()
    {
        return $this->hasOne(User::class);
    }
}
