import { defineStore } from "pinia";
import { ref } from "vue";

const THEME_KEY = "theme";
const STYLE_KEY = "style";
const SCHOOL_KEY = "school-theme";   // cache of the school's code, so refresh does not flash the default
const THEME_IDS = new Set(["theme1", "theme2", "theme3", "theme4", "theme5", "theme6"]);

export const useThemeStore = defineStore("theme", () => {
  // The empty id means the default Onion & Milky palette (:root).
  const themes = ref([
    { id: "", name: "Onion & Milky", description: "Default purple and soft milky neutrals" },
    { id: "theme1", name: "Goal Mastery", description: "Navy and clear sky blue" },
    { id: "theme2", name: "Royal Mint", description: "Royal purple and mint" },
    { id: "theme3", name: "Nanocodes", description: "Violet, deep purple and off-white" },
    { id: "theme4", name: "Blue Serenity", description: "Indigo and soft periwinkle" },
    { id: "theme5", name: "Ocean Breeze", description: "Blue, cyan and violet" },
    { id: "theme6", name: "Electric Teal", description: "Electric aqua and deep charcoal" },
  ]);

  const active = ref("");

  // Style axis: shape, depth, motion (see ek-styles.css). 'flat' = no attribute = classic look.
  const styles = ref([
    { id: "flat", label: "Classic" },
    { id: "glass", label: "Glass" },
    { id: "soft", label: "Soft" },
    { id: "elastic", label: "Elastic" },
  ]);
  const style = ref("flat");

  // Pass a theme id ('' = default). For a school's `theme` code use applyCode().
  function apply(id = "") {
    const safeId = THEME_IDS.has(id) ? id : "";
    active.value = safeId;

    if (safeId) {
      document.documentElement.dataset.theme = safeId;
    } else {
      document.documentElement.removeAttribute("data-theme");
    }
  }

  // The database code for the default palette is 'default'; the CSS id for it is ''.
  function applyCode(code) {
    apply(!code || code === "default" ? "" : code);
  }

  // The server told us which school colour is right: paint it and keep a copy in this browser.
  // The copy is only a head start for the next refresh; the server's answer always wins.
  // No code (logged out) clears the copy.
  function showSchool(code) {
    applyCode(code);
    if (code) localStorage.setItem(SCHOOL_KEY, code);
    else localStorage.removeItem(SCHOOL_KEY);
  }

  function applyStyle(id = "flat") {
    const safe = styles.value.some((s) => s.id === id) ? id : "flat";
    style.value = safe;
    if (safe !== "flat") {
      document.documentElement.dataset.style = safe;
      localStorage.setItem(STYLE_KEY, safe);
    } else {
      document.documentElement.removeAttribute("data-style");
      localStorage.removeItem(STYLE_KEY);
    }
  }

  // Style is the PERSON's choice and lives in this browser (localStorage has no expiry,
  // so it stays until the browser's site data is cleared). The school colour is painted from
  // the remembered copy straight away, before /me has answered.
  function init() {
    localStorage.removeItem(THEME_KEY);   // old personal-colour key; the school owns colour now
    applyCode(localStorage.getItem(SCHOOL_KEY));
    applyStyle(localStorage.getItem(STYLE_KEY) || "flat");
  }

  return { themes, active, apply, applyCode, showSchool, styles, style, applyStyle, init };
});
