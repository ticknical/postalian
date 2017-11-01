<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreatePostalCodesTable
 * 郵便番号を格納するテーブルを作成する
 */
class CreatePostalCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postal_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('lpe_code', 10)->comment('全国地方公共団体コード(JIS X0401/X0402)');
            $table->string('old_postal_code', 5)->comment('旧郵便番号（5桁）');
            $table->string('postal_code', 7)->comment('郵便番号');
            $table->string('prefecture', 10)->comment('都道府県');
            $table->string('prefecture_kana', 20)->comment('都道府県（かな）');
            $table->string('city', 30)->comment('市区町村');
            $table->string('city_kana', 60)->comment('市区町村（かな）');
            $table->string('street', 1024)->comment('町域');
            $table->string('street_kana', 1024)->comment('町域（かな）');
            $table->boolean('is_multi_code_area')->comment('一町域が二以上の郵便番号で表される場合の表示（1:該当 / 0:該当せず）');
            $table->boolean('is_koaza_per_code')->comment('小字毎に番地が起番されている町域の表示（1:該当 / 0:該当せず）');
            $table->boolean('is_have_choume_area')->comment('丁目を有する町域の場合の表示（1:該当 / 0:該当せず）');
            $table->boolean('is_multi_area_code')->comment('丁目を有する町域の場合の表示（1:該当 / 0:該当せず）');
            $table->tinyInteger('is_updated')->comment('更新の表示（0:変更なし / 1:変更あり / 2:廃止（廃止データのみ使用））');
            $table->tinyInteger('updated_reason')->comment('変更理由　（0:変更なし / 1:市政・区政・町政・分区・政令指定都市施行 / 2:住居表示の実施 / 3:区画整理 / 4:郵便区調整等 / 5:訂正 / 6:廃止（廃止データのみ使用））');
            $table->timestamps();

            $table->index('postal_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('postal_codes');
    }
}
