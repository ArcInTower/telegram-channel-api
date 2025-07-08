# Troubleshooting Guide

## Common Issues

### Authentication Issues

#### "AUTH_KEY_UNREGISTERED" Error
Session is invalid or corrupted.

**Solution**:
```bash
php artisan telegram:login --reset
```

#### "AUTH_KEY_DUPLICATED" Error
Same session is being used on multiple servers. Each server needs its own session.

### Channel Access Issues

#### "CHANNEL_PRIVATE" Error
The channel is private or doesn't exist. Verify:
- Channel is public
- Username is correct (without @)
- Try using channel ID instead

#### "CHANNEL_INVALID" Error
- For usernames: use without @ (e.g., `python` not `@python`)
- For IDs: use the numeric ID (e.g., `-1001234567890`)

### Permission Issues

#### Can't Write to Storage
```bash
chmod -R 775 storage bootstrap/cache
chown -R webuser:webgroup storage bootstrap/cache
```

### Cache Issues

#### Stale Cache Data
```bash
php artisan cache:clear
```

#### Cache Not Working
Check `.env`:
```env
CACHE_STORE=database
TELEGRAM_CACHE_TTL=300
```

### API Response Issues

#### Empty or Null Response
Possible causes:
- Channel has no messages
- Channel is private
- Rate limiting

#### Timeout Errors
- Check internet connectivity
- Verify Telegram servers are accessible
- Check firewall rules

### Database Issues

#### "Table not found" Errors
```bash
php artisan migrate
```

## Debug Mode

Enable in `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

## Emergency Recovery

1. **Complete Reset**:
```bash
php artisan telegram:login --reset
php artisan cache:clear
```

2. **Verify Setup**:
```bash
php artisan telegram:status
```

Remember: Always perform operations as the web user, not root!