# Export Options

## Overview

Our platform supports comprehensive data export capabilities to facilitate integration with other systems, compliance requirements, and data backup strategies.

## Available Export Formats

### Document Exports

- **PDF**: Fixed-layout document with preserved formatting
- **Markdown**: Plain text format with simple formatting
- **HTML**: Web-ready format with styling
- **DOCX**: Microsoft Word compatible document

### Data Exports

- **CSV**: Tabular data in comma-separated format
- **JSON**: Structured data in JavaScript Object Notation
- **XML**: Extensible Markup Language format
- **XLSX**: Microsoft Excel compatible spreadsheet

## Export Configuration Options

Each export type supports various configuration parameters:

### Document Export Options

- Page size and orientation
- Header and footer customization
- Font selection and sizing
- Include/exclude comments
- Version selection

### Data Export Options

- Field selection
- Date formatting
- Number formatting
- Filtering criteria
- Sort order

## Bulk Export API

For large-scale exports, use our bulk export API:

```javascript
const exportJob = await client.exports.create({
  format: 'json',
  resource_type: 'projects',
  filters: {
    created_after: '2023-01-01',
    status: 'active'
  },
  include_relations: true
});

// Check export status
const status = await client.exports.getStatus(exportJob.id);

// Download when complete
if (status.state === 'completed') {
  const data = await client.exports.download(exportJob.id);
}
```

## Scheduled Exports

Enterprise customers can configure automated exports on a schedule:

- Daily, weekly, or monthly frequency
- Automatic delivery to SFTP, S3, or other storage
- Email notifications upon completion
- Data retention policies

## Export Limits

| Plan | Max Records | Available Formats | Concurrent Exports |
|------|-------------|-------------------|-------------------|
| Free | 1,000 | CSV, JSON | 1 |
| Professional | 100,000 | All formats | 3 |
| Enterprise | Unlimited | All formats | 10 |

## Security Considerations

- Exports may contain sensitive information; handle securely
- Enable encryption for exported data
- Set appropriate retention periods for exports
- Consider using field-level exclusions for sensitive data
