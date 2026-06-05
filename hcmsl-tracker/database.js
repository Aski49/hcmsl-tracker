/**
 * ============================================================
 *  HCMSL Tracker — database.js  (XAMPP / MySQL edition — FIXED)
 *  All methods return Promises identical to the IndexedDB API.
 * ============================================================
 */

const DB = (() => {

  // ── Change this if your folder name is different ───────────
  const BASE = 'http://localhost/hcmsl-tracker/api';

  /**
   * Core fetch wrapper.
   * @param {string} endpoint  - 'staff' | 'contributions' | 'expenditure'
   * @param {string} method    - HTTP verb
   * @param {object|null} body - JSON body for POST
   * @param {object} params    - URL query params for GET / DELETE
   */
  async function request(endpoint, method = 'GET', body = null, params = {}) {
    const url = new URL(`${BASE}/${endpoint}.php`);

    // Append query params (skip empty values)
    Object.entries(params).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) {
        url.searchParams.set(k, v);
      }
    });

    const options = {
      method,
      headers: { 'Content-Type': 'application/json' },
    };
    if (body) options.body = JSON.stringify(body);

    let res;
    try {
      res = await fetch(url.toString(), options);
    } catch (networkErr) {
      console.error('[DB] Network error:', networkErr);
      throw new Error('Cannot reach server. Is XAMPP running?');
    }

    // Parse JSON even for error responses so we can show PHP messages
    const text = await res.text();
    let json;
    try {
      json = JSON.parse(text);
    } catch {
      console.error('[DB] Non-JSON response:', text);
      throw new Error('Server returned unexpected response.');
    }

    if (!res.ok) {
      const msg = json?.error || `HTTP ${res.status}`;
      console.error('[DB] API error:', msg);
      throw new Error(msg);
    }

    return json;
  }

  // ── Generic store API ───────────────────────────────────────
  function storeAPI(endpoint) {
    return {

      /** Insert a new record. Returns the saved record (with real DB id). */
      add(data) {
        return request(endpoint, 'POST', data);
      },

      /** Fetch all records. */
      getAll() {
        return request(endpoint, 'GET');
      },

      /** Fetch one record by id. */
      get(id) {
        return request(endpoint, 'GET', null, { id });
      },

      /** Delete a record by id. */
      delete(id) {
        return request(endpoint, 'DELETE', null, { id });
      },

      /**
       * Query with filters.
       * Pass an object: DB.contributions.query({ year: 2025, month: 'January' })
       */
      query(filterObj) {
        return request(endpoint, 'GET', null, filterObj || {});
      },
    };
  }

  // ── Public API ──────────────────────────────────────────────
  return {
    ready:         Promise.resolve(),   // MySQL is always ready
    staff:         storeAPI('staff'),
    contributions: storeAPI('contributions'),
    expenditure:   storeAPI('expenditure'),
    seedIfEmpty:   () => Promise.resolve(),  // seed via phpMyAdmin / SQL file
  };

})();