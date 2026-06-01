const TYPE_LABELS = {
  reservation_queued: 'Rezerwacja w kolejce',
  reservation_prepared: 'Gotowa do odbioru',
  announcement_published: 'Ogłoszenie',
  event_published: 'Wydarzenie',
  catalog_new_book: 'Nowa pozycja',
  info: 'Informacja',
}

function normalizeText(value) {
  return typeof value === 'string' ? value.trim() : ''
}

function translateMessage(notification) {
  const type = normalizeText(notification?.type)
  const title = normalizeText(notification?.title)
  const message = normalizeText(notification?.message)

  if (type === 'reservation_queued') {
    const reservationMatch = message.match(/Your reservation for "(.+?)" has been registered\..*?Current expiry date: ([0-9-]+)\.?/i)
    const titleMatch = title.match(/Reservation queued: "(.+?)"/i)
    const bookTitle = reservationMatch?.[1] || titleMatch?.[1]
    const expiresAt = reservationMatch?.[2]

    if (bookTitle && expiresAt) {
      return `Twoja rezerwacja na tytuł "${bookTitle}" została zarejestrowana. Powiadomimy Cię, gdy egzemplarz będzie gotowy do odbioru. Aktualna data wygaśnięcia: ${expiresAt}.`
    }
  }

  if (type === 'reservation_prepared') {
    const reservationMatch = message.match(/Your reservation for "(.+?)" is ready for pickup\.? Please collect it by ([0-9-]+)\.?/i)
    const bookTitle = reservationMatch?.[1]
    const expiresAt = reservationMatch?.[2]

    if (bookTitle && expiresAt) {
      return `Twoja rezerwacja na tytuł "${bookTitle}" jest gotowa do odbioru. Odbierz ją do ${expiresAt}.`
    }
  }

  if (type === 'announcement_published') {
    return message || 'Opublikowano nowe ogłoszenie.'
  }

  if (type === 'event_published') {
    return message || 'Opublikowano nowe wydarzenie.'
  }

  if (type === 'catalog_new_book') {
    return message || 'W katalogu pojawiła się nowa pozycja.'
  }

  return message
}

export function getNotificationDisplay(notification) {
  const type = normalizeText(notification?.type)
  const title = normalizeText(notification?.title) || normalizeText(notification?.subject)
  const message = translateMessage(notification)

  return {
    title: title || TYPE_LABELS[type] || 'Powiadomienie',
    message,
    typeLabel: TYPE_LABELS[type] || type || 'info',
  }
}