# coachtech 勤怠管理アプリ

企業向けの勤怠管理アプリケーションです。一般ユーザーは出勤・休憩・退勤の打刻や勤怠情報の修正申請を行うことができ、管理者ユーザーは全ユーザーの勤怠確認・修正申請の承認・スタッフ管理を行うことができます。

## 主な機能
- 会員登録・ログイン（メール認証あり／一般ユーザー・管理者）
- 出勤・休憩・退勤の打刻
- 勤怠一覧・勤怠詳細の確認
- 勤怠情報の修正申請、および管理者による承認
- スタッフ一覧・スタッフ別月次勤怠一覧（CSV出力対応）
- マイ勤怠レポート（過去6ヶ月の勤怠統計）
- 勤怠データを外部から取得・操作できる公開API（Laravel Sanctum認証）

## 使用技術（実行環境）
- PHP 8.4
- Laravel 13.14.0
- nginx
- MySQL 8.0
- phpMyAdmin
- MailHog（メール認証確認用）
- Laravel Fortify（認証基盤）
- Laravel Sanctum（API認証）

## 環境構築

1. Dockerを起動する

2. プロジェクト直下で、以下のコマンドを実行する
```
make init
```

`make init` を実行すると、Dockerコンテナのビルド・起動、composer install、`.env`作成、`APP_KEY`生成、マイグレーション、ダミーデータ投入までが自動で行われます。

※ `.env.example` にはあらかじめDocker環境（MySQL・MailHog）用の接続情報を設定済みのため、個別に値を変更する必要はありません。

### リポジトリのクローン
```
git clone git@github.com:XXXXXXXX/kintai_app.git
cd kintai_app
```

