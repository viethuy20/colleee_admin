<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfferProgramAndCvPoints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offer_programs', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->integer('asp_id')->comment('ASP ID');
            $table->integer('ad_id')->comment('広告ID');
            $table->string('title')->comment('広告名');
            $table->string('app_id')->comment('アプリID');
            $table->integer('platform_type')->comment('プラットフォーム種別: 1:Web 2:Android 3:iOS');
            $table->integer('multi_course')->comment('マルチステップ: 0:単一 1:マルチステップ');
            $table->integer('revenue_type')->comment('案件種別: 1:定額 2:定率');
            $table->dateTime('publish_start_at')->nullable()->comment('配信開始日時');
            $table->dateTime('publish_end_at')->nullable()->comment('配信終了日時');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `offer_programs` comment 'オファーウォールプログラム'");

        Schema::create('offer_program_categories', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('offer_program_id')->comment('オファーウォールプログラムID');
            $table->integer('category_id')->comment('カテゴリID');
            $table->string('category_name')->nullable()->comment('カテゴリ名');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `offer_program_categories` comment 'オファーウォールプログラムカテゴリ'");

        Schema::create('offer_cv_points', function (Blueprint $table) {
            $table->bigIncrements('id')->comment('ID');
            $table->bigInteger('offer_program_id')->comment('オファーウォールプログラムID');
            $table->integer('aff_course_id')->nullable()->comment('成果地点の連携コースID');
            $table->string('course_name')->comment('成果地点名');
            $table->integer('point')->nullable()->comment('成果地点のポイント');
            $table->decimal('point_rate', 8, 6)->nullable()->comment('成果地点のポイント獲得率');
            $table->integer('revenue')->nullable()->comment('成果報酬⾦額');
            $table->decimal('revenue_rate', 8, 6)->nullable()->comment('成果報酬率');
            $table->integer('category_id')->comment('カテゴリID');
            $table->string('category_name')->comment('カテゴリ名');
            $table->timestamps();
        });
        DB::statement("ALTER TABLE `offer_cv_points` comment 'オファーウォール成果地点'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('offer_programs');
        Schema::dropIfExists('offer_program_categories');
        Schema::dropIfExists('offer_cv_points');
    }
}
