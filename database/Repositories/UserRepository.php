<?php

namespace Database\Repositories;

use Docile\Database\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserRepository implements Repository
{
    public static function up()
    {
        Capsule::schema()->create("users", function ($table) {
            $table->bigIncrements("id");

            $table->string('first_name');
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('last_name')->nullable();
            $table->text('avatar')->nullable();
            $table->text('phone_number')->nullable();
            $table->string('password')->nullable();
            $table->tinyInteger('status')->default(1);

            $table->rememberToken();

            $table->timestamps();
        });
    }
}
