const esbuild = require('esbuild');
const { readdirSync } = require('fs');

const webComponentsDir = 'assets/js/web-components';
const outdir = 'assets/js/dist';

const entryPoints = ['assets/js/app.js'];

// Add each web component individually
try {
  const files = readdirSync(webComponentsDir).filter(f => f.endsWith('.js'));
  for (const file of files) {
    entryPoints.push(`${webComponentsDir}/${file}`);
  }
} catch {
  // web-components dir may not exist yet
}

esbuild.build({
  entryPoints,
  bundle: true,
  minify: true,
  target: 'es2020',
  outdir,
  // Don't bundle external dependencies — WP provides them
  external: [],
}).then(() => {
  console.log(`Build complete: ${entryPoints.length} files → ${outdir}/`);
}).catch((err) => {
  console.error(err);
  process.exit(1);
});
