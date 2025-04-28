# Usage Limits

## Overview

To ensure service stability and fair usage, our API implements various usage limits. These limits include rate limiting, data quotas, and concurrent request restrictions, varying by subscription tier.

## Rate Limit Structure

| Plan | Requests per minute | Requests per day | Concurrent requests |
|------|---------------------|------------------|---------------------|
| Free | 90 | 5,000 | 5 |
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

## Data Quotas

In addition to request limits, the following data quotas apply:

| Plan | Storage limit | File upload size | Monthly bandwidth |
|------|---------------|------------------|-------------------|
| Free | 1 GB | 10 MB | 50 GB |
| Professional | 50 GB | 100 MB | 500 GB |
| Enterprise | 1 TB | 500 MB | 5 TB |

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

## Usage Limit Increases

Enterprise customers can request temporary limit increases for special events or migrations. Contact your account manager to discuss your needs.
