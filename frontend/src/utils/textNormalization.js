const MOJIBAKE_SIGNAL = /[ÃĂÅÄâ€Â�]/

const DIRECT_REPLACEMENTS = [
  ['Ãąâ€š', 'ł'],
  ['Ăąâ€š', 'ł'],
  ['Aąâ€š', 'ł'],
  ['Ãąâ€º', 'ś'],
  ['Ăąâ€º', 'ś'],
  ['Aąâ€º', 'ś'],
  ['Ãąâ€ž', 'ń'],
  ['Ăąâ€ž', 'ń'],
  ['Aąâ€ž', 'ń'],
  ['Ã„â€¦', 'ą'],
  ['Ă„â€¦', 'ą'],
  ['A„â€¦', 'ą'],
  ['Ã„â€¡', 'ć'],
  ['Ă„â€¡', 'ć'],
  ['A„â€¡', 'ć'],
  ['Ã„â„¢', 'ę'],
  ['Ă„â„¢', 'ę'],
  ['A„â„¢', 'ę'],
  ['Ã…›', 'ś'],
  ['Ă…›', 'ś'],
  ['A…›', 'ś'],
  ['Ã…¼', 'ż'],
  ['Ă…¼', 'ż'],
  ['A…¼', 'ż'],
]

export function normalizeDisplayText(value) {
  if (typeof value !== 'string' || value.length === 0) {
    return value
  }

  let normalized = value
  for (const [bad, good] of DIRECT_REPLACEMENTS) {
    if (normalized.includes(bad)) {
      normalized = normalized.split(bad).join(good)
    }
  }

  if (!MOJIBAKE_SIGNAL.test(normalized)) {
    return normalized
  }

  normalized = normalized
    .replace(/\bOg.{0,12}oszenie\b/g, 'Ogłoszenie')
    .replace(/\bog.{0,12}oszenie\b/g, 'ogłoszenie')
    .replace(/\bog.{0,12}oszenia\b/g, 'ogłoszenia')
    .replace(/\bTre\S{0,12}\b/g, 'Treść')
    .replace(/\btre\S{0,12}\b/g, 'treść')

  return normalized
}
