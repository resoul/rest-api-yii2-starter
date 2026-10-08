# HTTP REST API Service

Yii2-based REST API service with queue support and modern architecture.

## Requirements

- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer 2.x

## Installation

```bash
# Install dependencies
composer install

# Copy environment config
cp app/etc/env.test.php app/etc/env.php

# Edit environment variables
nano app/etc/env.php

# Apply application and queue migrations
php bin/middleware migrate --interactive=0

# Start queue worker
php bin/middleware queue/listen
```

## Database migrations

Application migrations live in `app/migrations`. The starter includes an
example migration that creates an `example_record` table; replace or remove it
when adapting the project. Queue migrations remain in
`app/code/Middleware/Framework/Queue/Migration` and are discovered through
their migration namespace.

Create a migration with:

```bash
php bin/middleware migrate/create add_example_field
```

Apply pending migrations with `php bin/middleware migrate` and roll back the
last migration with `php bin/middleware migrate/down`.

## Configuration

Edit `app/etc/env.php`:

- Database credentials
- Mailer settings
- Cookie validation key (generate with `php -r "echo bin2hex(random_bytes(32));"`)
- Timezone and language

## API Endpoints

- `GET /` - Get API version
- `GET /health` - Health check

## Queue

Process jobs in background:

```bash
php bin/middleware queue/listen
```

## Development

Run local server:

```bash
php -S localhost:8080 -t public
```

## Project Structure

```
app/
  code/           - Application code
    Middleware/   - Framework and modules
  etc/            - Configuration files
bin/              - CLI scripts
public/           - Web root
runtime/          - Runtime files (logs, cache)
vendor/           - Composer dependencies
```
