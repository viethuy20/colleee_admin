<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseToAffRewardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aff_rewards', function (Blueprint $table) {
            $table->string('aff_course_id', 256)->nullable()->comment('連携コースID')->after('affiriate_id');
            $table->string('course_name', 256)->nullable()->comment('コース名')->after('aff_course_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aff_rewards', function (Blueprint $table) {
            $table->dropColumn('aff_course_id');
            $table->dropColumn('course_name');
        });
    }
}
