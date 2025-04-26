# FAQ

## General Questions

### What is the platform used for?

Our platform provides a collaborative workspace for teams to create, manage, and share documentation and structured content. It combines the power of document management with project organization and API capabilities.

### Who is the platform designed for?

The platform serves cross-functional teams, including engineers, product managers, designers, marketers, and customer support. It's particularly valuable for organizations that need to maintain technical documentation accessible to technical and non-technical stakeholders.

### What are the main benefits?

- Centralized knowledge management
- Improved collaboration across departments
- Version-controlled documentation
- Powerful search and discovery
- Integration with development workflows
- Customizable workflows and permissions

### How does pricing work?

We offer three pricing tiers:

- **Free**: For individuals and small teams (up to 3 users)
- **Professional**: For growing teams ($12 per user/month)
- **Enterprise**: For organizations with advanced needs (contact sales)

## Technical Questions

### What technologies power the platform?

Our platform is built on a modern stack including:

- React and TypeScript for the frontend
- Node.js and GraphQL for the API layer
- PostgreSQL for primary data storage
- Elasticsearch for search functionality
- Redis for caching and real-time features

### Is there an on-premise version available?

Yes, Enterprise customers can request an on-premise deployment. Contact our sales team for details and requirements.

### How do I integrate with my existing tools?

We offer several integration options:

- REST and GraphQL APIs
- Webhook subscriptions for events
- Native integrations with popular tools
- Custom integrations via our Professional Services team

### How is data backed up?

We perform automated backups:

- Hourly incremental backups
- Daily full backups
- 30-day retention period
- Point-in-time recovery capability

## Security Questions

### How is my data secured?

We implement multiple security measures:

- Data encryption at rest and in transit
- Regular security audits and penetration testing
- SOC 2 Type II compliance
- Role-based access controls
- Two-factor authentication
- Single Sign-On (SSO) support

### Can I control who has access to specific content?

Yes, our [User Permissions](./features/user-permissions.md) system allows fine-grained control over who can view, edit, or manage content at the organization, project, and document levels.

### Do you comply with GDPR?

Yes, we are fully GDPR compliant. We provide data processing agreements and tools to help you fulfill data subject requests.

## API Questions

### What are the API rate limits?

See our [Rate Limits](./api/rate-limits.md) documentation for detailed information on limits by plan level.

### How do I authenticate with the API?

Our API supports multiple authentication methods. See our [Authentication](./api/authentication.md) guide for implementation details.

### Are there SDKs available?

Yes, we provide official SDKs for:

- JavaScript/TypeScript
- Python
- Java
- Ruby
- Go

## Feature Questions

### How does the notification system work?

Our [Notification System](./features/notification-system.md) allows users to receive alerts through multiple channels when relevant events occur, such as document updates or mentions.

### Can I export my data?

Yes, see our [Export Options](./features/export-options.md) documentation for details on available formats and methods.

### Does the platform support multiple organizations?

Yes, our [Multi-Tenancy](./features/multi-tenancy.md) architecture allows Enterprise users to manage multiple organizations within a single account.

### How detailed is the audit logging?

Our [Audit Logging](./features/audit-logging.md) system captures comprehensive information about all system activities for security and compliance purposes.

## Support Questions

### How do I get help if I encounter an issue?

We offer multiple support channels:

- In-app chat support during business hours
- Email support at <support@example.com>
- Self-service knowledge base at help.example.com
- Community forums for peer assistance
- Priority support with dedicated account managers for Enterprise customers

### What are your support hours?

Our support team is available:

- Standard Support: Monday-Friday, 9am-5pm ET
- Premium Support (Enterprise): 24/7 for critical issues

### Is there a community forum?

Yes, our [Community Forum](https://community.example.com) provides a space to connect with other users, share best practices, and get answers from community experts and our team.

### How do I report a bug?

Bugs can be reported through:

- The in-app feedback tool
- Email to <bugs@example.com>
- Your account manager (Enterprise customers)

## Deployment Questions

### What browsers are supported?

We officially support:

- Chrome (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- Edge (latest 2 versions)

### Are there mobile apps available?

Yes, we offer native mobile apps for:

- iOS (requires iOS 14 or later)
- Android (requires Android 9 or later)

### What are the network requirements?

For on-premise deployments:

- Outbound HTTPS (port 443) for updates and telemetry
- Minimum 100Mbps network connection
- Static IP allocation

### How are updates handled?

- Cloud: Updates are deployed automatically with zero downtime
- On-premise: Updates are released quarterly with detailed release notes

## Performance Questions

### What are the system requirements?

For optimal performance:

- Modern browser (see supported browsers)
- 4GB RAM minimum (8GB recommended)
- Stable internet connection (5Mbps or faster)

### How does the platform scale?

Our architecture scales horizontally to accommodate growth:

- Microservices architecture
- Content delivery network (CDN) for assets
- Regional deployment options for global teams
- Automatic resource scaling based on demand

### Is there a limit to document size?

Individual documents are limited to 10MB in size. For larger content, we recommend splitting into multiple linked documents or using file attachments.

### How many users can collaborate simultaneously?

There is no hard limit on concurrent users. Our real-time collaboration features can comfortably support 50+ simultaneous editors on a single document.

## Migration Questions

### Can I import data from other platforms?

Yes, we provide import tools for:

- Confluence
- Notion
- Microsoft SharePoint
- Google Workspace
- Markdown/GitHub repositories

### What is the migration process?

Our migration process involves:

1. Initial assessment of your data
2. Configuration of mapping rules
3. Test migration to staging environment
4. Validation and adjustments
5. Production migration
6. Post-migration verification

### Will my existing links break?

We provide a URL redirect service to ensure your existing links continue to work after migration.

### How long does migration typically take?

Migration timelines depend on data volume:

- Small teams (< 100 documents): 1-2 days
- Medium organizations (100-1000 documents): 3-5 days
- Large enterprises (1000+ documents): 1-3 weeks
