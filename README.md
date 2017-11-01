# postalian

postalianは自分用の郵便番号APIサーバーの作成・公開を支援するスケルトンです。

## 必須条件

- [PHP](http://php.net/) 7.0+
- [Lumen](https://lumen.laravel.com/) 5.5.*
- [Guzzle](http://docs.guzzlephp.org/en/latest/) 5.3.*

## インストール

### Composer

```bash
$ composer create-project --prefer-dist ticknical/postalian [your project name]
```

## 使用方法

### 郵便番号CSVの取り込み

日本郵便からのCSVダウンロード・取り込み・取得前後のデータ加工までをコマンド一発で行います。

```bash
$ php artisan postalcode:storeall
```

ダウンロードした郵便番号CSVについては、少しデータを加工して取り込んでいます。

- 町域名が多すぎて複数行に分割されている場合はマージ
- 「以下に掲載がない場合」を空文字へ置換
- 「＊＊の次に番地がくる場合」を空文字へ置換
- 「＊＊一円」を空文字へ置換

### 簡単な郵便番号API

簡単な郵便番号APIを公開できます。

例えば`http://[domain]/api/search/code/1000001`にアクセスすると、以下のJSONが返却されます。

```json
{
    "status": true,
    "version": "2017-10-31",
    "postal_codes": [
        {
            "postal_code": "1000001",
            "prefecture": "東京都",
            "city": "千代田区",
            "street": "千代田"
        }
    ]
}
```

## ライセンス

Copyright &copy; 2017 Tick Licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)