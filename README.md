# Laravel Telegram Channel API

A Laravel API service that fetches the last message ID from public Telegram channels using the MTProto protocol through MadelineProto.

## Features

- Fetch last message ID from public Telegram channels
- Built-in LRU cache to prevent rate limiting (configurable TTL)
- RESTful API endpoints
- Session management via CLI commands
- Works in restricted environments

## Requirements

- PHP 8.2+
- Laravel 12
- Composer
- SQLite/MySQL/PostgreSQL
- Telegram API credentials from [my.telegram.org](https://my.telegram.org)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/ArcInTower/telegram-channel-api.git
cd telegram-channel-api
```

2. Install dependencies:
```bash
composer install
npm install && npm run build
```

3. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Set up your Telegram API credentials in `.env`:
```env
TELEGRAM_API_ID=your_api_id
TELEGRAM_API_HASH=your_api_hash
TELEGRAM_CACHE_TTL=300  # Cache duration in seconds
```

5. Run migrations:
```bash
php artisan migrate
```

6. Authenticate with Telegram:
```bash
php artisan telegram:login
```

## API Endpoints

### Get Last Message ID

Fetch the last message ID from a public Telegram channel:

```bash
GET /api/telegram/last-message?channel=channelname
```

**Parameters:**
- `channel`: The channel username (without @) or channel ID

**Response:**
```json
{
  "channel": "channelname",
  "last_message_id": 12345,
  "cached": false,
  "timestamp": "2024-01-20T10:30:00.000Z"
}
```



## CLI Commands

### Session Management

```bash
# Login to Telegram
php artisan telegram:login

# Check session status
php artisan telegram:status

# View session information
php artisan telegram:session-info
```

### Cache Management

```bash
# Clear all cache
php artisan cache:clear
```

## Production Deployment

For detailed production setup instructions, especially for Plesk servers, see [docs/PRODUCTION_SETUP.md](docs/PRODUCTION_SETUP.md).

### Quick Setup

1. Deploy code to your server
2. Set proper permissions (as root):
```bash
chown -R webuser:webgroup .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
```

3. Configure PHP settings in Plesk to include required paths in `open_basedir`
4. Login to Telegram as the web user via SSH
5. Set up your web server to point to the `public` directory

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `TELEGRAM_API_ID` | Your Telegram API ID | required |
| `TELEGRAM_API_HASH` | Your Telegram API Hash | required |
| `TELEGRAM_SESSION_FILE` | Session file name | telegram.madeline |
| `TELEGRAM_CACHE_TTL` | Cache duration in seconds | 300 |
| `MADELINE_RESTRICTED_MODE` | Force restricted mode | auto-detected |

### Cache Configuration

The application uses Laravel's cache system. By default, it's configured to use the database driver, but you can change this in `.env`:

```env
CACHE_STORE=redis  # or file, memcached, etc.
```

## How to Get Telegram API Credentials

1. Go to [my.telegram.org](https://my.telegram.org)
2. Log in with your phone number
3. Click on "API development tools"
4. Fill out the form to create a new application:
   - **App title**: Your application name
   - **Short name**: A short identifier
   - **Platform**: Select "Other"
   - **Description**: Brief description
5. After creation, you'll receive:
   - **App api_id**: A numeric ID
   - **App api_hash**: A 32-character string

## Troubleshooting

### Common Issues

1. **IPC Server Errors**: These are expected in restricted environments and don't affect functionality.

2. **Permission Errors**: Ensure all files are owned by the web user, not root.

3. **Session Invalid**: Reset and re-authenticate:
```bash
php artisan telegram:login --reset
```

4. **"CHANNEL_PRIVATE" Error**: The channel is private or doesn't exist. This API only works with public channels.

5. **"AUTH_KEY_UNREGISTERED" Error**: Delete the session and re-authenticate.

For more troubleshooting tips, see [docs/TROUBLESHOOTING.md](docs/TROUBLESHOOTING.md).

## Security Considerations

- Never commit `.env` files or session files
- Use environment variables for all sensitive data
- Implement authentication for production APIs
- Monitor rate limits to avoid Telegram restrictions
- Keep MadelineProto updated for security patches

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- [MadelineProto](https://github.com/danog/MadelineProto) - MTProto client for PHP
- [Laravel](https://laravel.com) - The PHP framework
- [Telegram](https://telegram.org) - For providing the MTProto API

## Support

For issues and questions:
- Open an issue on GitHub
- Check existing documentation in the `docs/` folder
- Review closed issues for common problems

## API Rate Limits

Please be aware of Telegram's rate limits:
- Use caching to minimize API calls
- Don't poll channels too frequently
- Consider implementing exponential backoff
- Monitor your usage to avoid restrictions