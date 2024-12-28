<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGoogleIdToUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->string('google_id')->nullable()->after('line_id');
            $table->string('carriers')->nullable()->after('line_id');
            $table->integer('sex')->nullable()->change();
            $table->integer('prefecture_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn('google_id');
            $table->dropColumn('carriers');
            $table->integer('sex')->nullable(false)->change();
            $table->integer('prefecture_id')->nullable(false)->change();
        });
    }
}
