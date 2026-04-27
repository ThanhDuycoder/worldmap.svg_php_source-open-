function isCountryShape(el) {
  if (!el) return false;
  const tag = el.tagName?.toLowerCase();
  if (!["path", "polygon", "rect"].includes(tag)) return false;
  const token =
    el.getAttribute("name") ||
    el.getAttribute("data-name") ||
    el.getAttribute("class") ||
    el.getAttribute("id");
  return Boolean(String(token || "").trim());
}

function getCountryToken(el) {
  if (!el) return "";
  const byName = String(el.getAttribute("name") || "").trim();
  if (byName) return byName;

  const byDataName = String(el.getAttribute("data-name") || "").trim();
  if (byDataName) return byDataName;

  const byId = String(el.getAttribute("id") || "").trim();
  if (byId) return byId;

  // Some maps store country labels in class names.
  const classTokens = String(el.getAttribute("class") || "")
    .split(/\s+/)
    .map((x) => x.trim())
    .filter(Boolean)
    .filter((x) => !["country-shape", "country-selected"].includes(x.toLowerCase()));

  return classTokens.join(" ").trim();
}

export function initWorldMap({ rootEl, onPickCountryName }) {
  const svg = rootEl.querySelector("svg");
  if (!svg) throw new Error("SVG not found.");

  const shapes = Array.from(svg.querySelectorAll("path, polygon, rect")).filter(isCountryShape);
  shapes.forEach((el) => el.classList.add("country-shape"));

  let selectedEl = null;

  function setSelected(el) {
    if (selectedEl) selectedEl.classList.remove("country-selected");
    selectedEl = el;
    if (selectedEl) selectedEl.classList.add("country-selected");
  }

  svg.addEventListener("click", (e) => {
    const target = e.target?.closest?.("path,polygon,rect");
    if (!isCountryShape(target)) return;

    setSelected(target);
    const token = getCountryToken(target);
    if (token) onPickCountryName?.(token);
  });

  svg.addEventListener("mouseover", (e) => {
    const target = e.target?.closest?.("path,polygon,rect");
    if (!isCountryShape(target)) return;
    const title = getCountryToken(target);
    if (title) target.setAttribute("title", title);
  });

  return {
    selectByName(name) {
      const norm = String(name || "").trim().toLowerCase();
      if (!norm) return;
      const el = shapes.find((s) => getCountryToken(s).toLowerCase() === norm);
      if (el) setSelected(el);
    }
  };
}

