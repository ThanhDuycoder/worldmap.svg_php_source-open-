export async function apiGet(path, params = {}) {
  const url = new URL(path, window.location.href);
  Object.entries(params).forEach(([k, v]) => {
    if (v === undefined || v === null) return;
    url.searchParams.set(k, String(v));
  });

  const res = await fetch(url.toString(), {
    headers: { "Accept": "application/json" }
  });

  let json;
  try {
    json = await res.json();
  } catch {
    throw new Error("Server returned invalid JSON.");
  }

  if (!json?.ok) {
    const msg = json?.error?.message || `Request failed (${res.status})`;
    const err = new Error(msg);
    err.meta = json?.meta;
    throw err;
  }

  return json.data;
}

export const CountriesApi = {
  all() {
    return apiGet("api/all.php");
  },
  countryByName(name) {
    return apiGet("api/country.php", { name });
  },
  countryByCode(code) {
    return apiGet("api/alpha.php", { code });
  },
  search(q, limit = 30) {
    return apiGet("api/search.php", { q, limit });
  }
};

