select json_object('token', w.token, 'database_id', db.notion_id, 'content', doc.content) from documents doc
    left join sources src on doc.source_id = src.id
    left join notion_databases db on src.notion_database_id = db.id
    left join notion_workspaces w on db.notion_workspace_id = w.id
    where doc.id = 12;
