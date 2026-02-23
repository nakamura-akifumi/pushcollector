# PushCollector

HTTP の GET/POST で送られてきたリクエスト（ヘッダ・ボディ）を保存し、内容の確認や別の URL へのリレー送信ができるアプリです。

- **PHP 8.1+** / **Symfony 6.4** / **SQLite**

## セットアップ

```bash
composer install
php bin/console doctrine:schema:update --force
```

## 起動

```bash
php -S 127.0.0.1:8080 -t public
```

ブラウザで `http://127.0.0.1:8080` にアクセスすると受信一覧に遷移します。

## 使い方

### 受信エンドポイント `/in`

- **GET** または **POST** で `http://localhost:8080/in` にアクセスすると、そのリクエストが記録されます。
- 記録される内容（シリアライズして DB に保存）:
  - メソッド・Request URI
  - **アクセス元 IP**（`X-Forwarded-For` / `X-Real-IP` 対応）
  - **HTTP ヘッダ**（すべて）
  - **ボディ**（生データ ＋ Content-Type に応じた解析: JSON / form-urlencoded / multipart）
  - クエリパラメータ、関連サーバ変数

例:

```bash
# GET
curl "http://localhost:8080/in?foo=bar"

# POST (JSON)
curl -X POST http://localhost:8080/in -H "Content-Type: application/json" -d '{"key":"value"}'
```

### 受信一覧・詳細

- **`/inbox`** … 保存したリクエストの一覧（新しい順）
- **`/inbox/{id}`** … 指定 ID の詳細（ヘッダ・ボディ生・ボディ解析・クエリ・サーバ変数）

### リレー

- 詳細画面または一覧から「リレー」を選ぶと、そのリクエストを**別の URL に同じメソッド・ヘッダ・ボディで再送信**できます。
- 送信先 URL を入力して「送信」で実行し、レスポンスのステータス・ヘッダ・ボディを確認できます。

## 設定

- **DB**: `.env` の `DATABASE_URL`（既定は `sqlite:///%kernel.project_dir%/var/data.db`）
- **APP_ENV** / **APP_SECRET**: `.env` で変更可能

## 構成概要

| 役割 | 説明 |
|------|------|
| `CaptureController` | `/in` で GET/POST を受けて保存 |
| `HttpCaptureService` | リクエストから IP・ヘッダ・ボディを解析し JSON でシリアライズ |
| `HttpCapture` エンティティ | メソッド・URI・IP・日時・ペイロード（JSON）を保持 |
| `InboxController` | 一覧・詳細表示 |
| `RelayController` | 保存済みリクエストのリレー送信 |
