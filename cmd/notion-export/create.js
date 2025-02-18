const {markdownToBlocks} = require("@tryfabric/martian");
const {Client} = require("@notionhq/client");
const fs = require("fs");
const {trimNestedChildren} = require("./utils/fix-too-many-children");
const {fixTooDeepChildren} = require("./utils/fix-too-deep-children");

try {
    (async () => {
        let input = "";

        console.log("Listening for 'data'");
        process.stdin.on("data", (chunk) => {
            console.log('Received data');
            input += chunk;
        });

        console.log("Listening for 'end'");
        process.stdin.on("end", async () => {
            let blocks;
            try {
                const {
                    content,
                    // Path in the repo
                    filePath,
                    // (optional) local file path to get content from
                    contentPath,
                    databaseId,
                    token
                } = JSON.parse(input);
                let finalContent = content;

                if (!finalContent && contentPath) {
                    try {
                        if (fs.existsSync(contentPath)) {
                            finalContent = fs.readFileSync(contentPath, 'utf-8');
                            console.log(`Read content from file: ${contentPath}`);
                        } else {
                            console.error(`Content file not found at path: ${contentPath}`);
                        }
                    } catch (err) {
                        console.error(`Error reading file at path ${contentPath}:`, err);
                        throw err;
                    }
                }

                if (!finalContent) {
                    console.error("No content provided and contentPath is invalid or unreadable.");
                    throw new Error("Content is required.");
                }

                // Convert content to blocks
                console.log('Converting to blocks');
                blocks = markdownToBlocks(finalContent);

                // Workarounds for Notion errors
                blocks = trimNestedChildren(
                    blocks);
                fixTooDeepChildren(
                    blocks);

                // Extract h1
                const h1Indexes = blocks
                    .map((block, index) => block.type === "heading_1" ? index : -1)
                    .filter(index => index !== -1);
                console.log('h1Indexes', h1Indexes);
                let titleRichText;
                if (h1Indexes.length === 1 && h1Indexes[0] <= 1) {
                    console.log('Found h1');
                    titleRichText = blocks.splice(h1Indexes[0], 1)[0].heading_1.rich_text;
                } else {
                    console.log('No h1 found, using file path to extract title:' + filePath);
                    const titleText = filePath.split('/').pop().replace(/\.[^/.]+$/, "");
                    titleRichText = [
                        {
                            "type": "text",
                            "text": {
                                "content": titleText,
                            }
                        }
                    ];
                }

                // Initializing a client
                const notion = new Client({
                    auth: token,
                });

                const chunkSize = 100;
                const blockChunks = [];
                for (let i = 0; i < blocks.length; i += chunkSize) {
                    blockChunks.push(blocks.slice(i, i + chunkSize));
                }


                console.log("Creating page in Notion");
                const pagePropResp = await notion.pages.create({
                    parent: {
                        type: 'database_id',
                        database_id: databaseId,
                    },
                    properties: {
                        "title": {
                            type: "title",
                            title: titleRichText,
                        },
                    },
                    children: blockChunks[0],
                });
                console.log(pagePropResp);

                sendMessage('page-created', {
                    'notionPageID': pagePropResp.id,
                })

                // Append remaining blocks in separate calls
                const pageId = pagePropResp.id; // Retrieve created page's ID
                for (let i = 1; i < blockChunks.length; i++) {
                    console.log(`Appending chunk ${i} to page`);
                    await notion.blocks.children.append({
                        block_id: pageId,
                        children: blockChunks[i],
                    });
                    console.log(`Chunk ${i} appended successfully`);
                }

            } catch (e) {
                console.info(e);
                console.info('Blocks', blocks);
                throw e;
            }
        });

    })();
} catch (e) {
    console.error(e);
    throw e;
}

function sendMessage(type, payload) {
    process.stdout.write("\n");
    process.stdout.write(JSON.stringify({
        type,
        payload,
    }));
    process.stdout.write("\n");
}
