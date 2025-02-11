const TYPES_TO_TRIM = ['bulleted_list_item'];

function fixTooDeepChildren(blocks) {
    for (const block of blocks) {
        if (TYPES_TO_TRIM.includes(block.type)) {
            checkDepth(block, [], 2);
        }
    }
}

function checkDepth(block, types, depth) {
    if (depth === 0) {
        block[block.type].children = undefined;
        return;
    }
    for (const child of block[block.type].children ?? []) {
        if (TYPES_TO_TRIM.includes(child.type)) {
            checkDepth(child, [...types, block.type], depth - 1);
        }
    }
}

module.exports = {
    fixTooDeepChildren,
};
