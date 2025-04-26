# Audit Logging

## Overview

Our audit logging system tracks all significant actions within the platform to provide accountability, security monitoring, and compliance support.

## Event Categories

Audit logs capture events across these categories:

- **Authentication**: Login attempts, password changes, MFA events
- **Authorization**: Permission changes, access grants/revocations
- **Data Operations**: Create, read, update, delete actions
- **Configuration**: System setting modifications
- **Security**: Security-related events and potential threats
- **User Management**: User creation, modification, and deletion

## Log Data Structure

Each audit log entry contains:

```json
{
  "event_id": "evt_8f7d6e5c",
  "timestamp": "2023-07-15T14:25:36Z",
  "actor": {
    "user_id": "usr_1a2b3c4d",
    "email": "john.smith@example.com", 
    "ip_address": "192.168.1.100",
    "user_agent": "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)..."
  },
  "action": "document.update",
  "resource": {
    "type": "document",
    "id": "doc_5e6f7g8h",
    "name": "Q2 Financial Report"
  },
  "changes": [
    {
      "field": "content",
      "old_value_hash": "sha256:a1b2c3...",
      "new_value_hash": "sha256:d4e5f6..."
    },
    {
      "field": "permissions",
      "old_value": ["team_finance"],
      "new_value": ["team_finance", "team_executives"]
    }
  ],
  "status": "success",
  "context": {
    "session_id": "sess_9i8j7k6l",
    "request_id": "req_5m4n3o2p"
  }
}
```

## Retention and Storage

- Audit logs are stored securely and immutably
- Standard retention period is 90 days
- Enterprise customers can configure extended retention up to 7 years
- Logs can be exported for long-term archival

## Access Controls

- Only users with explicit audit log permissions can view logs
- Administrators can configure role-based access to specific log categories
- Search and filtering capabilities are restricted based on user permissions

## Integration Options

### SIEM Integration

Enterprise customers can forward logs to Security Information and Event Management (SIEM) systems:

- **Splunk**: Direct integration via HTTP Event Collector
- **ELK Stack**: Export via Logstash
- **AWS CloudWatch**: Automated streaming
- **Azure Monitor**: Log Analytics workspace integration

### API Access

Logs can be accessed programmatically:

```javascript
const auditLogs = await client.auditLogs.list({
  start_date: '2023-07-01T00:00:00Z',
  end_date: '2023-07-31T23:59:59Z',
  actor_id: 'usr_1a2b3c4d',
  action_types: ['document.update', 'document.delete']
});
```

## Compliance Support

Our audit logging supports compliance requirements for:

- SOC 2
- GDPR
- HIPAA
- ISO 27001
- PCI DSS
