const { markdownToBlocks } = require("@tryfabric/martian");
const { Client } = require("@notionhq/client");
const fs = require("fs");

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
            console.log('Received input', input);
            let blocks;
            try {
                console.log('Received end');
                const { content, content_path: contentPath, database_id: databaseId, token } = JSON.parse(input);
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
                    console.error("No content provided and content_path is invalid or unreadable.");
                    throw new Error("Content is required.");
                }

                console.log('Converting to blocks');
                blocks = markdownToBlocks(finalContent);

                // Extract h1
                const heading1Index = blocks.findIndex(
                    (block) => block.type === "heading_1"
                );
                let heading1Block;
                if (heading1Index !== -1) {
                    heading1Block = blocks.splice(heading1Index, 1)[0];
                }

                // Initializing a client
                const notion = new Client({
                    auth: token,
                });

                console.log("Creating page in Notion");
                const pagePropResp = await notion.pages.create({
                    parent: {
                        type: 'database_id',
                        database_id: databaseId,
                    },
                    properties: {
                        "title": {
                            type: "title",
                            title: heading1Block?.heading_1.rich_text ?? [
                                {
                                    "type": "text",
                                    "text": {
                                        "content": "untitled",
                                    }
                                }
                            ],
                        },
                    },
                    children: blocks,
                });
                console.log(pagePropResp);
                // console.log('Appending children in Notion');
                // const blockAppRes = await notion.blocks.children.append({
                //     block_id: page,
                //     children: blocks,
                // });
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
