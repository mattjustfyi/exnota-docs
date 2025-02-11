function trimNestedChildren(blocks) {
    return blocks.filter(block => {
        switch (block.type) {
            case 'table':
                return filterTable(block);
            default:
                return true;
        }
    });
}

function filterTable(block) {
    if (block.table.children.length > 100) {
        console.info('too many rows', block.table.children.length);
        return false;
    }

    for (const tableRow of block.table.children) {
        if (tableRow.table_row.cells.some(c => c.length > 100)) {
            console.info('Cells have too much content', tableRow.table_row.cells.length);
            return false;
        }
    }

    return true;
}

module.exports = {
    trimNestedChildren,
};
