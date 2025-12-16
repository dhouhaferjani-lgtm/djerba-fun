/**
 * Session utility for managing guest checkout sessions.
 * Generates and stores a unique session ID for guest users.
 */

const SESSION_KEY = 'go_adventure_guest_session';

/**
 * Generate a UUID v4 for session identification.
 */
function generateUUID(): string {
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
    const r = (Math.random() * 16) | 0;
    const v = c === 'x' ? r : (r & 0x3) | 0x8;
    return v.toString(16);
  });
}

/**
 * Get or create a guest session ID.
 * The session ID persists in localStorage across page reloads.
 */
export function getGuestSessionId(): string {
  if (typeof window === 'undefined') {
    // Server-side: return a new UUID (will be replaced on client)
    return generateUUID();
  }

  let sessionId = localStorage.getItem(SESSION_KEY);
  if (!sessionId) {
    sessionId = generateUUID();
    localStorage.setItem(SESSION_KEY, sessionId);
  }

  return sessionId;
}

/**
 * Clear the guest session ID.
 * Call this after the user creates an account or logs in.
 */
export function clearGuestSessionId(): void {
  if (typeof window !== 'undefined') {
    localStorage.removeItem(SESSION_KEY);
  }
}
