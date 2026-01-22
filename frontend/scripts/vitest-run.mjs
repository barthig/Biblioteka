import { spawn } from 'node:child_process'
import { fileURLToPath } from 'node:url'

const suppressed = "CJS build of Vite's Node API is deprecated"
const vitestBin = fileURLToPath(new URL('../node_modules/vitest/vitest.mjs', import.meta.url))

const child = spawn(process.execPath, [vitestBin, ...process.argv.slice(2)], {
  stdio: ['inherit', 'inherit', 'pipe']
})

child.stderr.on('data', chunk => {
  const text = chunk.toString()
  const filtered = text
    .split('\n')
    .filter(line => !line.includes(suppressed))
    .join('\n')

  if (filtered.length > 0) {
    process.stderr.write(filtered)
    if (text.endsWith('\n') && !filtered.endsWith('\n')) {
      process.stderr.write('\n')
    }
  }
})

child.on('close', code => {
  process.exit(code ?? 0)
})
