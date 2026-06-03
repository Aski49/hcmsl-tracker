/**
 * HCMSL Tracker — database.js (XAMPP / MySQL edition)
 * All methods return Promises, identical API to the IndexedDB version.
 */

const DB = (() => {
  const BASE = 'http://localhost/hcmsl-tracker/api';

  async function request(endpoint, method = 'GET', body = null, params = {}) {
    const url = new URL(`${BASE}/${endpoint}.php`);
    Object.entries(params).forEach(([k, v]) => { if (v) url.searchParams.set(k, v); });

    const res = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      body: body ? JSON.stringify(body) : null,
    });

    if (!res.ok) throw new Error(`API error ${res.status}: ${await res.text()}`);
    return res.json();
  }

  function storeAPI(endpoint) {
    return {
      add:    (data)          => request(endpoint, 'POST', data),
      getAll: ()              => request(endpoint),
      get:    (id)            => request(endpoint, 'GET', null, { id }),
      delete: (id)            => request(endpoint, 'DELETE', null, { id }),
      update: (id, changes)   => request(endpoint, 'POST', { ...changes, id }),
      query:  (filterOrObj)   => {
        const params = typeof filterOrObj === 'object' ? filterOrObj : {};
        return request(endpoint, 'GET', null, params);
      },
    };
  }

  return {
    ready:         Promise.resolve(),     // MySQL is always ready
    staff:         storeAPI('staff'),
    contributions: storeAPI('contributions'),
    expenditure:   storeAPI('expenditure'),
    seedIfEmpty:   () => Promise.resolve(), // seed via phpMyAdmin if needed
  };
})();