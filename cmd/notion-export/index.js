const { markdownToBlocks, markdownToRichText } = require("@tryfabric/martian");
// const arg = require('arg');
const { program } = require("commander");
const { Client } = require("@notionhq/client");

(async () => {
    console.log("run!");

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

    const result = markdownToBlocks(options.content);
    console.log(result);

    // Initializing a client
    const notion = new Client({
        auth: token,
    });

    console.log("sending to notion");
    const response = await notion.blocks.children.append({
        block_id: page,
        children: result,
    });
    console.log(response);
})();
