<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUrlIntoAspsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asps', function (Blueprint $table) {
            $table->text('url')->nullable()->comment('URL')->after('status');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('asps', function (Blueprint $table) {
            $table->dropColumn('url');
            //
        });
    }
}
