<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddQuestionCampaignAdtitleAddetailIntoProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            //
            $table->text('ad_title')->nullable()->comment('広告主タイトル');
            $table->text('ad_detail')->nullable()->comment('広告主テキスト');



        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            //
            $table->dropColumn('ad_title');
            $table->dropColumn('ad_detail');
        });
    }
}
