<?php

namespace App\Console\Commands\PostalCode;

use App\Tables\PostalCode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Class StoreAll
 * 日本郵便株式会社が公開している最新のKEN_ALL.csvをDBに取り込むコマンド
 *
 * @package App\Console\Commands\PostalCode
 * @see http://www.post.japanpost.jp/zipcode/dl/kogaki-zip.html 最新のCSVファイル
 */
class StoreAll extends Command
{
    /**
     * コマンドの作業ステップ
     *
     * @var int
     */
    const STEPS_COUNT = 5;

    /**
     * 郵便番号Zipのフルパス
     *
     * @var string
     */
    const KEN_ALL_CSV_URL = 'http://www.post.japanpost.jp/zipcode/dl/kogaki/zip/ken_all.zip';

    /**
     * コマンド名
     *
     * @var string
     */
    protected $name = 'postalcode:storeall';

    /**
     * コマンドの説明
     *
     * @var string
     */
    protected $description = '日本郵便株式会社が公開している最新のKEN_ALL.csvを郵便番号DBに取り込みます。';

    /**
     * 郵便番号Table
     *
     * @var PostalCode
     */
    protected $postalCode = null;

    /**
     * StoreAllPostalCode constructor.
     *
     * @param PostalCode $postalCode
     */
    public function __construct(PostalCode $postalCode)
    {
        parent::__construct();

        $this->postalCode = $postalCode;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // nkfがインストールされているか確認
        if (!app()->make('CheckInstalledCommand\CheckInstalledNkf'))
        {
            $this->error('NKFがインストールされていませんでした。');
            return;
        }

        // データがすでに入っている場合、テーブルを全消去してもいいか確認する
        if ($this->postalCode->needsTruncate())
        {
            if (!$this->confirm('テーブルをtruncateしますがよろしいですか？'))
            {
                $this->error('処理を終了します。');
                return;
            }

            $this->postalCode->query()->truncate();
        }

        $start_time = microtime(true);

        $bar = $this->output->createProgressBar(self::STEPS_COUNT);

        try
        {
            $bar->advance();
            $this->line('最新の郵便番号zipをダウンロードします。');
            $this->fetchPostalCodeZip();
        }
        catch (ClientException $e)
        {
            $this->error('郵便番号Zipのダウンロードに失敗しました。');
            return;
        }
        catch (\RuntimeException $e)
        {
            $this->error($e->getMessage());
            return;
        }

        $bar->advance();
        $this->line('解凍したCSVを加工します。');
        $this->execPreInsertPostalCodeCsv();

        $bar->advance();
        $this->line('加工したCSVをDBに取り込みます。');
        $this->execInsertPostalCodeCsv();

        $bar->advance();
        $this->line('DBに取り込んだデータを諸々加工します。');
        $this->execAfterInsertPostalCodeCsv();

        $bar->advance();
        $this->line('作業ディレクトリを消去します。');
        $this->execFlushTempDir();

        $time = microtime(true) - $start_time;
        $this->info("郵便番号CSVの取り込みが完了しました（{$time}秒）。");
    }

    /**
     * 一時ディレクトリのパスを返却する
     *
     * @return string
     */
    protected function getTempDir(): string
    {
        return sys_get_temp_dir().'/postalapi';
    }

    /**
     * 配布されている郵便番号zipを保存するファイルパスを返却する
     *
     * @return string
     */
    protected function getTempZipPath(): string
    {
        return $this->getTempDir().'/ken_all.zip';
    }

    /**
     * 解凍した郵便番号CSVのファイルパスを返却する
     *
     * @return string
     */
    protected function getTempCsvPath(): string
    {
        return $this->getTempDir().'/KEN_ALL.CSV';
    }

    /**
     * 日本郵便から最新の郵便番号CSVを取得する
     *
     * @return bool
     */
    protected function fetchPostalCodeZip(): bool
    {
        try
        {
            if (!@mkdir($this->getTempDir()))
            {
//                throw new \RuntimeException('一時ディレクトリの作成に失敗しました。');
            }
        }
        catch (\ErrorException $e)
        {
            throw new \RuntimeException(
                '一時ディレクトリがすでに存在しているか、ディレクトリの作成権限がありません。',
                $e->getCode(),
                $e
            );
        }

        $handle = fopen($this->getTempZipPath(), 'w');
        $response = (new Client())->get(self::KEN_ALL_CSV_URL, [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FILE => $handle
            ]
        ]);
        fclose($handle);

        app()->make(
            'SetEnv\SetEnv', [
            'key'   => 'POSTAL_CODE_VERSION',
            'value' => (new \DateTime($response->getHeader('Last-Modified')[0]))
                ->setTimeZone(new \DateTimeZone(getenv('APP_TIMEZONE')))
                ->format('Y-m-d')
        ]);

        $zip = new \ZipArchive();

        if ($zip->open($this->getTempZipPath()) !== TRUE)
        {
            throw new \RuntimeException('郵便番号Zipを開けませんでした。');
        }

        $zip->extractTo($this->getTempDir());
        $zip->close();

        return true;
    }

    /**
     * 郵便番号CSV取り込み処理の前処理
     *
     * @return bool
     */
    protected function execPreInsertPostalCodeCsv(): bool
    {
        // nkfでCSVをUTF-8に変換、半角カタカナを全角カタカナに置換
        exec("nkf -w --overwrite {$this->getTempCsvPath()}");

        // 「以下に掲載がない場合」を削除
        exec(implode(' ', [
            'sed -i',
            '-e "s/以下に掲載がない場合//g"',
            '-e "s/イカニケイサイガナイバアイ//g"',
            $this->getTempCsvPath()
        ]));

        return true;
    }

    /**
     * 郵便番号CSVをDBに取り込む
     *
     * @return bool
     */
    protected function execInsertPostalCodeCsv(): bool
    {
        DB::connection()->getPdo()->exec(implode(' ', [
            'LOAD DATA LOCAL INFILE',
            "'{$this->getTempCsvPath()}'",
            'INTO TABLE postal_codes',
            "FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY ''",
            'LINES STARTING BY \'\' TERMINATED BY \'\r\n\'',
            '(
                lpe_code,
                old_postal_code,
                postal_code,
                prefecture_kana,
                city_kana,
                street_kana,
                prefecture,
                city,
                street,
                is_multi_code_area,
                is_koaza_per_code,
                is_have_choume_area,
                is_multi_area_code,
                is_updated,
                updated_reason
            )',
            'SET created_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP',
        ]));

        return true;
    }

    /**
     * 郵便番号CSV取り込み処理の後処理
     *
     * @return bool
     */
    protected function execAfterInsertPostalCodeCsv(): bool
    {
        $this->postalCode->mergeSeparatedStreetRecord();
        $this->postalCode->deleteNextAddressStreet();
        $this->postalCode->deleteThroughoutStreet();

        return true;
    }

    /**
     * 一時ディレクトリをクリーンアップします
     *
     * @return bool
     */
    protected function execFlushTempDir(): bool
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->getTempDir(),
                \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ( $files as $file )
        {
            if ( $file->isDir() === true )
            {
                rmdir($file->getPathname());
            }
            else
            {
                unlink($file->getPathname());
            }
        }

        rmdir($this->getTempDir());

        return true;
    }
}
