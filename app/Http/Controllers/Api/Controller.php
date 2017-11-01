<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

/**
 * Class Controller
 * API系コントローラの基底クラス
 *
 * @package App\Http\Controllers\Api
 */
class Controller extends BaseController
{
    /**
     * バリデーションエラーが発生した場合のJSONレスポンス
     *
     * @param Request $request
     * @param array $errors
     * @return mixed
     */
    protected function buildFailedValidationResponse(Request $request, array $errors)
    {
        return app()->make('JsonResponse\Error\Validation', [
            'errors'   => $errors,
            'instance' => $request->fullUrl()
        ]);
    }
}
