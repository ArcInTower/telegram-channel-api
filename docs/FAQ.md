# Frequently Asked Questions

## General

### What is this API for?
This API allows you to fetch the last message ID from public Telegram channels without using the Bot API. It uses the MTProto protocol, which gives you access to channels as a regular user.

### Do I need a Telegram Bot?
No, this API uses your regular Telegram account. You authenticate with your phone number, not a bot token.

### Is it safe to use my Telegram account?
Yes, it's safe. The API uses the official Telegram MTProto protocol. Your session is stored locally and encrypted. You can revoke access anytime from Telegram Settings → Devices.

## Setup Issues

### How do I get API credentials?
1. Visit [my.telegram.org](https://my.telegram.org)
2. Log in with your phone number
3. Go to "API development tools"
4. Create a new application
5. Copy your `api_id` and `api_hash`

### "AUTH_KEY_UNREGISTERED" error
Your session has expired or been revoked. Run:
```bash
php artisan telegram:login --reset
```

### Can't find channel
Make sure:
- The channel is public (not private)
- You're using the correct username (without @)
- The channel exists and hasn't been deleted

## Usage

### What's the rate limit?
By default, responses are cached for 5 minutes. You can adjust this with:
```env
TELEGRAM_CACHE_TTL=300  # seconds
```

### Can I access private channels?
No, this API only works with public channels. For private channels, you would need to be a member and handle additional authentication.

### How do I check multiple channels?
Currently, you need to make separate requests for each channel. Consider implementing caching on your side to avoid hitting rate limits.

### Can I use channel IDs instead of usernames?
Yes, both formats work:
- Username: `channel=python`
- ID: `channel=-1001234567890`

## Technical

### What is IPC server error?
This error appears in some hosting environments but doesn't affect functionality. The API automatically works in restricted mode.

### How to run in production?
1. Use a process manager like Supervisor or systemd
2. Set up proper logging
3. Configure your web server (Apache/Nginx)
4. Use environment variables for sensitive data

### Can I use multiple accounts?
Each instance can only use one Telegram account at a time. For multiple accounts, run separate instances on different ports.

### Session files are getting large
This is normal. MadelineProto stores channel data for performance. The files are automatically managed and cleaned.

## Troubleshooting

### API returns null message ID
Possible causes:
- Channel has no messages
- Channel is private
- Rate limiting from Telegram
- Network issues

### Memory usage is high
MadelineProto can use significant memory. Solutions:
- Increase PHP memory limit
- Use queue workers for heavy operations
- Restart the service periodically

### Can't authenticate after server move
Sessions are tied to the server. You'll need to re-authenticate on the new server.

## Need More Help?

- Check the [Installation Guide](INSTALLATION.md)
- Review the [API Reference](API_REFERENCE.md)
- Open an issue on GitHub
- Check MadelineProto [documentation](https://docs.madelineproto.xyz)