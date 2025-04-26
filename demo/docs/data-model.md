# Data Model

## Overview

Our platform uses a relational data model with carefully designed entities and relationships. Understanding this model is essential for effective API usage and data integration.

## Core Entities

### User

The User entity represents individual users of the system.

```
User {
  id: UUID (primary key)
  email: String (unique)
  first_name: String
  last_name: String
  role: Enum [admin, manager, editor, viewer]
  status: Enum [active, inactive, pending]
  created_at: Timestamp
  updated_at: Timestamp
  last_login_at: Timestamp
  organization_id: UUID (foreign key)
}
```

### Organization

Organizations are the top-level containers that group users and resources.

```
Organization {
  id: UUID (primary key)
  name: String
  slug: String (unique)
  plan: Enum [free, professional, enterprise]
  billing_email: String
  created_at: Timestamp
  updated_at: Timestamp
}
```

### Project

Projects organize work within an organization.

```
Project {
  id: UUID (primary key)
  name: String
  description: Text
  status: Enum [active, archived, draft]
  created_at: Timestamp
  updated_at: Timestamp
  organization_id: UUID (foreign key)
  created_by_user_id: UUID (foreign key)
}
```

### Document

Documents contain content created by users.

```
Document {
  id: UUID (primary key)
  title: String
  content: Text
  format: Enum [markdown, rich_text, plain_text]
  status: Enum [draft, published, archived]
  version: Integer
  created_at: Timestamp
  updated_at: Timestamp
  published_at: Timestamp
  project_id: UUID (foreign key)
  created_by_user_id: UUID (foreign key)
  last_modified_by_user_id: UUID (foreign key)
}
```

## Relationships

### One-to-Many Relationships

- Organization → Users: An organization has many users
- Organization → Projects: An organization has many projects
- Project → Documents: A project contains many documents
- User → Documents: A user creates many documents

### Many-to-Many Relationships

- Users ↔ Projects: Users participate in multiple projects through the UserProject join table
- Users ↔ Documents: Users collaborate on documents through the DocumentCollaborator join table

## Join Tables

### UserProject

```
UserProject {
  user_id: UUID (foreign key, part of composite primary key)
  project_id: UUID (foreign key, part of composite primary key)
  role: Enum [owner, contributor, viewer]
  joined_at: Timestamp
}
```

### DocumentCollaborator

```
DocumentCollaborator {
  document_id: UUID (foreign key, part of composite primary key)
  user_id: UUID (foreign key, part of composite primary key)
  permission: Enum [edit, comment, view]
  added_at: Timestamp
}
```

## Indexes

| Table | Index | Fields | Purpose |
|-------|-------|--------|---------|
| User | email_idx | email | Optimize login lookups |
| Organization | slug_idx | slug | URL-friendly lookups |
| Document | project_status_idx | project_id, status | Filter documents by status within projects |
| Document | full_text_idx | title, content | Enable full-text search |

## Database Constraints

- Foreign key constraints ensure referential integrity
- Unique constraints prevent duplicate records
- Check constraints enforce data validation rules
- Default values provide sensible initial states

## Data Validation Rules

- User emails must be valid email format
- Organization slugs must be URL-safe (alphanumeric, hyphens)
- Document titles cannot be empty
- Project names must be between 3-100 characters

## Schema Evolution

We follow these principles for schema changes:

- Backward compatible changes whenever possible
- Explicit schema versioning
- Migration scripts for all changes
- Deprecation periods before removing fields or tables

## API Representation

Resources in our API use camelCase naming conventions:

```json
{
  "id": "usr_5f8d3a21",
  "firstName": "Jane",
  "lastName": "Smith",
  "email": "jane.smith@example.com",
  "role": "editor",
  "organizationId": "org_7e6f5d4c"
}
```
