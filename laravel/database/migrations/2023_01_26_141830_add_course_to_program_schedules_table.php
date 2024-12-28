<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCourseToProgramSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_schedules', function (Blueprint $table) {
            $table->bigInteger('course_id')->nullable()->after('program_id')->commnet('コースID');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_schedules', function (Blueprint $table) {
            $table->dropColumn('course_id');
        });
    }
}
