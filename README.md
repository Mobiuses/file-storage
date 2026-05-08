# File Storage

Web application for storing PDF and DOCX files with automatic deletion after 24 hours.

## Tested docker build setup on

| OS | Status |
|---|---|
| Windows 10 | ✅ |
| Ubuntu 22.04 LTS | ✅ |
| macOS | ❓ |
## Quick Start

### Using Make (recommended)

```bash
# Full project setup and launch
make setup
```

This command will:
- Create .env file from .env.example
- Install PHP dependencies
- Generate APP_KEY
- Install Node.js dependencies
- Build frontend for production
- Start Docker containers
- Run database migrations

### Without Make (alternative method)

```bash
# 1. Create .env file
cp .env.example .env

# 2. Install PHP dependencies
docker-compose run --rm php composer install

# 3. Generate APP_KEY
docker-compose run --rm php php artisan key:generate

# 4. Install Node.js dependencies
docker-compose run --rm node npm install

# 5. Build frontend
docker-compose run --rm node npm run build

# 6. Start Docker containers
docker-compose up -d

# 7. Run migrations (wait 10 seconds after starting containers)
docker exec file_storage_php php artisan migrate
```

### Application Access

- **Frontend**: http://localhost:8080
- **API**: http://localhost:8080/api/files
- **RabbitMQ Management UI**: http://localhost:15672 (admin/admin123)

## Running Services

Docker Compose starts the following containers:
- **MySQL** - database (port 3306)
- **RabbitMQ** - message queue (ports 5672, 15672)
- **PHP-FPM** - PHP processing
- **Nginx** - web server (port 8080)
- **Consumer** - RabbitMQ notification processing
- **Scheduler** - automatic expired file deletion

**Additional containers for development:**
- `composer` - PHP dependency management (tools profile)
- `node` - frontend build (tools profile)

These containers are started on demand via `docker-compose run --rm`.

## Make Commands

```bash
make help          # Show all available commands
make setup         # Full project setup from scratch
make install       # Install dependencies only
make build         # Build frontend
make up            # Start containers
make down          # Stop containers
make restart       # Restart containers
make logs          # Show container logs
make migrate       # Run migrations
make test          # Run tests
make clean         # Clean containers, volumes and built files
make shell-php     # Open shell in PHP container
```

## Development

### Frontend Build

```bash
# Using Make
make build

# Without Make - for development (with hot reload)
docker-compose run --rm node npm run dev

# Without Make - for production
docker-compose run --rm node npm run build
```

### Running Tests

```bash
# Using Make
make test

# Without Make - all tests
docker exec file_storage_php php artisan test

# Unit tests only
docker exec file_storage_php php artisan test --testsuite=Unit

# Feature tests only
docker exec file_storage_php php artisan test --testsuite=Feature

# With verbose output
docker exec file_storage_php php artisan test --verbose

# Specific test
docker exec file_storage_php php artisan test --filter=FileUploadTest

# With code coverage (requires Xdebug)
docker exec file_storage_php php artisan test --coverage
```

### Monitoring

```bash
# Using Make
make logs

# Without Make - all logs
docker-compose logs -f

# Specific service logs
docker logs -f file_storage_consumer
docker logs -f file_storage_scheduler

# Restart services
docker restart file_storage_consumer
docker restart file_storage_scheduler
```

### Container Management

```bash
# Using Make
make up            # Start
make down          # Stop
make restart       # Restart
make clean         # Full cleanup

# Without Make
docker-compose up -d              # Start
docker-compose down               # Stop
docker-compose restart            # Restart
docker-compose down -v            # Stop and remove volumes
```

## Artisan Commands

```bash
# Manually delete expired files
docker exec file_storage_php php artisan files:delete-expired

# Start RabbitMQ consumer (already running in container)
docker exec file_storage_php php artisan rabbitmq:consume-files

# Start scheduler (already running in container)
docker exec file_storage_php php artisan schedule:work
```

## Test Coverage

Total: **58 tests, 169 assertions**

**Unit Tests (35 tests):**
- ✅ File Module DTO (6 tests)
- ✅ File Module Managers (7 tests)
- ✅ File Module Repositories (8 tests)
- ✅ File Module Services (6 tests)
- ✅ Notification Module Console (2 tests) - failed jobs handling
- ✅ Notification Module Listeners (2 tests)
- ✅ Notification Module Services (1 test)
- ✅ Scheduler Module Services (3 tests)

**Feature Tests (23 tests):**
- ✅ File Upload API (6 tests) - validation, file types, size
- ✅ File List API (5 tests) - list retrieval, data structure
- ✅ File Download API (3 tests) - download, error handling
- ✅ File Delete API (3 tests) - deletion, events
- ✅ Commands (4 tests) - automatic expired file deletion
- ✅ Basic Test (1 test) - application health check
- ✅ Scheduler Command (1 test) - task scheduler verification

## Error Handling

### RabbitMQ Failed Jobs

The application implements a robust error handling mechanism for RabbitMQ message processing:

**Retry Mechanism:**
- Failed messages are automatically retried up to 3 times
- Each retry attempt is tracked via message headers (`x-retry-count`)
- Exponential backoff can be configured if needed

**Failed Jobs Logging:**
- After 3 failed attempts, the message is logged to the `failed_jobs` table
- Logged information includes:
  - Queue name
  - Full message payload
  - Exception message and stack trace
  - Timestamp of failure
- Failed jobs can be inspected via database queries or Laravel Horizon (if installed)

**Monitoring:**
```bash
# View failed jobs in database
docker exec file_storage_php php artisan tinker
>>> DB::table('failed_jobs')->get();

# Check consumer logs
docker logs -f file_storage_consumer
```
