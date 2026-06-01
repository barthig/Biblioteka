const AGE_GROUP_LABELS = {
  '0-2': '0-2 lata',
  '3-6': '3-6 lat',
  '7-9': '7-9 lat',
  '10-12': '10-12 lat',
  '13-15': '13-15 lat',
  '16+': '16+ lat',
  adult: 'dla dorosłych',
  teen: 'dla młodzieży',
  children: 'dla dzieci',
  'young adult': 'młodzieżowe',
  'young-adult': 'młodzieżowe',
}

export function getAgeGroupLabel(value) {
  if (!value) return null

  const normalized = String(value).trim().toLowerCase()
  return AGE_GROUP_LABELS[normalized] ?? value
}