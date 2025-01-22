const { markdownToBlocks, markdownToRichText } = require("@tryfabric/martian");
// const arg = require('arg');
const { program } = require("commander");
const { Client } = require("@notionhq/client");

(async () => {

    // const args = arg({
    // 	'--token': String,
    // 	'--page': String,
    // });
    program
        .option("--token <string>")
        .option("--content <string>")
        .option("--page <string>");

    program.parse();
    const options = program.opts();

    const token = options.token;
    const page = options.page;

    const blocks = markdownToBlocks(options.content);

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

    console.log("Updating title in Notion");
    const pagePropResp = await notion.pages.update({
        page_id: page,
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
    });
    console.log('Appending children in Notion');
    const blockAppRes = await notion.blocks.children.append({
        block_id: page,
        children: blocks,
    });
})();
