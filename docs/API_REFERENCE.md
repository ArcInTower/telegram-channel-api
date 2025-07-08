# API Reference

## Base URL

```
http://your-domain.com/api/telegram
```

## Authentication

Currently, the API doesn't require authentication. Consider implementing API keys or OAuth for production use.

## Endpoints

### Get Last Message ID

Retrieves the last message ID from a public Telegram channel.

**Endpoint:** `GET /last-message`

**Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| channel | string | Yes | Channel username (without @) or channel ID |

**Example Request:**
```bash
curl "http://localhost:8000/api/telegram/last-message?channel=laravel"
```

**Success Response (200):**
```json
{
  "channel": "laravel",
  "last_message_id": 12345,
  "cached": false,
  "timestamp": "2025-01-20T10:30:00.000Z"
}
```

**Error Response (404):**
```json
{
  "error": "Channel not found or inaccessible",
  "channel": "private_channel"
}
```



## Rate Limiting

Default rate limits:
- 60 requests per minute per IP
- Cache TTL: 300 seconds (5 minutes)

## Error Codes

| Code | Description |
|------|-------------|
| 200 | Success |
| 400 | Bad Request - Invalid parameters |
| 404 | Not Found - Channel doesn't exist or is private |
| 429 | Too Many Requests - Rate limit exceeded |
| 500 | Server Error - Internal error |

## Best Practices

1. **Use caching**: Don't request the same channel more than once per cache TTL
2. **Handle errors**: Always check for error responses
3. **Respect rate limits**: Implement exponential backoff on 429 errors