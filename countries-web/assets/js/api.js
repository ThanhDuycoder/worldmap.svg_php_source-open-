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
    throw new Error("Máy chủ trả về JSON không hợp lệ.");
  }

  if (!json?.ok) {
    const msg = json?.error?.message || `Yêu cầu thất bại (${res.status})`;
    const err = new Error(msg);
    err.meta = json?.meta;
    throw err;
  }

  return json.data;
}

export async function apiPost(path, body = {}) {
  const url = new URL(path, window.location.href);
  const res = await fetch(url.toString(), {
    method: "POST",
    headers: {
      "Accept": "application/json",
      "Content-Type": "application/json",
    },
    body: JSON.stringify(body),
  });

  let json;
  try {
    json = await res.json();
  } catch {
    throw new Error("Máy chủ trả về JSON không hợp lệ.");
  }

  if (!json?.ok) {
    const msg = json?.error?.message || `Yêu cầu thất bại (${res.status})`;
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
  },
  chat(question) {
    return apiPost("api/chat.php", { question });
  }
};

