# Rate Limits

## Overview

To ensure service stability and fair usage, our API implements rate limiting. These limits vary by endpoint and subscription tier.

## Rate Limit Structure

| Plan | Requests per minute | Requests per day | Concurrent requests |
|------|---------------------|------------------|---------------------|
| Free | 60 | 5,000 | 5 |
| Professional | 300 | 50,000 | 20 |
| Enterprise | 1,200 | 500,000 | 50 |

## Rate Limit Headers

All API responses include headers to help you track your usage:

```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1625160264
```

- `X-RateLimit-Limit`: Maximum requests allowed in the current period
- `X-RateLimit-Remaining`: Requests remaining in the current period
- `X-RateLimit-Reset`: Unix timestamp when the rate limit resets

## Handling Rate Limiting

When you exceed your rate limit, the API returns a `429 Too Many Requests` response with a JSON body:

```json
{
  "error": "rate_limit_exceeded",
  "message": "Rate limit exceeded. Try again in 35 seconds.",
  "retry_after": 35
}
```

## Best Practices

- Implement exponential backoff for retry mechanisms
- Cache responses when possible to reduce API calls
- Spread non-urgent requests throughout the day
- Use bulk operations instead of multiple individual requests
- Monitor your usage with our dashboard analytics

## Rate Limit Increases

Enterprise customers can request temporary rate limit increases for special events or migrations. Contact your account manager to discuss your needs.
