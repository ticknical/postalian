<?php

namespace App\Http\Controllers\Api;

use App\Tables\PostalCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Class SearchController
 * 検索APIコントローラ
 *
 * @package App\Http\Controllers\Api
 */
class SearchController extends Controller
{
    /**
     * 指定された郵便番号から該当する住所を検索する
     *
     * @param PostalCode $postalCode
     * @param Request $request
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCode(PostalCode $postalCode, Request $request, $code)
    {
        $request['code'] = $code;

        $this->validate($request, [
            'code' => 'required|digits:7'
        ]);

        return app()->make(
            'JsonResponse\SearchCode', [
            'postal_codes' => Cache::remember("code.{$code}", 86400, function () use ($postalCode, $code) {
                return $postalCode
                    ->where(['postal_code' => $code])
                    ->get();
            })
        ]);
    }
}
