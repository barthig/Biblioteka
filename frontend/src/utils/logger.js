/**
 * Logging utility that respects environment
 * Only logs in development mode
 * In production, errors can be sent to error tracking service (e.g., Sentry)
 */
const isDev = import.meta.env.DEV;
const isTest = import.meta.env.MODE === 'test';
const shouldLog = isDev && !isTest;

export const logger = {
  /**
   * Log general information (development only)
   */
  log: (...args) => {
    if (shouldLog) console.log(...args);
  },
  
  /**
   * Log warnings (development only)
   */
  warn: (...args) => {
    if (shouldLog) console.warn(...args);
  },
  
  /**
   * Log errors (development only, could be sent to Sentry in production)
   */
  error: (...args) => {
    if (shouldLog) {
      console.error(...args);
    }
    // In production, send to error tracking service
    // Example: if (window.Sentry) window.Sentry.captureException(args[0])
  },
  
  /**
   * Log informational messages (development only)
   */
  info: (...args) => {
    if (shouldLog) console.info(...args);
  },
  
  /**
   * Log debug information (development only)
   */
  debug: (...args) => {
    if (shouldLog) console.debug(...args);
  }
};
