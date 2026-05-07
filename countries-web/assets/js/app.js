import { CountriesApi } from "./api.js";
import { initWorldMap } from "./map.js";
import { initChatbot } from "./chatbot.js";

const $ = (sel) => document.querySelector(sel);

const COUNTRY_ALIASES = new Map([
  ["usa", "US"],
  ["us", "US"],
  ["united states", "US"],
  ["united states of america", "US"],
  ["russia", "RU"],
  ["russian federation", "RU"],
  ["china", "CN"],
  ["people s republic of china", "CN"],
  ["people republic of china", "CN"],
]);

function formatNumber(n) {
  const x = Number(n);
  if (!Number.isFinite(x)) return "—";
  return x.toLocaleString("vi-VN");
}

function asListText(list) {
  return Array.isArray(list) && list.length ? list.join(", ") : "—";
}

function formatCoord(value, axis) {
  const n = Number(value);
  if (!Number.isFinite(n)) return "—";
  const abs = Math.abs(n);
  const dir = axis === "lat" ? (n >= 0 ? "N" : "S") : (n >= 0 ? "E" : "W");
  return `${abs.toFixed(4)}° ${dir}`;
}

function normalizeKey(text) {
  return String(text || "")
    .toLowerCase()
    .replaceAll("&", " and ")
    .replace(/[^a-z0-9]+/g, " ")
    .trim();
}

function showInfoPanel(show) {
  const panel = $("#countryInfoPanel");
  if (!panel) return;
  panel.classList.toggle("hidden", !show);
}

function renderCountryDetails(country) {
  const name = country?.name?.common || "Không rõ";
  const official = country?.name?.official || "";
  const capital = country?.capital || "—";
  const currencies = Array.isArray(country?.currencies) ? country.currencies : [];
  const cca2 = country?.cca2 || "—";
  const region = country?.region || "—";
  const subregion = country?.subregion || "—";
  const population = country?.population ?? null;
  const latlng = Array.isArray(country?.latlng) ? country.latlng : null;
  const languages = Array.isArray(country?.languages) ? country.languages : [];
  const timezones = Array.isArray(country?.timezones) ? country.timezones : [];
  const flagUrl = country?.flags?.png || country?.flags?.svg || "";
  const flagAlt = country?.flags?.alt || `Cờ ${name}`;
  const googleMaps = country?.maps?.googleMaps || "";
  const osm = country?.maps?.openStreetMaps || "";

  const curText = currencies.length
    ? currencies.map(c => `${c.code}${c.symbol ? ` (${c.symbol})` : ""}${c.name ? ` — ${c.name}` : ""}`).join(", ")
    : "—";

  $("#countryDetails").innerHTML = `
    <div class="countryCard">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
        <div>
          <h3 style="margin:0 0 4px">${escapeHtml(name)}</h3>
          <div class="status">${official ? escapeHtml(official) : ""}</div>
        </div>
        ${flagUrl ? `<img src="${escapeHtml(flagUrl)}" alt="${escapeHtml(flagAlt)}" style="width:56px;height:38px;object-fit:cover;border-radius:8px;border:1px solid rgba(255,255,255,.12)" />` : ""}
      </div>
      <div class="kv">
        <div class="k">Mã quốc gia</div><div class="v">${escapeHtml(cca2)}</div>
        <div class="k">Thủ đô</div><div class="v">${escapeHtml(capital)}</div>
        <div class="k">Khu vực</div><div class="v">${escapeHtml(region)}${subregion !== "—" ? ` • ${escapeHtml(subregion)}` : ""}</div>
        <div class="k">Vĩ/kinh độ</div><div class="v">${latlng ? `${escapeHtml(formatCoord(latlng[0], "lat"))}, ${escapeHtml(formatCoord(latlng[1], "lng"))}` : "—"}</div>
        <div class="k">Dân số</div><div class="v">${escapeHtml(formatNumber(population))}</div>
        <div class="k">Tiền tệ</div><div class="v">${escapeHtml(curText)}</div>
        <div class="k">Ngôn ngữ</div><div class="v">${escapeHtml(asListText(languages))}</div>
        <div class="k">Múi giờ</div><div class="v">${escapeHtml(asListText(timezones))}</div>
        <div class="k">Bản đồ</div>
        <div class="v">
          ${googleMaps ? `<a href="${escapeHtml(googleMaps)}" target="_blank" rel="noreferrer" style="color:inherit;text-decoration:underline">Google Maps</a>` : "—"}
          ${osm ? ` • <a href="${escapeHtml(osm)}" target="_blank" rel="noreferrer" style="color:inherit;text-decoration:underline">OpenStreetMap</a>` : ""}
        </div>
      </div>
    </div>
  `;
  showInfoPanel(true);
}

