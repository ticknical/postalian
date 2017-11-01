<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class SetEnvServiceProvider
 * .envに値を追加する機能を提供するサービスプロバイダー
 *
 * @package App\Providers
 */
class SetEnvServiceProvider extends ServiceProvider
{
    /**
     * プロバイダのローディングを遅延させるフラグ
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // NKFがインストールされているか確認するサービスコンテナ
        $this->app->bind('SetEnv\SetEnv', function($app, $params = [])
        {
            $path = base_path('.env');

            if (!file_exists($path))
            {
                return;
            }

            $prev_env = getenv($params['key']);

            if ($prev_env !== false)
            {
                file_put_contents($path, str_replace(
                    "{$params['key']}=" . getenv($params['key']),
                    "{$params['key']}=".$params['value'],
                    file_get_contents($path)
                ));

                return;
            }

            file_put_contents($path, "{$params['key']}=".$params['value'], FILE_APPEND);
        });
    }
}
