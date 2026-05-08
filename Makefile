.PHONY: help install up down restart logs clean setup

help: ## Show this help message
	@echo 'Usage: make [target]'
	@echo ''
	@echo 'Available targets:'
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

setup: ## Full project setup from scratch (install dependencies, build, migrate)
	@echo "🚀 Starting project setup..."
	@echo "📝 Creating .env file from .env.example..."
	@if not exist .env copy .env.example .env
	@echo "📦 Installing PHP dependencies..."
	docker-compose run --rm composer install
	@echo "🔑 Generating application key..."
	docker-compose run --rm composer php artisan key:generate
	@echo "📦 Installing Node.js dependencies..."
	docker-compose run --rm node npm install
	@echo "🏗️  Building frontend for production..."
	docker-compose run --rm node npm run build
	@echo "🐳 Starting Docker containers..."
	docker-compose up -d
	@echo "⏳ Waiting for MySQL to be ready..."
	@timeout /t 10 /nobreak > nul
	@echo "🗄️  Running database migrations..."
	docker exec file_storage_php php artisan migrate --force
	@echo "✅ Project setup complete!"
	@echo ""
	@echo "🌐 Application is running at: http://localhost:8080"
	@echo "🐰 RabbitMQ Management UI: http://localhost:15672 (admin/admin123)"

install: ## Install dependencies only
	@echo "📦 Installing dependencies..."
	docker-compose run --rm composer install
	docker-compose run --rm node npm install

build: ## Build frontend assets
	@echo "🏗️  Building frontend..."
	docker-compose run --rm node npm run build

up: ## Start Docker containers
	@echo "🐳 Starting containers..."
	docker-compose up -d

down: ## Stop Docker containers
	@echo "🛑 Stopping containers..."
	docker-compose down

restart: ## Restart Docker containers
	@echo "🔄 Restarting containers..."
	docker-compose restart

logs: ## Show container logs
	docker-compose logs -f

clean: ## Clean up containers, volumes, and built assets
	@echo "🧹 Cleaning up..."
	docker-compose down -v
	rm -rf vendor node_modules public/build

migrate: ## Run database migrations
	@echo "🗄️  Running migrations..."
	docker exec file_storage_php php artisan migrate

test: ## Run tests
	@echo "🧪 Running tests..."
	docker exec file_storage_php php artisan config:clear
	docker exec file_storage_php php artisan test

shell-php: ## Open shell in PHP container
	docker exec -it file_storage_php bash