function setStatus(text) {
  $("#status").textContent = text || "";
}

function escapeHtml(s) {
  return String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

let allCountries = [];
let mapApi = null;
let lastPicked = null;

function findCca2ByName(name) {
  const n = normalizeKey(name);
  if (!n) return null;
  if (COUNTRY_ALIASES.has(n)) return COUNTRY_ALIASES.get(n) || null;

  const hit = allCountries.find((c) => {
    const common = normalizeKey(c?.name?.common || "");
    const official = normalizeKey(c?.name?.official || "");
    const code = normalizeKey(c?.cca2 || "");
    return n === common || n === official || n === code;
  });
  return hit?.cca2 || null;
}

async function pickCountryByCode(code, fallbackName = "") {
  const c = String(code || "").trim();
  if (!c) return;
  lastPicked = fallbackName || c;
  setStatus(`Đang tải: ${fallbackName || c}...`);
  try {
    const detail = await CountriesApi.countryByCode(c);
    renderCountryDetails(detail);
    if (fallbackName) mapApi?.selectByName(fallbackName);
    setStatus("");
  } catch (e) {
    renderCountryDetails({ name: { common: fallbackName || c, official: "" } });
    setStatus(e?.message || "Không thể tải thông tin quốc gia.");
  }
}

async function pickCountryByName(name) {
  const n = String(name || "").trim();
  if (!n) return;

  lastPicked = n;
  setStatus(`Đang tải: ${n}...`);
  try {
    const cca2 = findCca2ByName(n);
    if (cca2) {
      await pickCountryByCode(cca2, n);
    } else {
      const c = await CountriesApi.countryByName(n);
      renderCountryDetails(c);
      mapApi?.selectByName(n);
      setStatus("");
    }
  } catch (e) {
    renderCountryDetails({ name: { common: n, official: "" } });
    setStatus(e?.message || "Không thể tải thông tin quốc gia.");
  }
}

function filterLocal(q) {
  const n = String(q || "").trim().toLowerCase();
  if (!n) return allCountries;
  return allCountries.filter(c => String(c?.name?.common || "").toLowerCase().includes(n));
}

async function loadAll() {
  setStatus("Đang tải danh sách quốc gia...");
  allCountries = await CountriesApi.all();
  setStatus(`Đã tải ${allCountries.length} quốc gia.`);
  window.setTimeout(() => setStatus(""), 1200);
}

function setupSearch() {
  const input = $("#searchInput");
  input.addEventListener("keydown", async (e) => {
    if (e.key !== "Enter") return;
    const q = input.value.trim();
    if (!q) return;
    await pickCountryByName(q);
  });
}

function setupMap() {
  mapApi = initWorldMap({
    rootEl: $("#mapRoot"),
    onPickCountryName: (name) => pickCountryByName(name),
  });
}

function setupChatbot() {
  initChatbot();
}

function setupInfoPanelInteractions() {
  const closeBtn = $("#closeInfoPanelBtn");
  const panel = $("#countryInfoPanel");
  const mapRoot = $("#mapRoot");

  closeBtn?.addEventListener("click", () => {
    showInfoPanel(false);
  });

  document.addEventListener("click", (e) => {
    if (!panel || panel.classList.contains("hidden")) return;
    const target = e.target;
    if (!(target instanceof Element)) return;

    // Close when clicking outside the map area.
    if (!target.closest("#mapRoot")) {
      showInfoPanel(false);
      return;
    }

    // Clicks on map background (outside panel and country shapes) also hide panel.
    if (mapRoot && target.closest("#mapRoot") && !target.closest("#countryInfoPanel") && !target.closest(".country-shape")) {
      showInfoPanel(false);
    }
  });
}

async function main() {
  setupMap();
  setupSearch();
  setupChatbot();
  setupInfoPanelInteractions();
  await loadAll();
  showInfoPanel(false);
}

main().catch((e) => {
  setStatus(e?.message || "Ứng dụng gặp lỗi.");
});

