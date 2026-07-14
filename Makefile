.PHONY: init build up down migrate fresh test bash logs

# Docker起動〜ダミーデータ投入までを一括セットアップ（テストは含まない）
init: build
	docker-compose exec php composer install
	docker-compose exec php cp -n .env.example .env
	docker-compose exec php php artisan key:generate
	@sleep 10
	docker-compose exec php php artisan migrate
	docker-compose exec php php artisan db:seed

# Dockerコンテナのビルド・起動
build:
	docker-compose up -d --build

# Dockerコンテナの起動
up:
	docker-compose up -d

# Dockerコンテナの停止
down:
	docker-compose down

# マイグレーションの実行
migrate:
	docker-compose exec php php artisan migrate

# マイグレーションのやり直し＋ダミーデータ再作成
fresh:
	docker-compose exec php php artisan migrate:fresh --seed

# PHPUnitテストの実行
test:
	docker-compose exec php php artisan test

# phpコンテナ内に入る
bash:
	docker-compose exec php bash

# 各コンテナのログを表示
logs:
	docker-compose logs -f