## メール認証について
本アプリはMailHogを使用してメール認証を行います。ブラウザで [http://localhost:8025/](http://localhost:8025/) にアクセスすると、送信された認証メールを確認できます。

## ⚠️ テスト実行に関する注意（重要）
`php artisan test`（`make test`）を実行すると、テスト用にDBがリセットされ、開発用に投入していたダミーデータ（テストアカウントや勤怠データ）はすべて消えます。

**テストを実行した後は、必ず以下のコマンドでダミーデータを再作成してください。**
```
make fresh
```
または
```
docker-compose exec php php artisan migrate:fresh --seed
```

## テスト実行
```
make test
```
または
```
docker-compose exec php php artisan test
```

※ テスト実行後のDBリセットについては、上記「⚠️ テスト実行に関する注意」を参照してください。

## Makefileコマンド一覧
プロジェクト直下で `make [コマンド名]` を実行してください。

| コマンド | 内容 |
| --- | --- |
| `make build` | Dockerコンテナのビルド・起動（`docker-compose up -d --build`） |
| `make up` | Dockerコンテナの起動（`docker-compose up -d`） |
| `make down` | Dockerコンテナの停止（`docker-compose down`） |
| `make init` | Dockerビルド・起動〜ダミーデータ投入までの初期セットアップを一括実行 |
| `make migrate` | マイグレーションの実行 |
| `make fresh` | マイグレーションのやり直し＋ダミーデータ再作成（`migrate:fresh --seed`） |
| `make test` | PHPUnitテストの実行 |
| `make bash` | phpコンテナ内に入る（`docker-compose exec php bash`） |
| `make logs` | 各コンテナのログを表示 |

## 開発環境
- 勤怠登録画面（一般ユーザー）：http://localhost/
- 会員登録画面：http://localhost/register
- ログイン画面（一般ユーザー）：http://localhost/login
- ログイン画面（管理者）：http://localhost/admin/login
- phpMyAdmin：http://localhost:8080/（ユーザー名：`laravel_user` / パスワード：`laravel_pass`）
- MailHog：http://localhost:8025/

## テストアカウント
`make fresh`（または `php artisan migrate:fresh --seed`）実行後、以下のアカウントが作成されます。パスワードは全アカウント共通で `password` です。

| 名前 | メールアドレス | パスワード | 権限 | 備考 |
| --- | --- | --- | --- | --- |
| ユーザー1 | user1@example.com | password | 一般ユーザー | マイ勤怠レポート確認用の意図的な勤怠データを保持 |
| ユーザー2 | user2@example.com | password | 一般ユーザー | 通常勤務パターンの勤怠データを保持 |
| ユーザー3 | user3@example.com | password | 管理者ユーザー | `admin_status = true` |

いずれのアカウントもメール認証済みの状態で作成されます。

## 公開API
外部アプリケーションが勤怠データを取得・操作できるAPIを `routes/api.php` の `v1` プレフィックス配下に用意しています。

| メソッド | URI | 説明 | 認証 |
| --- | --- | --- | --- |
| GET | /api/v1/attendance-records | 勤怠一覧取得 | 不要 |
| GET | /api/v1/attendance-records/{attendanceRecord} | 勤怠詳細取得 | 不要 |
| POST | /api/v1/attendance-records | 勤怠登録 | Sanctum必須 |
| PUT / PATCH | /api/v1/attendance-records/{attendanceRecord} | 勤怠更新 | Sanctum必須（本人または管理者のみ） |
| DELETE | /api/v1/attendance-records/{attendanceRecord} | 勤怠削除 | Sanctum必須（本人または管理者のみ） |

書き込み系エンドポイントには `auth:sanctum` ミドルウェアを適用し、更新・削除は `AttendanceRecordPolicy` により本人または管理者のみ許可しています。

## テーブル仕様

### usersテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| name | varchar(255) |  |  | ◯ |  |
| email | varchar(255) |  | ◯ | ◯ |  |
| email_verified_at | timestamp |  |  |  |  |
| password | varchar(255) |  |  | ◯ |  |
| admin_status | boolean |  |  | ◯ |  |
| remember_token | varchar(100) |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### attendance_recordsテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| user_id | bigint unsigned |  |  | ◯ | users(id) |
| date | date |  |  | ◯ |  |
| clock_in | time |  |  |  |  |
| clock_out | time |  |  |  |  |
| comment | text |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### attendance_breaksテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| attendance_record_id | bigint unsigned |  |  | ◯ | attendance_records(id) |
| break_in | time |  |  |  |  |
| break_out | time |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### stamp_correction_requestsテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| attendance_record_id | bigint unsigned |  |  | ◯ | attendance_records(id) |
| user_id | bigint unsigned |  |  | ◯ | users(id) |
| new_clock_in | time |  |  |  |  |
| new_clock_out | time |  |  |  |  |
| new_comment | text |  |  |  |  |
| is_approved | boolean |  |  | ◯ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### correction_breaksテーブル
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| stamp_correction_request_id | bigint unsigned |  |  | ◯ | stamp_correction_requests(id) |
| new_break_in | time |  |  |  |  |
| new_break_out | time |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

### personal_access_tokensテーブル（Laravel Sanctumが自動生成）
| カラム名 | 型 | primary key | unique key | not null | foreign key |
| --- | --- | --- | --- | --- | --- |
| id | bigint unsigned | ◯ |  | ◯ |  |
| tokenable_type | varchar(255) |  |  | ◯ |  |
| tokenable_id | bigint unsigned |  |  | ◯ | ポリモーフィック関連（users等） |
| name | text |  |  | ◯ |  |
| token | varchar(64) |  | ◯ | ◯ |  |
| abilities | text |  |  |  |  |
| last_used_at | timestamp |  |  |  |  |
| expires_at | timestamp |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

## ER図
![ER図](kintai_er_diagram%20.png)

## UIについての変更点（コーチに相談済み）
- マイ勤怠レポート画面は、参考デザインをそのまま実装すると既存画面とUIデザインが大きく異なり複雑になってしまうため、基本的なレイアウト（既存画面の構成）は変更せず、既存のUIデザインに寄せる形で実装している。
- 勤怠一覧画面の「詳細」ボタンは、今回の要件では打刻漏れを許容しないため、打刻がある日のみ表示されるようにしている。
- 修正申請後・承認前の勤怠詳細画面について、参考デザインでは管理者側に空欄の休憩2が描かれていたが、一般ユーザー側の参考デザインには空欄の休憩枠が含まれていなかったため、一般ユーザーのデザインに統一している。

