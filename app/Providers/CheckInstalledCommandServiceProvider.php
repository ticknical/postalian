<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Class CheckInstalledCommandServiceProvider
 * 特定のコマンドをインストールしているか検査する機能を提供するサービスプロバイダー
 *
 * @package App\Providers
 */
class CheckInstalledCommandServiceProvider extends ServiceProvider
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
        $this->app->bind('CheckInstalledCommand\CheckInstalledNkf', function()
        {
            system('nkf --version 1>/dev/null 2>/dev/null', $code);
            return $code === 0;
        });
    }
}
