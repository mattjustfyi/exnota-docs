const {markdownToBlocks, markdownToRichText} = require('@tryfabric/martian');
// const arg = require('arg');
const { program } = require('commander');

console.log('run!')

// const args = arg({
// 	'--token': String,
// 	'--page': String,
// });
program
  .option('--token <string>')
  .option('--page <string>');


program.parse();
const options = program.opts();
console.log(options);

const token = options.token;
const page = options.page;

console.log(token, page);


const result = markdownToBlocks(`
hello _world_ 
*** 
## heading2
* [x] todo
`);


console.log(result);