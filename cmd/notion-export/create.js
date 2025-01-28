const {markdownToBlocks} = require("@tryfabric/martian");
const {Client} = require("@notionhq/client");

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
                console.log('Received end');
                const {content, page, token} = JSON.parse(input);
                console.log('content', content);
                console.log('page', page);
                console.log('token', token);

                console.log('Converting to blocks')
                blocks = markdownToBlocks(content);

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
                        database_id: page,
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
            }
            catch (e) {
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
