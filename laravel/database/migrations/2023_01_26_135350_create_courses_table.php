<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('program_id')->comment('プログラムID');
            $table->string('aff_course_id', 256)->nullable()->comment('連携コースID');
            $table->string('course_name', 256)->comment('コース名');
            $table->integer('status')->default(0)->comment('状態');
            $table->integer('priority')->default(1)->comment('表示順');
            $table->datetime('created_at')->comment('作成日時');
            $table->datetime('updated_at')->comment('更新日時');
            $table->timestamp('deleted_at')->nullable()->comment('削除日時');
        });
        DB::statement("ALTER TABLE `courses` comment 'コース情報'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('courses');
    }
}
