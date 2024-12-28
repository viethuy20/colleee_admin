<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVcUrlToAspaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('asps', function (Blueprint $table) {
            $table->string('url_parameter_name')->nullable()->after('url');
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
            $table->dropColumn('url_parameter_name');
            //
        });
    }
}
