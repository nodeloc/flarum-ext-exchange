<?php

use Illuminate\Database\Schema\Blueprint;

use Flarum\Database\Migration;

return Migration::createTable(
    'user_exchange_history',
    function (Blueprint $table) {
        $table->increments('id');

        $table->integer('user_id')->index();
        $table->double('money')->default(0)->comment("支出");
        $table->double('credits')->default(0)->comment("积分");
        $table->timestamps();

    }
);
