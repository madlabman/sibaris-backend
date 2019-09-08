<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UserTable extends Migration
{
    /**
     * Запуск миграции.
     *
     * @return void
     */
    public function up()
    {
        // Создаем таблицу `users`.
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('login');
            $table->string('password');
            $table->string('name');
            $table->string('api_token');
            $table->double('latitude')->nullable();     // Широта
            $table->double('longitude')->nullable();    // Долгота
            $table->timestamps();   // Поля created_at и updated_at будут автоматически созданы
        });
    }

    /**
     * Откат миграции.
     *
     * @return void
     */
    public function down()
    {
        // Удаляем таблицу users
        Schema::drop('users');
    }
}
