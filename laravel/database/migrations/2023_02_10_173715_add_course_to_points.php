<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseToPoints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('points', function (Blueprint $table) {
            $table->bigInteger('course_id')->nullable()->comment('コースID')->after('program_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('points', function (Blueprint $table) {
            $table->dropColumn('course_id');
        });
    }
}
