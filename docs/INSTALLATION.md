# Installation Guide

## Requirements

- PHP 8.2 or higher
- Composer
- Laravel 12
- SQLite/MySQL/PostgreSQL
- Telegram API credentials

## Quick Start

### 1. Clone and Install

```bash
git clone https://github.com/ArcInTower/telegram-channel-api.git
cd telegram-channel-api
composer install
```

### 2. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Configure Telegram API

Add your credentials to `.env`:
```env
TELEGRAM_API_ID=your_api_id
TELEGRAM_API_HASH=your_api_hash
```

Get these from [my.telegram.org](https://my.telegram.org)

### 4. Database Setup

```bash
# If using SQLite (default)
touch database/database.sqlite

# Run migrations
php artisan migrate
```

### 5. Authenticate with Telegram

```bash
php artisan telegram:login
```

Follow the prompts to authenticate with your Telegram account.

### 6. Start the Server

```bash
php artisan serve
```

Your API is now available at `http://localhost:8000`

## Production Deployment

### Using Apache/Nginx

1. Point your web server to the `public` directory
2. Ensure proper file permissions:
   ```bash
   chmod -R 755 .
   chmod -R 775 storage bootstrap/cache
   ```

### Environment Variables

For production, set:
```env
APP_ENV=production
APP_DEBUG=false
```

### Session Persistence

Telegram sessions are stored in `storage/app/`. Ensure this directory is:
- Writable by the web server
- Excluded from version control
- Backed up regularly

## Troubleshooting

### Common Issues

**Can't authenticate**: Ensure your API credentials are correct and you have internet access.

**Permission errors**: Check that the web server user owns the storage directory.

**Session expired**: Run `php artisan telegram:login` again.

For more help, see the [FAQ](FAQ.md) or open an issue.