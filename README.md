# Laravel Telegram Channel API

An unofficial Laravel-based API for retrieving public Telegram channel information, statistics, and messages using the MTProto protocol.

## 🚀 Quick Start

```bash
# Clone the repository
git clone https://github.com/ArcInTower/telegram-channel-api.git
cd telegram-channel-api

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php artisan key:generate

# Add your Telegram API credentials to .env
TELEGRAM_API_ID=your_api_id
TELEGRAM_API_HASH=your_api_hash

# Run migrations and authenticate
php artisan migrate
php artisan telegram:login

# Start the server
php artisan serve
```

## 📖 Documentation

For complete documentation including:
- API endpoint reference
- Authentication setup
- Configuration options
- Advanced features
- Deployment guides

Visit: **[https://api-telegram.repostea.com](https://api-telegram.repostea.com)**

## 🛸 Key Features

- **Channel Statistics**: Get detailed analytics for any public channel
- **Message Retrieval**: Fetch last message IDs with caching
- **Channel Comparison**: Compare multiple channels side-by-side
- **User Privacy**: Built-in user anonymization support
- **Channel Blocking**: Exclude specific channels from API access
- **JSON:API 1.1**: Fully compliant RESTful API

## 🔧 Requirements

- PHP 8.2+
- Laravel 12
- SQLite/MySQL/PostgreSQL
- Telegram API credentials from [my.telegram.org](https://my.telegram.org)

## 📝 License

This project is open-sourced software licensed under the [MIT license](LICENSE).