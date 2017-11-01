<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\ServiceProvider;

/**
 * Class JsonResponseServiceProvider
 * APIで返却するJSONの型を管理するサービスプロバイダー
 *
 * @package App\Providers
 */
class JsonResponseServiceProvider extends ServiceProvider
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
        // エラーレスポンス時のテンプレート
        $this->app->bind('JsonResponse\Error', function($app, $params = []) {
            return new JsonResponse([
                'type'     => $params['type'],
                'title'    => $params['title'],
                'detail'   => $params['detail'],
                'instance' => $params['instance'],
            ], $params['response_code']);
        });

        // バリデーションエラー時のエラーレスポンス
        $this->app->bind('JsonResponse\Error\Validation', function($app, $params = []) {
            return $this->app->make('JsonResponse\Error', [
                'type'          => null,
                'title'         => 'Validation error.',
                'detail'        => $params['errors'],
                'instance'      => $params['instance'],
                'response_code' => 422
            ]);
        });

        // 郵便番号検索APIのレスポンス
        $this->app->bind('JsonResponse\SearchCode', function($app, $params = []) {
            $postal_codes = $params['postal_codes']->map(
                function ($v) {
                    return [
                        'postal_code' => $v->postal_code,
                        'prefecture'  => $v->prefecture,
                        'city'        => $v->city,
                        'street'      => $v->street,
                    ];
                }
            )->toArray();

            return new JsonResponse([
                'status'       => $params['postal_codes']->count() > 0,
                'version'      => getenv('POSTAL_CODE_VERSION'),
                'postal_codes' => $postal_codes,
            ], 200);
        });

    }
}
