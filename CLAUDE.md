# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12.x (framework version) application running on PHP 8.4.8 with Composer 2.8.9. The project uses SQLite as the default database and includes Vite with TailwindCSS 4.0 for frontend asset compilation.

## Development Commands

### Setup & Installation
```bash
# Initial setup (install dependencies, generate key, run migrations, build assets)
composer run setup

# Install PHP dependencies only
composer install

# Install frontend dependencies
npm install

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate
```

### Development Server
```bash
# Start development environment (runs server, queue, logs viewer, and vite concurrently)
composer run dev

# Or start services individually:
php artisan serve              # Start development server (http://localhost:8000)
php artisan queue:listen       # Start queue worker
php artisan pail              # View logs in real-time
npm run dev                   # Start Vite dev server
```

### Testing
```bash
# Run all tests
composer run test

# Run PHPUnit directly
php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run tests with coverage
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Fix specific files
./vendor/bin/pint app/Models/User.php
```

### Database
```bash
# Create migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh database with seeders
php artisan migrate:fresh --seed

# Create seeder
php artisan make:seeder TableNameSeeder

# Run seeders
php artisan db:seed
```

### Artisan Generators
```bash
# Create controller
php artisan make:controller ControllerName

# Create model with migration
php artisan make:model ModelName -m

# Create model with migration, factory, and seeder
php artisan make:model ModelName -mfs

# Create request (form validation)
php artisan make:request RequestName

# Create middleware
php artisan make:middleware MiddlewareName

# Create job
php artisan make:job JobName

# Create event
php artisan make:event EventName

# Create listener
php artisan make:listener ListenerName
```

### Frontend
```bash
# Build assets for production
npm run build

# Watch and rebuild assets
npm run dev
```

### Cache & Optimization
```bash
# Clear all caches
php artisan optimize:clear

# Clear specific caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Architecture

### Application Structure

**Laravel 12 uses a streamlined bootstrap configuration** in `bootstrap/app.php` with a fluent API for configuring routing, middleware, and exception handling. This replaces the traditional HTTP kernel approach.

**Routes:**
- `routes/web.php` - Web routes with session/CSRF middleware
- `routes/console.php` - Artisan console commands
- Health check endpoint automatically registered at `/up`

**Application Layers:**
- `app/Http/Controllers/` - Request handlers
- `app/Models/` - Eloquent ORM models
- `app/Providers/` - Service providers (AppServiceProvider is the main one)
- `app/Http/Middleware/` - HTTP middleware (configured in bootstrap/app.php)

**Database:**
- Default connection: SQLite (`database/database.sqlite`)
- Migrations: `database/migrations/`
- Factories: `database/factories/`
- Seeders: `database/seeders/`

**Frontend:**
- Vite configuration: `vite.config.js`
- TailwindCSS 4.0 via `@tailwindcss/vite`
- Entry point: `resources/js/app.js`
- Views: `resources/views/`

**Storage:**
- `storage/app/` - File storage
- `storage/logs/` - Application logs
- `storage/framework/` - Framework generated files (cache, sessions, views)

### Key Configuration Files

- `.env` - Environment variables (database, mail, cache, queue configuration)
- `composer.json` - PHP dependencies and scripts
- `package.json` - Frontend dependencies
- `phpunit.xml` - PHPUnit test configuration
- `config/` - Application configuration files

### Service Container & Dependency Injection

Laravel uses automatic dependency injection. Type-hint dependencies in constructors and methods, and the container will resolve them:

```php
public function __construct(UserRepository $users)
{
    $this->users = $users;
}
```

### Queue System

Default queue connection is `database`. Jobs are stored in the `jobs` table.

```bash
# Process queued jobs
php artisan queue:work

# Process jobs with automatic restart on code changes
php artisan queue:listen
```

### Session & Cache

- Sessions: Database-backed (default)
- Cache: Database-backed (default)
- Both can be changed via `.env` configuration

## Testing Conventions

- Feature tests: `tests/Feature/` - HTTP endpoints, full application tests
- Unit tests: `tests/Unit/` - Individual class/method tests
- Use factories for model creation in tests
- Use `RefreshDatabase` trait to reset database between tests

## Environment Setup

The application uses SQLite by default. To switch to MySQL/PostgreSQL:

1. Update `.env`:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gibics
DB_USERNAME=root
DB_PASSWORD=
```

2. Create the database
3. Run migrations: `php artisan migrate`

## Important Notes

- **PHP Version:** Requires PHP ^8.2 (currently running 8.4.8)
- **Laravel Version:** Framework v12.x (latest major version)
- **Frontend Stack:** Vite + TailwindCSS 4.0
- **Default Database:** SQLite (zero-configuration for development)
- **Middleware Configuration:** Now in `bootstrap/app.php` using fluent API, not HTTP kernel
- **Composer Scripts:** Custom scripts defined for `setup`, `dev`, and `test` workflows
