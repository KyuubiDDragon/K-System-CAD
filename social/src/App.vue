<script setup lang="ts">
import {
  computed,
  nextTick,
  onMounted,
  onUnmounted,
  reactive,
  ref,
  watch,
} from "vue";
import { api, upload, mediaUrl, type Row } from "./api";
import AccountsPanel from "./AccountsPanel.vue";
import CadBridge from "./CadBridge.vue";
import { selectAccount } from "./accountContext";
import VerifiedIcon from "./VerifiedIcon.vue";
import Icon from "./Icon.vue";
import PostCard from "./PostCard.vue";
import RichText from "./RichText.vue";
import CompanyMap from "./CompanyMap.vue";
import CompanyFields from "./CompanyFields.vue";
import CategoryFilter from "./CategoryFilter.vue";
import ThemeColors from "./ThemeColors.vue";
import LawsApp from "./LawsApp.vue";
import AdCard from "./AdCard.vue";
const settings = ref<Row>({ modules: {}, names: {}, icons: {}, links: [] }),
  me = ref<Row | null>(null),
  ready = ref(false),
  error = ref(""),
  accessNotice = ref(""),
  busy = ref(false);
const route = ref(location.hash.slice(2) || "apps"),
  page = computed(() => route.value.split("/")[0]),
  routeId = computed(() => Number(route.value.split("/")[1] || 0));
const modules = computed(() =>
  ["social", "gram", "market", "video"].filter(
    (m) => settings.value.modules[m],
  ),
);
const entryApps = computed(() => [...modules.value, "companies", ...(settings.value.laws_enabled ? ["laws"] : [])]);
const searchPosts = ref<Row[]>([]),
  searchCompanies = ref<Row[]>([]);
const discovery = ref<Row>({ highlights: [], open_companies: [], video: null });
const operatorName = computed(
  () =>
    settings.value.operator_name ||
    settings.value.names?.social ||
    "Social Media",
);
const openCompanies = computed(() =>
  (discovery.value.open_companies || []).filter(
    (c: Row) =>
      new Date(c.open_until.replace(" ", "T") + "Z").getTime() > clock.value,
  ),
);
const directorySearch = ref("");
const filteredCompanies = computed(() =>
  companies.value.filter((c) =>
    [c.name, c.description, c.location]
      .join(" ")
      .toLocaleLowerCase()
      .includes(directorySearch.value.toLocaleLowerCase()),
  ),
);
const isCompanyOpen = (c: Row) =>
  Boolean(
    c.open_until &&
    new Date(c.open_until.replace(" ", "T") + "Z").getTime() > clock.value,
  );
const companyPage = ref<Row | null>(null);
const posts = ref<Row[]>([]),
  next = ref<number | null>(null),
  tags = ref<Row>({}),
  people = ref<Row[]>([]),
  profile = ref<Row | null>(null),
  friendItems = ref<Row[]>([]),
  blocked = ref<Row[]>([]),
  notifications = ref<Row[]>([]),
  threads = ref<Row[]>([]),
  messages = ref<Row[]>([]),
  companies = ref<Row[]>([]),
  ads = ref<Row[]>([]),
  liveAds = ref<Row[]>([]),
  slots = ref<Row[]>([]),
  admin = ref<Row>({}),
  search = ref(""),
  category = ref(""),
  minPrice = ref(""),
  maxPrice = ref(""),
  filter = ref("all"),
  revision = ref(0);
const dialog = ref(""),
  form = reactive<Row>({}),
  selectedMedia = ref<Row[]>([]),
  uploading = ref(false),
  authMode = ref("login"),
  showAuth = ref(false),
  authReturn = ref(route.value),
  recovery = ref(""),
  authForm = reactive({
    handle: "",
    password: "",
    display_name: "",
    recovery_code: "",
  }),
  messageBody = ref(""),
  recipients = ref<number[]>([]),
  withSignature = ref(true),
  usePhotoGrid = ref(true),
  friendSearch = ref(""),
  theme = ref(localStorage.getItem("social-theme") || "system"),
  clock = ref(Date.now());
const themeClass = computed(() =>
  theme.value === "system" ? "" : theme.value,
);
const themeStyle = computed(() => {
  const style: Record<string, string> = {
    "--brand": settings.value.accent || "#087f80",
  };
  const palette = settings.value.theme_colors || {};
  for (const mode of ["light", "dark"])
    for (const [key, value] of Object.entries(palette[mode] || {})) {
      if (typeof value === "string" && /^#[0-9a-f]{6}$/i.test(value))
        style[`--theme-${mode}-${key}`] = value;
    }
  return style;
});
const hideAdPosts = ref(
  localStorage.getItem("social-hide-ad-posts") === "true",
);
watch(hideAdPosts, (value) => {
  localStorage.setItem("social-hide-ad-posts", String(value));
  void load();
});
const currentAds = computed(() =>
  liveAds.value.filter(
    (a) => new Date(a.ends_at.replace(" ", "T") + "Z").getTime() > clock.value,
  ),
);
const activeModule = computed(() =>
  ["social", "gram", "market", "video"].includes(page.value)
    ? page.value
    : page.value === "profile"
      ? "social"
      : posts.value[0]?.module || "social",
);
const canModerate = computed(() =>
    ["admin", "moderator"].includes(me.value?.role),
  ),
  canAds = computed(() => ["admin", "advertiser"].includes(me.value?.role)),
  canAdmin = computed(() => me.value?.role === "admin");
const adCompanies = computed(()=>companies.value.filter(c=>c.can_ads));
const mineCompanies = computed(() =>
    companies.value.filter((c) => Number(c.can_post)),
  ),
  heading = computed(
    () =>
      settings.value.names[page.value] ||
      (
        {
          bookmarks: "Gespeicherte Beiträge",
          companies: "Unternehmen",
          company: "Unternehmensprofil",
          apps: "Deine Plattformen",
          profile: "Profil",
          friends: "Freunde",
          messages: "Nachrichten",
          notifications: "Mitteilungen",
          account: "Mein Konto",
          accounts: "Kontoverwaltung",
          admin: "Verwaltung",
          ads: "Werbung",
          search: "Suche",
          post: "Beitrag",
        } as Row
      )[page.value] ||
      "Social",
  );
const stateName = (s: string) =>
  (
    ({
      active: "Freigegeben",
      pending: "Wartet auf Freigabe",
      accepted: "Angenommen",
      rejected: "Abgelehnt",
      suspended: "Gesperrt",
      published: "Veröffentlicht",
      hidden: "Ausgeblendet",
      member: "Benutzer",
      moderator: "Moderation",
      advertiser: "Werbeverwaltung",
      admin: "Technische Administration",
      ready: "Bereit",
      queued: "Wartet auf Verarbeitung",
      processing: "Wird verarbeitet",
      failed: "Fehlgeschlagen",
    }) as Row
  )[s] || s;
const amount = (n: any) =>
  Number(n).toLocaleString("de-DE", { maximumFractionDigits: 2 });
const date = (s: string) =>
  s
    ? new Date(s.includes("T") ? s : s.replace(" ", "T") + "Z").toLocaleString(
        "de-DE",
        { dateStyle: "medium", timeStyle: "short" },
      )
    : "";
function countdown(s: string) {
  const left = Math.max(
    0,
    Math.floor(
      (new Date(s.replace(" ", "T") + "Z").getTime() - clock.value) / 1000,
    ),
  );
  return left
    ? `${Math.floor(left / 86400)} T ${Math.floor((left % 86400) / 3600)} Std ${Math.floor((left % 3600) / 60)} Min`
    : "Aktion gestartet";
}
function go(p: string) {
  location.hash = "/" + p;
}
function hashChange(event?: HashChangeEvent) {
  if (!window.dispatchEvent(new CustomEvent('cad-before-close',{cancelable:true}))) { if(event)window.history.replaceState(null,'',event.oldURL);return; }
  route.value = location.hash.slice(2) || "apps";
  dialog.value = "";
  search.value = "";
  category.value = "";
  void load();
}
function showError(e: unknown) {
  if(e instanceof Error && e.message===accessNotice.value)return;
  error.value =
    e instanceof Error ? e.message : String(e).replace(/^Error: /, "");
}
async function run(fn: () => Promise<void>) {
  if (busy.value) return;
  busy.value = true;
  error.value = "";
  try {
    await fn();
  } catch (e) {
    showError(e);
  } finally {
    busy.value = false;
  }
}
async function bootstrap() {
  const r = await api("bootstrap");
  settings.value = r.settings;
  me.value = r.me;
  if(r.actor_id && !sessionStorage.getItem("social-account"))selectAccount(Number(r.actor_id));
  revision.value = r.revision;
  ready.value = true;
  if (
    (!location.hash || route.value === "apps") &&
    entryApps.value.length === 1
  )
    go(entryApps.value[0]);
}
async function load(append = false) {
  if (
    !ready.value ||
    (!me.value && !settings.value.guest) ||
    (me.value && me.value.status !== "active")
  )
    return;
  await run(async () => {
    if (
      ["social", "gram", "market", "video", "profile", "company"].includes(
        page.value,
      )
    ) {
      const m = ["profile", "company"].includes(page.value)
        ? "social"
        : page.value;
      if (page.value === "company")
        companyPage.value =
          (await api("companies")).items.find(
            (c: Row) => Number(c.id) === routeId.value,
          ) || null;
      if (!settings.value.modules[m]) {
        posts.value = [];
        return;
      }
      if (page.value === "profile")
        profile.value = (
          await api("profile", undefined, { id: routeId.value || me.value?.id })
        ).profile;
      const r = await api("posts", undefined, {
        hide_ad_posts: hideAdPosts.value ? 1 : 0,
        module: m,
        company: page.value === "company" ? routeId.value : undefined,
        q: search.value,
        before: append ? next.value : undefined,
        author:
          page.value === "profile" ? routeId.value || me.value?.id : undefined,
        filter: filter.value,
        category: category.value,
        min: minPrice.value,
        max: maxPrice.value,
      });
      posts.value = append ? [...posts.value, ...r.items] : r.items;
      next.value = r.next;
      tags.value = r.tags;
    }
    if (page.value === "bookmarks") {
      const r = await api("bookmarks", undefined, {
        hide_ad_posts: hideAdPosts.value ? 1 : 0,
        before: append ? next.value : undefined,
      });
      posts.value = append ? [...posts.value, ...r.items] : r.items;
      next.value = r.next;
    }
    if (page.value === "companies")
      companies.value = (await api("companies")).items;
    if (page.value === "post")
      posts.value = [
        (await api("post", undefined, { id: routeId.value })).post,
      ];
    if (page.value === "friends") {
      const r = await api("friends");
      friendItems.value = r.items;
      blocked.value = r.blocked;
    }
    if (page.value === "search" && search.value.trim()) {
      const r = await api("search", undefined, {
        q: search.value,
        hide_ad_posts: hideAdPosts.value ? 1 : 0,
      });
      people.value = r.people;
      searchPosts.value = r.posts;
      searchCompanies.value = r.companies;
    }
    if (page.value === "messages") {
      if (routeId.value) {
        const r = await api("messages", undefined, { with: routeId.value });
        messages.value = r.items;
        profile.value = r.profile;
      } else threads.value = (await api("messages")).items;
    }
    if (page.value === "notifications") {
      if(me.value?.delegated){await api("bootstrap");return;}
    notifications.value = (await api("notifications")).items;
    }
    if (page.value === "ads") {
      ads.value = (await api("ads")).items;
      slots.value = (await api("slots")).items;
    }
    if (page.value === "admin") admin.value = await api("admin");
    if (settings.value.modules.social && (me.value || settings.value.guest))
      liveAds.value = (await api("ads", undefined, { live: 1 })).items;
    else liveAds.value = [];
    discovery.value = await api("discovery", undefined, {
      hide_ad_posts: hideAdPosts.value ? 1 : 0,
    });
    if (me.value) companies.value = (await api("companies")).items;
  });
}
async function setCompanyOpen(minutes: number) {
  await run(async () => {
    await api("company_open", { id: routeId.value, minutes });
    discovery.value = await api("discovery", undefined, {
      hide_ad_posts: hideAdPosts.value ? 1 : 0,
    });
    companyPage.value = (await api("companies")).items.find(
      (c: Row) => Number(c.id) === routeId.value,
    );
  });
}
function beginAuth(mode='login') { if(!showAuth.value)authReturn.value=route.value;authMode.value=mode;showAuth.value=true;go('apps'); }
function resumeBrowsing(){showAuth.value=false;go(authReturn.value);void load();}
async function authenticate() {
  await run(async () => {
    const r = await api(authMode.value, authForm);
    if(r.profile_id)selectAccount(Number(r.profile_id));
    if (r.recovery_code) recovery.value = r.recovery_code;
    if (authMode.value === "recover") {
      authMode.value = "login";
      error.value = "Passwort geändert. Bitte anmelden.";
    } else { showAuth.value = false; await bootstrap();go(authReturn.value); }
  });
  if (me.value) await load();
}
function switchAccount(id:number,acting=0){
  if((dialog.value||messageBody.value) && !confirm('Offene Eingaben verwerfen und Konto wechseln?'))return;
  selectAccount(id,acting);location.hash='#/apps';location.reload();
}
function ownAccount(){switchAccount(Number(sessionStorage.getItem('social-account')));}
function manageAccounts(){
  if((dialog.value||messageBody.value) && !confirm('Offene Eingaben verwerfen und Kontenübersicht öffnen?'))return;
  if(me.value?.delegated){selectAccount(Number(sessionStorage.getItem('social-account')),0);location.hash='#/accounts';location.reload();}
  else go('accounts');
}
function accessLost(e:Event){
  accessNotice.value=(e as CustomEvent).detail||'Zugriff nicht mehr verfügbar. Bitte ein eigenes Konto anmelden.';
  selectAccount(-1);me.value=null;posts.value=[];messages.value=[];dialog.value='';
  if(['messages','account','notifications','admin','ads'].includes(page.value))go('apps');
  void bootstrap().then(()=>load()).catch(()=>{});
}
async function logout() {
  await run(async () => {
    await api("logout", {});
    selectAccount(-1);
    me.value = null;
    posts.value = [];
    messages.value = [];
    go("apps");
  });
}
function open(kind: string, data: Row = {}) {
  Object.keys(form).forEach((k) => delete form[k]);
  Object.assign(form, data);
  selectedMedia.value = data.media ? [...data.media] : [];
  dialog.value = kind;
}
function compose(p?: Row) {
  open(
    "post",
    p
      ? {
          ...p,
          ai_generated: Boolean(Number(p.ai_generated)),
          media: p.media.map((m: Row) => ({ ...m })),
        }
      : {
          module: activeModule.value,
          title: "",
          body: "",
          visibility: me.value?.delegated ? "public" : (me.value?.preferences.default_visibility || "friends"),
          price: 0,
          category:
            activeModule.value === "market"
              ? "Fahrzeuge"
              : settings.value.post_categories?.[0] || "Allgemein",
          ai_generated: false,
          sale_state: "available",
          wall_id: page.value === "profile" && !me.value?.delegated ? routeId.value : undefined,
        },
  );
}
async function files(event: Event, purpose = "post", module?: string) {
  const input = event.target as HTMLInputElement;
  if (!input.files?.length) return;
  uploading.value = true;
  try {
    for (const file of Array.from(input.files)) {
      const r = await upload(
        file,
        module || form.module || activeModule.value,
        purpose,
      );
      if (purpose === "profile") {
        form[input.name] = r.id;
      } else if (purpose === "branding") {
        if (["background_id", "logo_id", "header_id"].includes(input.name))
          form[input.name] = r.id;
        else form.icons = { ...form.icons, [input.name]: r.id };
      } else selectedMedia.value.push(r);
    }
  } catch (e) {
    showError(e);
  } finally {
    uploading.value = false;
    input.value = "";
  }
}
async function savePost() {
  await run(async () => {
    await api("save_post", {
      ...form,
      media: selectedMedia.value.map((m) => m.id),
    });
    dialog.value = "";
  });
  await load();
}
async function removePost() {
  if (!confirm("Diesen Beitrag löschen?")) return;
  await run(async () => {
    await api("delete_post", { id: form.id });
    dialog.value = "";
  });
  await load();
}
function share(p: Row) {
  open("post", {
    module: "social",
    body: "",
    title: "",
    visibility: me.value?.delegated ? "public" : (me.value?.preferences.default_visibility || "friends"),
    shared_id: p.id,
  });
}
async function friend(id: number, action: string) {
  await run(async () => {
    await api("friend", { id, action });
  });
  await load();
}
async function block(id: number, remove = false) {
  await run(async () => {
    await api("block", { id, remove });
  });
  await load();
}
async function sendMessage() {
  await run(async () => {
    await api("send", {
      body: messageBody.value,
      recipients: routeId.value ? [routeId.value] : recipients.value,
      media: selectedMedia.value.map((m) => m.id),
      signature: withSignature.value,
    });
    messageBody.value = "";
    selectedMedia.value = [];
    dialog.value = "";
  });
  await load();
}
const activeFormats = ref<string[]>([]);
function inspectPostFormat() {
  const el = document.getElementById(
    "post-editor",
  ) as HTMLTextAreaElement | null;
  if (!el) return;
  const value = String(form.body || "");
  const selected = value.slice(el.selectionStart, el.selectionEnd);
  activeFormats.value = ["**", "*", "~~", "• "].filter((mark) =>
    mark === "• "
      ? value
          .slice(
            value.lastIndexOf("\n", el.selectionStart - 1) + 1,
            el.selectionStart + 2,
          )
          .startsWith("• ")
      : (selected.startsWith(mark) &&
          selected.endsWith(mark) &&
          selected.length >= mark.length * 2 &&
          (mark !== "*" || !selected.startsWith("**"))) ||
        (value.slice(
          Math.max(0, el.selectionStart - mark.length),
          el.selectionStart,
        ) === mark &&
          value.slice(el.selectionEnd, el.selectionEnd + mark.length) === mark),
  );
}
async function formatPost(mark: string) {
  const el = document.getElementById("post-editor") as HTMLTextAreaElement;
  let start = el.selectionStart,
    end = el.selectionEnd;
  const body = String(form.body || "");
  let selected =
    body.slice(start, end) || (mark === "• " ? "Listenpunkt" : "Text");
  let replacement: string;
  if (mark === "• ")
    replacement = selected
      .split("\n")
      .map((line) => (line.startsWith("• ") ? line.slice(2) : "• " + line))
      .join("\n");
  else if (
    selected.startsWith(mark) &&
    selected.endsWith(mark) &&
    selected.length > mark.length * 2
  )
    replacement = selected.slice(mark.length, -mark.length);
  else if (
    body.slice(start - mark.length, start) === mark &&
    body.slice(end, end + mark.length) === mark
  ) {
    start -= mark.length;
    end += mark.length;
    replacement = selected;
  } else replacement = mark + selected + mark;
  form.body = body.slice(0, start) + replacement + body.slice(end);
  await nextTick();
  el.focus();
  el.setSelectionRange(start, start + replacement.length);
  inspectPostFormat();
}
function formatMessage(mark: string) {
  const el = document.getElementById(
    "message-editor",
  ) as HTMLTextAreaElement | null;
  if (!el) return;
  const a = el.selectionStart,
    b = el.selectionEnd;
  messageBody.value =
    messageBody.value.slice(0, a) +
    mark +
    messageBody.value.slice(a, b) +
    mark +
    messageBody.value.slice(b);
}
async function findPeople() {
  await run(async () => {
    people.value = (
      await api("people", undefined, { q: friendSearch.value })
    ).items;
  });
}
function editProfile() {
  open("profile", {
    ...me.value,
    notifications: true,
    ...me.value?.preferences,
  });
}
async function saveProfile() {
  await run(async () => {
    await api("save_profile", form);
    await bootstrap();
    dialog.value = "";
  });
  await load();
}
function editSettings() {
  open(
    "settings",
    JSON.parse(
      JSON.stringify({ ...settings.value, ...(admin.value.settings || {}) }),
    ),
  );
}
async function saveSettings() {
  await run(async () => {
    const config = { ...form };
    if (config.post_categories)
      config.post_categories = config.post_categories
        .map((c: string) => c.trim())
        .filter(Boolean);
    await api("save_settings", { settings: config, revision: revision.value });
    await bootstrap();
    dialog.value = "";
  });
  await load();
}
async function moderate(kind: string, id: number, value: string) {
  const reason = prompt("Begründung für diese Entscheidung:");
  if (!reason) return;
  let until: string | undefined;
  if (value === "suspended") {
    const days = prompt("Sperrdauer in Tagen (leer = unbefristet):", "7");
    if (days && Number(days) > 0)
      until = new Date(Date.now() + Number(days) * 86400000).toISOString();
  }
  await run(async () => {
    await api("moderate", { kind, id, value, reason, until });
  });
  await load();
}
async function purge(module: string) {
  await run(async () => {
    const r = await api("purge", { module });
    open("purge", { ...r, module });
  });
}
async function confirmPurge() {
  await run(async () => {
    const r = await api("purge", {
      module: form.module,
      confirm: form.confirm,
    });
    dialog.value = "";
    if (r.file_cleanup_failed)
      error.value = `Daten gelöscht; ${r.file_cleanup_failed} Dateien benötigen eine erneute Speicherbereinigung.`;
  });
  await load();
}
function requestAd() {
  open("ad", {
    company_id: adCompanies.value[0]?.id,
    slot_id: slots.value[0]?.id,
    title: "",
    body: "",
    target: "",
    starts_at: "",
    ends_at: "",
    countdown_at: "",
  });
}
async function submitAd() {
  await run(async () => {
    await api("request_ad", {
      ...form,
      starts_at: new Date(form.starts_at).toISOString(),
      ends_at: new Date(form.ends_at).toISOString(),
      countdown_at: form.countdown_at
        ? new Date(form.countdown_at).toISOString()
        : null,
      media: selectedMedia.value.map((m) => m.id),
    });
    dialog.value = "";
  });
  await load();
}
async function decideAd(ad: Row, decision: string) {
  open("decision", {
    id: ad.id,
    decision,
    amount: ad.amount,
    reason: "",
    message:
      decision === "accepted"
        ? admin.value.settings.accept_template
        : admin.value.settings.reject_template,
  });
  if (decision === "paid") await submitDecision();
}
async function submitDecision() {
  await run(async () => {
    await api("decide_ad", form);
    dialog.value = "";
  });
  await load();
}
async function saveSlot() {
  await run(async () => {
    await api("save_slot", {
      ...form,
      starts_at: new Date(form.starts_at).toISOString(),
      ends_at: new Date(form.ends_at).toISOString(),
    });
    dialog.value = "";
  });
  await load();
}
async function saveCompanyDetails() {
  await run(async () => {
    await api("company_details", form);
    dialog.value = "";
  });
  await load();
}
async function saveCompany() {
  await run(async () => {
    await api("save_company", {
      ...form,
      members: (form.members || []).map(Number),
    });
    dialog.value = "";
  });
  await load();
}
function onEscape(e: KeyboardEvent) {
  if (e.key === "Escape") dialog.value = "";
  trapTab(e);
}
const unread = computed(
  () => notifications.value.filter((n) => !n.read_at).length,
);
let returnFocus: HTMLElement | null = null;
watch(dialog, async (value, old) => {
  if (value && !old) {
    returnFocus = document.activeElement as HTMLElement;
    await nextTick();
    document
      .querySelector<HTMLElement>(".modal input,.modal textarea,.modal button")
      ?.focus();
  } else if (!value && old) returnFocus?.focus();
});
function trapTab(e: KeyboardEvent) {
  if (e.key !== "Tab" || !dialog.value) return;
  const els = Array.from(
    document.querySelectorAll<HTMLElement>(
      ".modal button:not(:disabled),.modal input,.modal select,.modal textarea,.modal a[href]",
    ),
  );
  if (!els.length) return;
  if (e.shiftKey && document.activeElement === els[0]) {
    e.preventDefault();
    els[els.length - 1].focus();
  } else if (!e.shiftKey && document.activeElement === els[els.length - 1]) {
    e.preventDefault();
    els[0].focus();
  }
}
async function pollInbox() {
  if (
    document.hidden ||
    !me.value ||
    me.value.status !== "active" ||
    busy.value
  )
    return;
  try {
    if(me.value?.delegated){await api("bootstrap");return;}
    notifications.value = (await api("notifications")).items;
    if (
      page.value === "messages" &&
      routeId.value &&
      settings.value.modules.messages
    ) {
      const r = await api("messages", undefined, {
        with: routeId.value,
        after: messages.value.at(-1)?.id || 0,
      });
      const seen = new Set(messages.value.map((m) => m.id));
      messages.value.push(...r.items.filter((m: Row) => !seen.has(m.id)));
    }
  } catch {
    /* Foreground actions report failures; background polling never replaces typed text. */
  }
}
async function olderMessages() {
  await run(async () => {
    const r = await api("messages", undefined, {
      with: routeId.value,
      before: messages.value[0]?.id,
    });
    messages.value = [...r.items, ...messages.value];
  });
}
let timer: ReturnType<typeof setInterval>;
let inboxTimer: ReturnType<typeof setInterval>;
onMounted(async () => {
  window.addEventListener("social-access-lost",accessLost);
  window.addEventListener("hashchange", hashChange);
  window.addEventListener("keydown", onEscape);
  timer = setInterval(() => (clock.value = Date.now()), 30000);
  inboxTimer = setInterval(pollInbox, 10000);
  try {
    await bootstrap();
    await load();
  } catch (e) {
    showError(e);
  }
});
onUnmounted(() => {
  window.removeEventListener("social-access-lost",accessLost);
  clearInterval(timer);
  clearInterval(inboxTimer);
  window.removeEventListener("hashchange", hashChange);
  window.removeEventListener("keydown", onEscape);
});
watch(theme, () => localStorage.setItem("social-theme", theme.value));
</script>
<template>
  <div
    class="suite"
    :class="[themeClass, 'icons-' + settings.icon_set]"
    :style="themeStyle"
  >
    <header class="topbar">
      <div class="topbar-inner">
        <a href="#/apps" class="wordmark"
          ><img
            v-if="settings.logo_id"
            :src="mediaUrl(settings.logo_id)"
            alt="Community-Logo"
          /><span v-else>k</span> {{ operatorName }}</a
        ><button class="quiet apps-button" @click="go('apps')">
          <Icon name="apps" />Apps
        </button>
        <form
          v-if="me?.status === 'active'"
          class="top-search"
          @submit.prevent="
            page === 'friends' || page === 'search'
              ? (go('search'), load())
              : load()
          "
        >
          <Icon name="search" /><input
            v-model="search"
            placeholder="Suchen …"
            aria-label="Suche"
          /><button class="sr-only">Suchen</button>
        </form>
        <div class="account-nav">
          <button
            class="icon-button"
            @click="theme = theme === 'dark' ? 'light' : 'dark'"
            :aria-label="theme === 'dark' ? 'Heller Modus' : 'Dunkler Modus'"
          >
            <Icon name="sun" /></button
          ><template v-if="me?.status === 'active'"
            ><button
              v-if="settings.modules.messages && !me.delegated"
              class="icon-button"
              aria-label="Nachrichten"
              @click="go('messages')"
            >
              <Icon name="messages" /></button
            ><button
              class="icon-button"
              v-if="!me.delegated"
              aria-label="Freunde"
              @click="go('friends')"
            >
              <Icon name="friends" /></button
            ><button
              class="icon-button"
              v-if="!me.delegated"
              aria-label="Benachrichtigungen"
              @click="go('notifications')"
            >
              <Icon name="bell" /><span
                v-if="unread"
                class="notification-count"
                >{{ unread > 99 ? "99+" : unread }}</span
              >
            </button>
            <details class="profile-menu">
              <summary class="avatar" aria-label="Profilmenü">
                <img
                  v-if="me.avatar_id"
                  :src="mediaUrl(me.avatar_id)"
                  alt=""
                /><span v-else>{{ me.display_name.slice(0, 2) }}</span>
              </summary>
              <div class="menu">
                <button @click="go('profile/' + me.id)">Mein Profil</button
                ><button @click="manageAccounts">Konten &amp; Zugriffe</button><button v-if="!me.delegated" @click="go('account')">Einstellungen</button
                ><button v-if="settings.modules.social && !me.delegated" @click="go('ads')">
                  Werbung</button
                ><button v-if="canModerate || canAds" @click="go('admin')">
                  Verwaltung</button
                ><button v-if="!me.delegated" @click="logout">Abmelden</button><button v-else @click="ownAccount()">Zurück zum eigenen Konto</button>
              </div>
            </details></template
          ><button v-else-if="me" @click="logout">Abmelden</button
          ><button v-else-if="settings.guest || page === 'laws'" @click="beginAuth()">
            Anmelden
          </button><button v-if="!me" @click="go('accounts')">Gespeicherte Konten</button>
        </div>
      </div>
    </header>
    <CadBridge :me="me" :ready="ready" :cad-url="settings.cad_url" @login="switchAccount" />
    <p v-if="me?.delegated" class="notice">Du arbeitest als {{me.display_name}} · angemeldet: {{me.actor_name}}. <button @click="manageAccounts">Konto wechseln</button></p>
    <div v-if="accessNotice" class="feedback access-notice" role="alert"><span>{{accessNotice}}</span><button @click="accessNotice=''" aria-label="Zugriffshinweis schließen">Schließen</button></div>
    <div v-if="error" class="feedback" role="alert">
      <span>{{ error }}</span
      ><button
        class="icon-button"
        aria-label="Meldung schließen"
        @click="error = ''"
      >
        <Icon name="close" />
      </button>
    </div>
    <div v-if="!ready" class="empty">
      {{
        error ? "Social konnte nicht geladen werden." : "Social wird geladen …"
      }}<button v-if="error" @click="bootstrap">Erneut versuchen</button>
    </div>
    <LawsApp v-else-if="page === 'laws' || page === 'laws-editor'" :editorial="page === 'laws-editor'" :cad-url="settings.cad_url || ''" />
    <AccountsPanel v-else-if="page === 'accounts'" :me="me" @switch="switchAccount" @company="go('company/'+$event)" />
    <section v-else-if="!me && (!settings.guest || showAuth)" class="auth-layout">
      <div class="auth-intro">
        <span class="eyebrow">{{ settings.community }}</span>
        <h1>Deine Stadt.<br />Deine Menschen.</h1>
        <p>Beiträge, Fotos, Videos und Angebote aus deiner Community.</p>
        <div class="auth-public-links"><button v-if="settings.guest" @click="resumeBrowsing()">Ohne Anmeldung weiterlesen</button><button v-if="settings.laws_enabled" class="text-button" @click="go('laws')">Gesetze ansehen ↗</button></div>
      </div>
      <form class="panel auth-form" @submit.prevent="authenticate">
        <h2>
          {{
            authMode === "register"
              ? "Konto erstellen"
              : authMode === "recover"
                ? "Konto wiederherstellen"
                : "Willkommen zurück"
          }}
        </h2>
        <label
          >Benutzername<input
            v-model="authForm.handle"
            required
            autocomplete="username"
            minlength="3"
            maxlength="40" /></label
        ><label v-if="authMode === 'register'"
          >Anzeigename<input
            v-model="authForm.display_name"
            required
            maxlength="100" /></label
        ><label
          >{{ authMode === "recover" ? "Neues Passwort" : "Passwort"
          }}<input
            v-model="authForm.password"
            type="password"
            required
            :autocomplete="
              authMode === 'login' ? 'current-password' : 'new-password'
            "
            :minlength="authMode === 'login' ? 1 : 12" /></label
        ><label v-if="authMode === 'recover'"
          >Wiederherstellungscode<input
            v-model="authForm.recovery_code"
            required
            autocomplete="off" /></label
        ><button class="primary" :disabled="busy">
          {{
            authMode === "register"
              ? "Registrieren"
              : authMode === "recover"
                ? "Passwort zurücksetzen"
                : "Anmelden"
          }}
        </button>
        <div class="row">
          <button
            type="button"
            class="text-button"
            @click="authMode = authMode === 'register' ? 'login' : 'register'"
          >
            {{
              authMode === "register" ? "Zur Anmeldung" : "Konto erstellen"
            }}</button
          ><button
            type="button"
            class="text-button"
            @click="authMode = 'recover'"
          >
            Passwort vergessen?
          </button>
        </div>
        <p class="muted small">
          Eigenes Social-Konto. Deine Behördenanmeldung bleibt getrennt.
        </p>
        <div v-if="recovery" class="notice">
          Neuen Wiederherstellungscode sicher aufbewahren:<code>{{
            recovery
          }}</code>
        </div>
      </form>
    </section>
    <section
      v-else-if="me && me.status !== 'active'"
      class="narrow panel pending"
    >
      <Icon name="shield" />
      <h1>{{ stateName(me.status) }}</h1>
      <p>{{ settings.registration_hint }}</p>
      <p v-if="me.account_notice" class="notice">{{ me.account_notice }}</p>
      <p class="muted">@{{ me.handle }}</p>
      <div v-if="recovery" class="notice">
        Wiederherstellungscode einmalig sichern:<code>{{ recovery }}</code
        ><button @click="recovery = ''">Gesichert</button>
      </div>
      <button @click="bootstrap">Status aktualisieren</button
      ><button @click="open('appeal', { reason: '' })">
        Rückfrage / Einspruch
      </button>
    </section>
    <template v-else>
      <section
        v-if="page === 'apps'"
        class="launcher"
        :style="{
          backgroundImage: `linear-gradient(180deg,rgba(8,19,28,.35),rgba(8,19,28,.78)),url(${settings.background_id ? mediaUrl(settings.background_id) : '/city.png'})`,
        }"
      >
        <div class="launcher-inner">
          <span class="eyebrow">{{ settings.community }}</span>
          <h1>Deine Stadt. Deine Plattformen.</h1>
          <p>Wo möchtest du starten?</p>
          <div class="launch-grid">
            <button
              v-for="m in modules"
              :key="m"
              class="launch-tile"
              @click="go(m)"
            >
              <span class="app-symbol"
                ><img
                  v-if="settings.icons[m]"
                  :src="mediaUrl(settings.icons[m])"
                  alt="" /><Icon v-else :name="'app-' + m" /></span
              ><strong>{{ settings.names[m] }}</strong>
            </button>
            <button v-if="settings.laws_enabled" class="launch-tile" @click="go('laws')"><span class="app-symbol"><img v-if="settings.icons.laws" :src="mediaUrl(settings.icons.laws)" alt=""/><span v-else>§</span></span><strong>{{ settings.names.laws || "Gesetze" }}</strong></button>
            <button class="launch-tile" @click="go('companies')">
              <span class="app-symbol"><Icon name="app-market" /></span
              ><strong>{{ settings.names.companies || "Unternehmen" }}</strong>
            </button>
          </div>
          <p v-if="!modules.length">
            Zurzeit sind keine Plattformen aktiviert.
          </p>
          <button v-if="canAdmin" class="launcher-config" @click="editSettings">
            <Icon name="settings" />Community anpassen
          </button>
        </div>
      </section>
      <div v-else class="page-wrap">
        <div class="view-options">
          <button
            class="quiet"
            :aria-pressed="hideAdPosts"
            :disabled="busy"
            title="Beiträge mit Kategorie Werbung oder #Werbung ausblenden. Werbeplätze bleiben sichtbar."
            @click="hideAdPosts = !hideAdPosts"
          >
            {{ hideAdPosts ? "✓ Werbebeiträge ausblenden" : "Werbebeiträge ausblenden" }}
          </button>
        </div>
        <section
          v-if="['social', 'gram', 'market', 'video'].includes(page)"
          class="feed-banner"
          :style="{
            backgroundImage: `linear-gradient(90deg, rgba(9,25,34,.84), rgba(9,25,34,.15)), url(${settings.header_id ? mediaUrl(settings.header_id) : '/city.png'})`,
          }"
        >
          <div>
            <span class="eyebrow">{{ operatorName }}</span>
            <h1>{{ heading }}</h1>
            <p>Was die Stadt bewegt. Was dich verbindet.</p>
          </div>
          <span class="banner-signature">Dein Moment. Deine Stadt.</span>
        </section>
        <div v-else class="page-title">
          <h1>{{ heading }}</h1>
        </div>
        <div v-if="recovery" class="notice">
          Wiederherstellungscode einmalig sichern:<code>{{ recovery }}</code
          ><button @click="recovery = ''">Gesichert</button>
        </div>
        <AdCard
          v-for="a in currentAds.filter((a) => a.placement === 'header')"
          :key="a.id"
          :ad="a"
          :now="clock"
        />
        <div
          class="content-grid"
          :class="{
            'wide-content': [
              'admin',
              'account',
              'accounts',
              'friends',
              'messages',
              'ads',
            ].includes(page),
          }"
        >
          <aside class="left-rail">
            <section
              v-if="settings.modules.social && discovery.highlights.length"
              class="side-box highlights-box"
            >
              <div class="side-cover">
                <span class="eyebrow">Entdecken</span>
                <h3>Highlights</h3>
              </div>
              <button
                v-for="(p, i) in discovery.highlights"
                :key="p.id"
                class="discovery-item"
                @click="go('post/' + p.id)"
              >
                <span class="rank">0{{ Number(i) + 1 }}</span
                ><span
                  ><strong>{{
                    p.title || p.body || "Geteilter Beitrag"
                  }}</strong
                  ><small
                    >{{ p.company?.name || p.author.display_name
                    }}<VerifiedIcon v-if="Number(p.company?.verified)" /></small
                ></span>
              </button>
            </section>
            <section v-if="page === 'market' || Object.keys(tags).length" class="side-box topics-box">
              <template v-if="page === 'market'"
                ><h3>Kategorien</h3>
                <button
                  v-for="c in [
                    'Alle',
                    'Fahrzeuge',
                    'Immobilien',
                    'Elektronik',
                    'Sonstiges',
                  ]"
                  :key="c"
                  class="topic"
                  :class="{ active: category === (c === 'Alle' ? '' : c) }"
                  @click="
                    category = c === 'Alle' ? '' : c;
                    load();
                  "
                >
                  {{ c }}
                </button>
                <form class="price-filter" @submit.prevent="load()">
                  <h3>Preis (RP-$)</h3>
                  <label
                    >Von<input
                      v-model="minPrice"
                      type="number"
                      min="0" /></label
                  ><label
                    >Bis<input
                      v-model="maxPrice"
                      type="number"
                      min="0" /></label
                  ><button>Filtern</button>
                </form></template
              ><template v-else>
                <h3>Themen</h3>
                <button
                  v-for="(count, tag) in tags"
                  :key="tag"
                  class="topic"
                  @click="
                    search = String(tag);
                    load();
                  "
                >
                  <strong>{{ tag }}</strong
                  ><small>{{ count }} Beiträge auf dieser Seite</small>
                </button>
                <p v-if="!Object.keys(tags).length" class="small muted">
                  Hashtags erscheinen hier, sobald Beiträge vorhanden sind.
                </p></template
              >
            </section>
            <section v-if="openCompanies.length" class="side-box">
              <div class="side-heading">
                <span class="live-dot"></span>
                <h3>Jetzt geöffnet</h3>
              </div>
              <button
                v-for="c in openCompanies"
                :key="c.id"
                class="discovery-item"
                @click="go('company/' + c.id)"
              >
                <span class="company-monogram">{{ c.name.slice(0, 1) }}</span
                ><span
                  ><strong
                    >{{ c.name
                    }}<VerifiedIcon v-if="Number(c.verified)" /></strong
                  ><small
                    >Geöffnet bis
                    {{
                      new Date(
                        c.open_until.replace(" ", "T") + "Z",
                      ).toLocaleTimeString("de-DE", {
                        hour: "2-digit",
                        minute: "2-digit",
                      })
                    }}</small
                  ></span
                >
              </button>
              <p v-if="!openCompanies.length" class="small muted">
                Aktuell hat kein Unternehmen eine Öffnung gemeldet.
              </p>
            </section>
            <section
              v-for="(block, i) in settings.info_blocks || []"
              :key="i"
              class="community-links side-box"
            >
              <h3>{{ block.title }}</h3>
              <RichText :text="block.body" />
            </section>
            <section
              v-if="settings.links.length"
              class="community-links side-box"
            >
              <h3>Community</h3>
              <a
                v-for="link in settings.links"
                :key="link.url"
                :href="link.url"
                target="_blank"
                rel="noopener noreferrer"
                >{{ link.label }} ↗</a
              >
            </section>
            <section class="side-box">
              <h3>Entdecken</h3>
              <button class="quiet" @click="go('companies')">
                Alle Unternehmen</button
              ><button v-if="me" class="quiet" @click="go('bookmarks')">
                Gespeicherte Beiträge
              </button>
            </section>
            <button v-if="me" class="quiet" @click="go('search')">
              <Icon name="search" />Personen finden
            </button>
          </aside>
          <main :aria-busy="busy">
            <section v-if="page === 'companies'" class="panel directory">
              <h2>Unternehmensverzeichnis</h2>
              <label
                >Unternehmen suchen<input
                  v-model="directorySearch"
                  placeholder="Name, Standort oder Beschreibung"
              /></label>
              <article
                v-for="c in filteredCompanies"
                :key="c.id"
                class="company-listing"
              >
                <div class="row between">
                  <button class="text-button" @click="go('company/' + c.id)">
                    <strong
                      >{{ c.name }}<VerifiedIcon v-if="Number(c.verified)"
                    /></strong>
                  </button>
                </div>
                <img
                  v-if="c.photo_id"
                  class="company-photo"
                  :src="mediaUrl(c.photo_id)"
                  :alt="c.name"
                />
                <p v-if="c.services" class="small">{{ c.services }}</p>
                <p v-if="c.service_area" class="small muted">
                  Einsatzgebiet: {{ c.service_area }}
                </p>
                <p class="small muted">
                  {{ c.description || "Noch keine Beschreibung." }}
                </p>
                <p v-if="c.location" class="small">
                  Standort: {{ c.location }}
                </p>
                <p v-if="c.contact" class="small">Kontakt: {{ c.contact }}</p>
                <span
                  class="badge"
                  :class="{ 'open-badge': isCompanyOpen(c) }"
                  >{{
                    isCompanyOpen(c) ? "Jetzt geöffnet" : "Geschlossen"
                  }}</span
                >
              </article>
              <p v-if="!filteredCompanies.length" class="muted">
                Keine Unternehmen gefunden.
              </p>
            </section>
            <template
              v-if="
                [
                  'social',
                  'gram',
                  'market',
                  'video',
                  'profile',
                  'company',
                  'bookmarks',
                  'post',
                ].includes(page)
              "
            >
              <section v-if="page === 'company'" class="panel">
                <span class="eyebrow">Unternehmensseite</span>
                <h2>
                  {{ companyPage?.name || "Seite nicht gefunden"
                  }}<VerifiedIcon v-if="Number(companyPage?.verified)" />
                </h2>
                <div v-if="companyPage" class="post-labels">
                  <span
                    class="badge"
                    :class="{ 'open-badge': isCompanyOpen(companyPage) }"
                    >{{
                      isCompanyOpen(companyPage)
                        ? "Jetzt geöffnet"
                        : "Geschlossen"
                    }}</span
                  >
                </div>
                <p v-if="companyPage?.location">
                  Standort: {{ companyPage.location }}
                </p>
                <p v-if="companyPage?.contact">
                  Kontakt: {{ companyPage.contact }}
                </p>
                <img
                  v-if="companyPage?.photo_id"
                  class="company-photo"
                  :src="mediaUrl(companyPage.photo_id)"
                  :alt="companyPage.name"
                />
                <RichText :text="companyPage?.description || ''" />
                <section v-if="companyPage?.services">
                  <h3>Leistungen / Zuständigkeit</h3>
                  <RichText :text="companyPage.services" />
                </section>
                <p v-if="companyPage?.service_area">
                  Einsatzgebiet: {{ companyPage.service_area }}
                </p>
                <section v-if="companyPage?.opening_hours">
                  <h3>Reguläre Öffnungszeiten</h3>
                  <RichText :text="companyPage.opening_hours" />
                </section>
                <CompanyMap
                  v-if="
                    companyPage?.map_x != null && companyPage?.map_y != null
                  "
                  :x="companyPage.map_x"
                  :y="companyPage.map_y"
                />
                <div
                  v-if="
                    companyPage && (Number(companyPage.can_edit) || canModerate)
                  "
                  class="row"
                >
                  <button @click="open('company-details', { ...companyPage })">
                    Informationen bearbeiten</button
                  ><button @click="setCompanyOpen(120)">
                    Für 2 Stunden öffnen</button
                  ><button @click="setCompanyOpen(0)">
                    Als geschlossen melden
                  </button>
                </div>
              </section>
              <div
                v-if="page === 'profile' && profile"
                class="profile-card panel"
              >
                <img
                  v-if="profile.cover_id"
                  class="cover"
                  :src="mediaUrl(profile.cover_id)"
                  alt="Profilheader"
                />
                <div class="profile-details">
                  <span class="avatar large"
                    ><img
                      v-if="profile.avatar_id"
                      :src="mediaUrl(profile.avatar_id)"
                      alt=""
                    /><span v-else>{{
                      profile.display_name.slice(0, 2)
                    }}</span></span
                  >
                  <div>
                    <h2>{{ profile.display_name }}</h2>
                    <p class="muted">
                      @{{ profile.handle }}
                      <span v-if="profile.online" class="badge">Online</span>
                    </p>
                  </div>
                  <button
                    v-if="me?.id === Number(profile.id) && (!me.delegated || me.rights?.includes('profile'))"
                    class="end"
                    @click="editProfile"
                  >
                    Bearbeiten</button
                  ><button
                    v-else-if="me && !me.delegated"
                    class="end"
                    @click="friend(profile.id, 'request')"
                  >
                    Freundschaft anfragen
                  </button>
                </div>
                <p v-if="profile.restricted" class="notice">
                  Dieses Profil ist privat.
                </p>
                <template v-else
                  ><RichText :text="profile.bio || ''" />
                  <p class="muted small">
                    {{ profile.friend_count }} Freunde
                  </p></template
                >
              </div>
              <div
                v-if="
                  !['post', 'company', 'bookmarks'].includes(page) &&
                  settings.modules[activeModule]
                "
                class="composer panel"
              >
                <button v-if="me && (!me.delegated || me.rights?.includes('posts'))" class="composer-trigger" @click="compose()">
                  <Icon :name="activeModule" />{{
                    page === "profile"
                      ? "Auf die Pinnwand schreiben"
                      : activeModule === "market"
                        ? "Anzeige erstellen"
                        : activeModule === "video"
                          ? "Video hochladen"
                          : activeModule === "gram"
                            ? "Foto veröffentlichen"
                            : "Was gibt es Neues?"
                  }}
                </button>
                <p v-else class="muted">Öffentliche Beiträge</p>
              </div>
              <CategoryFilter
                v-if="['social', 'gram', 'video'].includes(page)"
                v-model="category"
                :categories="settings.post_categories || []"
                @change="load()"
              />
              <div v-if="page === 'social'" class="tabs">
                <button
                  :class="{ active: filter === 'all' }"
                  @click="
                    filter = 'all';
                    load();
                  "
                >
                  Alle Beiträge</button
                ><button v-if="me"
                  :class="{ active: filter === 'friends' }"
                  @click="
                    filter = 'friends';
                    load();
                  "
                >
                  Freunde
                </button>
              </div>
              <div
                v-if="['gram', 'market', 'video'].includes(page)"
                class="row between"
              >
                <p class="muted">
                  {{ posts.length }}
                  {{ page === "market" ? "Anzeigen" : "Beiträge" }}
                </p>
                <button class="quiet" @click="usePhotoGrid = !usePhotoGrid">
                  {{ usePhotoGrid ? "Detailansicht" : "Rasteransicht" }}
                </button>
              </div>
              <div
                v-if="
                  ['gram', 'market', 'video'].includes(page) && usePhotoGrid
                "
                class="media-grid"
                :class="page"
              >
                <button
                  v-for="p in posts"
                  :key="p.id"
                  class="media-tile"
                  @click="go('post/' + p.id)"
                >
                  <img
                    v-if="p.media[0]?.mime.startsWith('image/')"
                    :src="mediaUrl(p.media[0].id)"
                    :alt="p.title || 'Foto'"
                    loading="lazy"
                  />
                  <div v-else class="video-placeholder">
                    <Icon name="video" /><span>{{
                      p.media[0]?.state === "ready"
                        ? "Video ansehen"
                        : stateName(p.media[0]?.state)
                    }}</span>
                  </div>
                  <div class="tile-caption">
                    <small v-if="Number(p.ai_generated)" class="badge"
                      >KI-generiert</small
                    ><small v-if="p.category">{{ p.category }}</small>
                    <strong>{{ p.title || p.author.display_name }}</strong
                    ><span v-if="page === 'market'"
                      >{{ amount(p.price) }} RP-$ ·
                      {{
                        p.sale_state === "reserved"
                          ? "Reserviert"
                          : p.sale_state === "sold"
                            ? "Verkauft"
                            : "Verfügbar"
                      }}</span
                    ><small v-else
                      >{{ p.likes }} Likes · {{ p.comments }} Kommentare</small
                    ><span v-if="p.state !== 'published'" class="badge">{{
                      stateName(p.state)
                    }}</span>
                  </div>
                </button>
              </div>
              <AdCard
                v-for="a in currentAds.filter(
                  (a) => a.placement === 'pinned' && page === 'social',
                )"
                :key="a.id"
                :ad="a"
                :now="clock"
              />
              <div
                v-if="
                  !(['gram', 'market', 'video'].includes(page) && usePhotoGrid)
                "
                class="post-list"
              >
                <PostCard
                  v-for="p in posts"
                  :key="p.id"
                  :post="p"
                  :me="me"
                  @refresh="load()"
                  @error="showError"
                  @edit="compose"
                  @contact="go('messages/' + $event)"
                  @profile="go('profile/' + $event)"
                  @company="go('company/' + $event)"
                  @open="go('post/' + $event)"
                  @share="share"
                />
              </div>
              <div v-if="!posts.length && !busy" class="empty">
                <Icon :name="activeModule" />
                <h2>
                  {{
                    settings.modules[activeModule]
                      ? "Hier ist noch Platz für Neues."
                      : "Dieses Modul ist deaktiviert."
                  }}
                </h2>
                <p v-if="settings.modules[activeModule]">
                  Sichtbare Beiträge erscheinen hier.
                </p>
              </div>
              <button
                v-if="next && page !== 'post'"
                @click="load(true)"
                :disabled="busy"
              >
                Weitere laden
              </button>
            </template>
            <section v-if="page === 'search'" class="panel">
              <form @submit.prevent="load()">
                <label
                  >Person suchen<input
                    v-model="search"
                    placeholder="Name oder Benutzername" /></label
                ><button class="primary">Suchen</button>
              </form>
              <div v-for="p in people" :key="p.id" class="person-row">
                <span class="avatar">{{ p.display_name.slice(0, 2) }}</span>
                <div>
                  <strong>{{ p.display_name }}</strong
                  ><small>@{{ p.handle }}</small>
                </div>
                <button class="end" @click="go('profile/' + p.id)">
                  Profil
                </button>
              </div>
              <h3 v-if="searchCompanies.length">Unternehmen</h3>
              <article
                v-for="c in searchCompanies"
                :key="c.id"
                class="list-item"
              >
                <button class="text-button" @click="go('company/' + c.id)">
                  <strong
                    >{{ c.name }}<VerifiedIcon v-if="Number(c.verified)"
                  /></strong>
                </button>
                <p>{{ c.description }}</p>
              </article>
              <h3 v-if="searchPosts.length">Beiträge & Anzeigen</h3>
              <article v-for="p in searchPosts" :key="p.id" class="list-item">
                <button class="text-button" @click="go('post/' + p.id)">
                  <strong>{{ p.title || p.author.display_name }}</strong>
                </button>
                <p>{{ p.body.slice(0, 160) }}</p>
              </article>
            </section>
            <section v-if="page === 'friends'" class="panel">
              <div class="row between">
                <h2>Freunde & Anfragen</h2>
                <button @click="go('search')">Person finden</button>
              </div>
              <p v-if="!friendItems.length" class="muted">
                Noch keine Freundschaften oder Anfragen.
              </p>
              <article
                v-for="f in friendItems"
                :key="f.profile.id"
                class="person-row"
              >
                <div>
                  <button
                    class="text-button"
                    @click="go('profile/' + f.profile.id)"
                  >
                    <strong>{{ f.profile.display_name }}</strong></button
                  ><small>{{
                    f.status === "accepted"
                      ? "Freunde"
                      : Number(f.sender) === me?.id
                        ? "Anfrage gesendet"
                        : "Neue Anfrage"
                  }}</small>
                </div>
                <button
                  v-if="
                    f.status === 'pending' && Number(f.recipient) === me?.id
                  "
                  @click="friend(f.profile.id, 'accept')"
                >
                  Annehmen</button
                ><button @click="friend(f.profile.id, 'remove')">
                  {{
                    f.status === "accepted"
                      ? "Entfernen"
                      : "Ablehnen / zurücknehmen"
                  }}</button
                ><button
                  v-if="settings.modules.messages"
                  class="icon-button"
                  aria-label="Nachricht schreiben"
                  @click="go('messages/' + f.profile.id)"
                >
                  <Icon name="messages" /></button
                ><button @click="block(f.profile.id)">Blockieren</button>
              </article>
              <h3>Blockierte Profile</h3>
              <article v-for="p in blocked" :key="p.id" class="person-row">
                {{ p.display_name
                }}<button @click="block(p.id, true)">Entsperren</button>
              </article>
            </section>
            <section v-if="page === 'messages'" class="panel">
              <template v-if="!settings.modules.messages"
                ><p>Nachrichten sind deaktiviert.</p></template
              ><template v-else
                ><div class="row between">
                  <h2>{{ routeId ? profile?.display_name : "Posteingang" }}</h2>
                  <button
                    @click="
                      recipients = [];
                      messageBody = '';
                      selectedMedia = [];
                      open('message');
                    "
                  >
                    Neue Nachricht
                  </button>
                </div>
                <template v-if="!routeId"
                  ><article
                    v-for="t in threads"
                    :key="t.profile.id"
                    class="thread"
                    @click="go('messages/' + t.profile.id)"
                  >
                    <button class="text-button">
                      <strong>{{ t.profile.display_name }}</strong>
                    </button>
                    <p>{{ t.last.body.slice(0, 120) }}</p>
                    <small>{{ date(t.last.created_at) }}</small>
                  </article>
                  <p v-if="!threads.length" class="empty">
                    Dein Posteingang ist leer.
                  </p></template
                ><template v-else
                  ><button v-if="messages.length >= 100" @click="olderMessages">
                    Ältere Nachrichten laden
                  </button>
                  <div class="conversation">
                    <article
                      v-for="m in messages"
                      :key="m.id"
                      class="bubble"
                      :class="{ mine: Number(m.sender) === me?.id }"
                    >
                      <RichText :text="m.body" />
                      <div v-for="a in m.media" :key="a.id">
                        <img
                          v-if="a.mime.startsWith('image/')"
                          :src="mediaUrl(a.id)"
                          alt="Bildanhang"
                        /><a
                          v-else
                          :href="mediaUrl(a.id)"
                          target="_blank"
                          rel="noopener"
                          >{{ a.filename }}</a
                        >
                      </div>
                      <small
                        >{{ date(m.created_at) }}
                        {{ m.read_at ? "· Gelesen" : "" }}</small
                      ><button
                        class="quiet small"
                        @click="
                          open('report', {
                            kind: 'message',
                            id: m.id,
                            reason: '',
                          })
                        "
                      >
                        Melden
                      </button>
                    </article>
                  </div>
                  <form @submit.prevent="sendMessage">
                    <div class="format-tools">
                      <button type="button" @click="formatMessage('**')">
                        <strong>B</strong></button
                      ><button type="button" @click="formatMessage('*')">
                        <em>I</em></button
                      ><button type="button" @click="messageBody += '\n• '">
                        Liste</button
                      ><button type="button" @click="messageBody += ' 😊'">
                        😊
                      </button>
                    </div>
                    <label
                      >Nachricht<textarea
                        id="message-editor"
                        v-model="messageBody"
                        rows="4"
                        required
                        maxlength="12000"
                      ></textarea></label
                    ><label class="file-button"
                      >Anhang hinzufügen<input
                        type="file"
                        multiple
                        @change="files($event, 'message', 'messages')" /></label
                    ><label class="check"
                      ><input type="checkbox" v-model="withSignature" />Meine
                      Signatur hinzufügen</label
                    >
                    <div class="attachments">
                      <span v-for="f in selectedMedia" :key="f.id"
                        >{{ f.filename || "Anhang" }} #{{ f.id }}</span
                      >
                    </div>
                    <button class="primary" :disabled="busy || uploading">
                      Senden
                    </button>
                  </form></template
                ></template
              >
            </section>
            <section v-if="page === 'notifications'" class="panel">
              <h2>Mitteilungen</h2>
              <button
                v-if="notifications.length"
                @click="
                  run(async () => {
                    await api('read_notifications', {
                      through: notifications[0].id,
                    });
                    notifications.forEach((n) => (n.read_at = 'now'));
                  })
                "
              >
                Alle als gelesen markieren
              </button>
              <article
                v-for="n in notifications"
                :key="n.id"
                class="notification"
                :class="{ unread: !n.read_at }"
              >
                <RichText :text="n.body" /><small>{{
                  date(n.created_at)
                }}</small
                ><button
                  v-if="n.target"
                  class="text-button"
                  @click="go(n.target)"
                >
                  Öffnen →
                </button>
              </article>
              <p v-if="!notifications.length" class="empty">
                Du bist auf dem neuesten Stand.
              </p>
            </section>
            <section v-if="page === 'account'" class="panel">
              <h2>Profil & Privatsphäre</h2>
              <p>
                Verwalte dein Erscheinungsbild, deine Signatur und wer dich
                erreichen darf.
              </p>
              <button class="primary" @click="editProfile">
                Profil bearbeiten</button
              ><label
                >Darstellung<select v-model="theme">
                  <option value="system">Systemeinstellung</option>
                  <option value="light">Hell</option>
                  <option value="dark">Dunkel</option>
                </select></label
              >
              <hr />
              <h3>Zugang</h3>
              <div class="row">
                <button
                  @click="open('password', { current: '', password: '' })"
                >
                  Passwort ändern</button
                ><button
                  class="danger"
                  @click="open('delete_account', { password: '', confirm: '' })"
                >
                  Konto löschen
                </button>
              </div>
            </section>
            <section v-if="page === 'ads'" class="panel">
              <div class="row between">
                <h2>Deine Werbung</h2>
                <button
                  class="primary"
                  :disabled="!adCompanies.length || !slots.length"
                  @click="requestAd"
                >
                  Werbung anfragen
                </button>
              </div>
              <p class="muted">
                Wähle einen freien Werbeplatz und reiche deine Anzeige zur
                Prüfung ein.
              </p>
              <p v-if="!adCompanies.length" class="notice">
                Die Betreiberfirma kann dich als Mitarbeiter einer
                Unternehmensseite hinterlegen.
              </p>
              <h3>Verfügbare Plätze</h3>
              <article v-for="s in slots" :key="s.id" class="list-item">
                <strong>{{ s.name }}</strong
                ><small>{{ date(s.starts_at) }} – {{ date(s.ends_at) }}</small>
                <p>{{ s.conditions }}</p>
                <span>{{ amount(s.price) }} RP-$</span>
              </article>
              <h3>Anfragen</h3>
              <article v-for="a in ads" :key="a.id" class="list-item">
                <strong>{{ a.title }}</strong
                ><span class="badge"
                  >{{ stateName(a.state) }}
                  {{
                    a.state === "accepted"
                      ? Number(a.paid)
                        ? "· Bezahlt"
                        : "· Zahlung offen"
                      : ""
                  }}</span
                ><small>{{ date(a.starts_at) }} – {{ date(a.ends_at) }}</small>
                <p>{{ a.decision }}</p>
              </article>
            </section>
            <section v-if="page === 'admin'" class="admin-content">
              <div class="panel row between">
                <h2>Betreiberverwaltung</h2>
                <button @click="editSettings">
                  {{
                    canAdmin
                      ? "Community konfigurieren"
                      : "Infoblöcke & Vorlagen"
                  }}</button
                ><button @click="load()">Aktualisieren</button>
              </div>
              <template v-if="canModerate"
                ><section class="panel">
                  <h2>Konten & Freigaben</h2>
                  <article
                    v-for="p in admin.profiles || []"
                    :key="p.id"
                    class="list-item"
                  >
                    <div class="row between">
                      <div>
                        <strong>{{ p.display_name }}</strong
                        ><small
                          >@{{ p.handle }} · {{ stateName(p.status) }} ·
                          {{ stateName(p.role) }}</small
                        >
                      </div>
                      <div class="row">
                        <button
                          v-if="p.status !== 'active'"
                          @click="moderate('profile', p.id, 'active')"
                        >
                          Freigeben</button
                        ><button
                          v-if="p.status === 'pending'"
                          @click="moderate('profile', p.id, 'rejected')"
                        >
                          Ablehnen</button
                        ><button
                          v-if="
                            p.status === 'active' && Number(p.id) !== me?.id
                          "
                          @click="moderate('profile', p.id, 'suspended')"
                        >
                          Sperren</button
                        ><select
                          v-if="canAdmin && Number(p.id) !== me?.id"
                          :value="p.role"
                          aria-label="Rolle ändern"
                          @change="
                            moderate(
                              'role',
                              p.id,
                              ($event.target as HTMLSelectElement).value,
                            )
                          "
                        >
                          <option value="member">Benutzer</option>
                          <option value="moderator">Moderation</option>
                          <option value="advertiser">Werbung</option>
                          <option value="admin">
                            Technische Administration
                          </option>
                        </select>
                      </div>
                    </div>
                  </article>
                </section>
                <section class="panel">
                  <h2>Videoprüfung</h2>
                  <p v-if="!admin.videos?.length" class="muted">
                    Keine Videos zur Prüfung.
                  </p>
                  <article
                    v-for="v in admin.videos || []"
                    :key="v.id"
                    class="review-item"
                  >
                    <h3>{{ v.title }}</h3>
                    <RichText :text="v.body" /><template
                      v-for="m in v.media"
                      :key="m.id"
                      ><video
                        v-if="m.state === 'ready'"
                        :src="mediaUrl(m.id)"
                        controls
                        preload="metadata"
                      ></video>
                      <p v-else class="notice">
                        {{ stateName(m.state) }}
                      </p></template
                    >
                    <div class="row">
                      <button
                        :disabled="
                          v.media.some((m: Row) => m.state !== 'ready')
                        "
                        @click="moderate('video', v.id, 'published')"
                      >
                        Freigeben</button
                      ><button @click="moderate('video', v.id, 'rejected')">
                        Ablehnen
                      </button>
                    </div>
                  </article>
                </section>
                <section class="panel">
                  <h2>Meldungen & Einsprüche</h2>
                  <p v-if="!admin.reports?.length" class="muted">
                    Keine offenen Meldungen.
                  </p>
                  <article
                    v-for="r in admin.reports || []"
                    :key="r.id"
                    class="review-item"
                  >
                    <span class="badge">{{ r.kind }}</span>
                    <p>{{ r.reason }}</p>
                    <RichText v-if="r.content?.body" :text="r.content.body" />
                    <div v-for="m in r.media || []" :key="m.id">
                      <a
                        :href="mediaUrl(m.id)"
                        target="_blank"
                        rel="noopener"
                        >{{ m.filename }}</a
                      >
                    </div>
                    <div class="row">
                      <button
                        v-if="r.kind === 'post' && r.content"
                        @click="moderate('post', r.target_id, 'hidden')"
                      >
                        Beitrag ausblenden</button
                      ><button @click="moderate('report', r.id, 'resolved')">
                        Mit Begründung abschließen
                      </button>
                    </div>
                  </article>
                </section>
                <section class="panel">
                  <div class="row between">
                    <h2>Unternehmensseiten</h2>
                    <button
                      @click="
                        open('company', {
                          name: '',
                          description: '',
                          members: [],
                        })
                      "
                    >
                      Unternehmen anlegen
                    </button>
                  </div>
                  <article v-for="c in companies" :key="c.id" class="list-item">
                    <button class="text-button" @click="go('company/' + c.id)">
                      <strong
                        >{{ c.name }}<VerifiedIcon v-if="Number(c.verified)"
                      /></strong>
                    </button>
                    <p>{{ c.description }}</p>
                    <small
                      >Mitarbeiter lassen sich über die Namenssuche zuordnen
                      (siehe Kontenliste).</small
                    ><button
                      @click="
                        open('company', {
                          ...c,
                          members: (c.members || []).map((p: Row) =>
                            Number(p.id),
                          ),
                        })
                      "
                    >
                      Mitarbeiter & Seite bearbeiten
                    </button>
                  </article>
                </section></template
              >
              <template v-if="canAds"
                ><section class="panel">
                  <div class="row between">
                    <h2>Werbeplätze</h2>
                    <button
                      @click="
                        open('slot', {
                          name: '',
                          placement: 'sidebar',
                          price: 0,
                          conditions: '',
                          active: true,
                        })
                      "
                    >
                      Platz freigeben
                    </button>
                  </div>
                  <article
                    v-for="s in admin.slots || []"
                    :key="s.id"
                    class="list-item"
                  >
                    <strong>{{ s.name }}</strong
                    ><small
                      >{{ s.placement }} · {{ date(s.starts_at) }} –
                      {{ date(s.ends_at) }}</small
                    ><span>{{ amount(s.price) }} RP-$</span>
                  </article>
                </section>
                <section class="panel">
                  <h2>Werbeanfragen</h2>
                  <article
                    v-for="a in admin.ads || []"
                    :key="a.id"
                    class="review-item"
                  >
                    <strong>{{ a.title }} · {{ a.company_name }}</strong
                    ><span class="badge">{{ stateName(a.state) }}</span
                    ><small
                      >{{ date(a.starts_at) }} – {{ date(a.ends_at) }} ·
                      {{ a.slot_name }}</small
                    ><RichText :text="a.body" /><img
                      v-for="m in a.media"
                      :key="m.id"
                      :src="mediaUrl(m.id)"
                      alt="Werbevorschau"
                    />
                    <p>{{ a.target }}</p>
                    <div class="row">
                      <template v-if="a.state === 'pending'"
                        ><button @click="decideAd(a, 'accepted')">
                          Annehmen</button
                        ><button @click="decideAd(a, 'rejected')">
                          Ablehnen
                        </button></template
                      ><button
                        v-if="a.state === 'accepted' && !Number(a.paid)"
                        @click="decideAd(a, 'paid')"
                      >
                        Zahlung bestätigen: {{ amount(a.amount) }} RP-$
                      </button>
                    </div>
                  </article>
                </section></template
              >
              <section v-if="canAdmin" class="panel">
                <h2>Module & Daten</h2>
                <p>
                  Deaktivierte Module behalten ihre Daten, bis du sie
                  ausdrücklich löschst.
                </p>
                <div
                  v-for="(enabled, m) in settings.modules"
                  :key="m"
                  class="row list-item"
                >
                  <strong>{{ settings.names[m] || m }}</strong
                  ><span>{{ enabled ? "Aktiv" : "Deaktiviert" }}</span
                  ><button
                    v-if="!enabled"
                    class="danger"
                    @click="purge(String(m))"
                  >
                    Moduldaten löschen
                  </button>
                </div>
                <h3>Änderungsprotokoll</h3>
                <article
                  v-for="entry in admin.audit || []"
                  :key="entry.id"
                  class="audit"
                >
                  <small
                    >{{ date(entry.created_at) }} · Profil {{ entry.actor }} ·
                    {{ entry.action }}</small
                  ><code>{{ entry.details }}</code>
                </article>
              </section>
            </section>
          </main>
          <aside class="right-rail">
            <section v-if="!me" class="side-box guest-invitation">
              <div class="side-heading"><Icon name="user" /><h3>Mach mit</h3></div>
              <p>Lies mit, was in deiner Stadt passiert. Melde dich an, um Beiträge zu posten, zu kommentieren und dich mit anderen auszutauschen.</p>
              <button class="primary" @click="beginAuth()">Anmelden</button>
              <button class="text-button" @click="beginAuth('register')">Konto erstellen</button>
            </section>
            <section
              v-if="settings.modules.video && discovery.video"
              class="side-box latest-video"
            >
              <div class="side-heading">
                <Icon name="video" />
                <h3>Neuestes Video</h3>
              </div>
              <template v-if="discovery.video"
                ><button
                  class="video-teaser"
                  @click="go('post/' + discovery.video.id)"
                >
                  <video
                    :src="mediaUrl(discovery.video.media[0].id) + '#t=0.1'"
                    preload="metadata"
                    muted
                    playsinline
                  ></video
                  ><span class="play-badge">▶</span></button
                ><button
                  class="text-button video-name"
                  @click="go('post/' + discovery.video.id)"
                >
                  <strong>{{ discovery.video.title }}</strong>
                </button>
                <p class="small muted">
                  {{ discovery.video.author.display_name }} ·
                  {{ discovery.video.likes }} Likes
                </p></template
              >
              <p v-else class="small muted">
                Hier erscheint das neueste freigegebene Video.
              </p>
            </section>
            <AdCard
              v-for="a in currentAds.filter((a) => a.placement === 'sidebar')"
              :key="a.id"
              :ad="a"
              :now="clock"
            />
            <section
              class="side-box advertising-box"
              v-if="settings.modules.social"
            >
              <span class="eyebrow">Werbung</span>
              <h3>Dein Unternehmen.<br />Hier im Blick.</h3>
              <p class="small muted">
                Erreiche die Stadt mit deiner eigenen Kampagne.
              </p>
              <button v-if="me" @click="go('ads')">
                Werbeplatz anfragen <span>↗</span>
              </button>
            </section>
          </aside>
        </div>
      </div></template
    >
    <div v-if="dialog" class="modal-backdrop" @click.self="dialog = ''">
      <section
        class="modal"
        role="dialog"
        aria-modal="true"
        :aria-label="
          (
            {
              post: 'Beitrag verfassen',
              profile: 'Profil bearbeiten',
              settings: 'Community konfigurieren',
              message: 'Neue Nachricht',
              ad: 'Werbung anfragen',
              purge: 'Moduldaten löschen',
            } as Row
          )[dialog] || 'Bearbeiten'
        "
      >
        <button
          class="icon-button modal-close"
          aria-label="Dialog schließen"
          @click="dialog = ''"
        >
          <Icon name="close" />
        </button>
        <form v-if="dialog === 'post'" @submit.prevent="savePost">
          <h2>
            {{
              form.id
                ? "Beitrag bearbeiten"
                : form.module === "market"
                  ? "Anzeige erstellen"
                  : form.module === "video"
                    ? "Video einreichen"
                    : "Beitrag verfassen"
            }}
          </h2>
          <label v-if="form.module !== 'social'"
            >Titel<input
              v-model="form.title"
              maxlength="160"
              :required="['video', 'market'].includes(form.module)" /></label
          ><label v-if="mineCompanies.length && !form.id"
            >Veröffentlichen als<select v-model="form.company_id">
              <option :value="null">{{ me?.display_name }}</option>
              <option v-for="c in mineCompanies" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select></label
          ><label
            >Text<textarea
              id="post-editor"
              @select="inspectPostFormat"
              @keyup="inspectPostFormat"
              @click="inspectPostFormat"
              @input="inspectPostFormat"
              v-model="form.body"
              rows="5"
              maxlength="12000"
              placeholder="Was möchtest du teilen?"
            ></textarea>
          </label>
          <div
            class="format-tools"
            role="group"
            aria-label="Beitrag formatieren"
          >
            <button
              type="button"
              @mousedown.prevent
              :aria-pressed="activeFormats.includes('**')"
              @click="formatPost('**')"
              aria-label="Fett"
            >
              <strong>B</strong>
            </button>
            <button
              type="button"
              @mousedown.prevent
              :aria-pressed="activeFormats.includes('*')"
              @click="formatPost('*')"
              aria-label="Kursiv"
            >
              <em>I</em>
            </button>
            <button
              type="button"
              @mousedown.prevent
              :aria-pressed="activeFormats.includes('~~')"
              @click="formatPost('~~')"
              aria-label="Durchgestrichen"
            >
              <s>S</s>
            </button>
            <button
              type="button"
              @mousedown.prevent
              :aria-pressed="activeFormats.includes('• ')"
              @click="formatPost('• ')"
            >
              Liste
            </button>
          </div>
          <details v-if="form.body" class="post-preview">
            <summary>Formatierte Vorschau</summary>
            <RichText :text="form.body" />
          </details>
          <label class="check"
            ><input type="checkbox" v-model="form.ai_generated" />Als
            KI-generiert kennzeichnen</label
          >
          <div class="form-grid">
            <label v-if="form.module !== 'market'"
              >Kategorie<select
                v-model="form.category"
                aria-label="Kategorie"
                required
              >
                <option
                  v-if="
                    form.category &&
                    !settings.post_categories.includes(form.category)
                  "
                  :value="form.category"
                >
                  {{ form.category }} (bisherige Kategorie)
                </option>
                <option v-for="c in settings.post_categories" :key="c">
                  {{ c }}
                </option>
              </select></label
            >
            <label
              >Sichtbarkeit<select
                v-model="form.visibility"
                aria-label="Sichtbarkeit"
              >
                <option value="public">Öffentlich</option>
                <option value="friends">Freunde</option>
                <option value="friends_of_friends">Freunde von Freunden</option>
              </select></label
            ><template v-if="form.module === 'market'"
              ><label
                >Preis in RP-$<input
                  v-model="form.price"
                  type="number"
                  min="0"
                  step="0.01"
                  required /></label
              ><label
                >Kategorie<select
                  v-model="form.category"
                  aria-label="Kategorie"
                >
                  <option>Fahrzeuge</option>
                  <option>Immobilien</option>
                  <option>Elektronik</option>
                  <option>Sonstiges</option>
                </select></label
              ><label
                >Status<select v-model="form.sale_state">
                  <option value="available">Verfügbar</option>
                  <option value="reserved">Reserviert</option>
                  <option value="sold">Verkauft</option>
                </select></label
              ></template
            >
          </div>
          <label v-if="!form.shared_id" class="file-button"
            >{{
              form.module === "video"
                ? "Videodatei wählen"
                : "Bilder hinzufügen"
            }}<input
              type="file"
              :multiple="form.module !== 'video'"
              :accept="
                form.module === 'video'
                  ? 'video/mp4,video/webm,video/quicktime'
                  : 'image/png,image/jpeg,image/webp'
              "
              @change="files($event)"
          /></label>
          <div class="attachment-grid">
            <div v-for="f in selectedMedia" :key="f.id">
              <img
                v-if="f.mime.startsWith('image/')"
                :src="mediaUrl(f.id)"
                alt="Uploadvorschau"
              /><span v-else>{{ stateName(f.state) }} · Video</span
              ><button
                type="button"
                @click="
                  selectedMedia = selectedMedia.filter((m) => m.id !== f.id)
                "
              >
                Entfernen
              </button>
            </div>
          </div>
          <p v-if="form.module === 'video'" class="notice">
            Nach der Verarbeitung prüft die Betreiberfirma dein Video. Es
            erscheint erst nach Freigabe.
          </p>
          <div class="row">
            <button class="primary" :disabled="busy || uploading">
              {{
                uploading
                  ? "Lädt hoch …"
                  : form.module === "video"
                    ? "Zur Prüfung einreichen"
                    : "Speichern"
              }}</button
            ><button
              v-if="form.id"
              type="button"
              class="danger"
              @click="removePost"
            >
              Löschen
            </button>
          </div>
        </form>
        <form v-if="dialog === 'profile'" @submit.prevent="saveProfile">
          <h2>Profil & Privatsphäre</h2>
          <div class="form-grid">
            <label
              >Anzeigename<input
                v-model="form.display_name"
                required
                maxlength="100" /></label
            ><label v-if="!me?.delegated"
              >Profil sichtbar für<select v-model="form.privacy">
                <option value="public">Alle</option>
                <option value="friends">Freunde</option>
                <option value="friends_of_friends">Freunde von Freunden</option>
              </select></label
            >
          </div>
          <label
            >Beschreibung<textarea
              v-model="form.bio"
              rows="3"
              maxlength="2000"
            ></textarea>
          </label>
          <div class="form-grid">
            <label
              >Avatar<input
                type="file"
                name="avatar_id"
                accept="image/png,image/jpeg,image/webp"
                @change="files($event, 'profile', 'social')" /><img
                v-if="form.avatar_id"
                class="preview-avatar"
                :src="mediaUrl(form.avatar_id)"
                alt="Avatarvorschau" /></label
            ><label
              >Headerbild<input
                type="file"
                name="cover_id"
                accept="image/png,image/jpeg,image/webp"
                @change="files($event, 'profile', 'social')" /><img
                v-if="form.cover_id"
                :src="mediaUrl(form.cover_id)"
                alt="Header-Vorschau"
            /></label>
          </div>
          <template v-if="!me?.delegated"><label
            >Persönliche Signatur<textarea
              v-model="form.signature"
              rows="3"
              maxlength="2000"
            ></textarea>
          </label>
          <div class="form-grid">
            <label
              v-for="(label, key) in {
                messages: 'Wer darf Nachrichten senden?',
                requests: 'Wer darf Freundschaft anfragen?',
                wall: 'Wer darf auf die Pinnwand schreiben?',
                default_visibility: 'Sichtbarkeit neuer Beiträge',
              }"
              :key="key"
              >{{ label
              }}<select v-model="form[key]">
                <option value="public">Alle</option>
                <option value="friends">Freunde</option>
                <option value="friends_of_friends">Freunde von Freunden</option>
                <option v-if="key !== 'default_visibility'" value="nobody">
                  Niemand
                </option>
              </select></label
            >
          </div>
          <label class="check"
            ><input type="checkbox" v-model="form.receipts" />Lesebestätigungen
            anzeigen</label
          ><label class="check"
            ><input type="checkbox" v-model="form.online" />Online-Status
            anzeigen</label
          ><label class="check"
            ><input
              type="checkbox"
              v-model="form.notifications"
            />Benachrichtigungen zu sozialen Aktivitäten</label
          ></template><button class="primary" :disabled="busy || uploading">
            Speichern
          </button>
        </form>
        <form v-if="dialog === 'settings'" @submit.prevent="saveSettings">
          <h2>
            {{ canAdmin ? "Community-Einstellungen" : "Inhalte & Vorlagen" }}
          </h2>
          <label v-if="canAdmin"
            >Beitragskategorien (eine pro Zeile)<textarea
              :value="(form.post_categories || []).join('\n')"
              @input="
                form.post_categories = (
                  $event.target as HTMLTextAreaElement
                ).value.split('\n')
              "
              rows="6"
            ></textarea>
          </label>
          <ThemeColors v-if="canAdmin" v-model="form.theme_colors" />
          <template v-if="canAdmin"
            ><div class="form-grid">
              <label
                >Community-Name<input
                  v-model="form.community"
                  required
                  maxlength="100" /></label
              ><label
                >Name des Social-Media-Unternehmens<input
                  v-model="form.operator_name"
                  maxlength="100"
                  placeholder="Name der Betreiberfirma" /></label
              ><label
                >Akzentfarbe<input type="color" v-model="form.accent" /></label
              ><label
                >Icon-Stil<select v-model="form.icon_set">
                  <option value="coastal">Los Santos</option>
                  <option value="pacific">Pacific</option>
                  <option value="night">After Hours</option>
                  <option value="signs">City Signs</option>
                  <option value="studio">Studio</option>
                </select></label
              ><label
                >Startbildschirm<input
                  name="background_id"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  @change="files($event, 'branding', 'social')"
              /></label>
            </div>
            <div class="form-grid">
              <label
                >Community-Logo<input
                  name="logo_id"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  @change="files($event, 'branding', 'social')" /></label
              ><label
                >Community-Header<input
                  name="header_id"
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  @change="files($event, 'branding', 'social')"
              /></label>
            </div>
            <h3>Plattformen</h3>
            <div
              v-for="m in ['social', 'gram', 'market', 'video', 'companies', 'laws']"
              :key="m"
              class="module-setting"
            >
              <label v-if="!['companies', 'laws'].includes(m)" class="check"
                ><input type="checkbox" v-model="form.modules[m]" />Aktiv</label
              ><label
                >Anzeigename<input
                  v-model="form.names[m]"
                  maxlength="40"
                  required /></label
              ><label
                >Eigenes Icon<input
                  type="file"
                  :name="m"
                  accept="image/png,image/jpeg,image/webp"
                  @change="files($event, 'branding', 'social')" /></label
              ><button
                v-if="form.icons[m]"
                type="button"
                @click="delete form.icons[m]"
              >
                Standard
              </button>
            </div>
            <label class="check"
              ><input
                type="checkbox"
                v-model="form.modules.messages"
              />Direktnachrichten aktiviert</label
            ><label class="check"
              ><input type="checkbox" v-model="form.guest" />Öffentliche Inhalte
              ohne Konto lesen</label
            ><label
              >Registrierung<select v-model="form.registration">
                <option value="manual">Prüfung durch Betreiberfirma</option>
                <option value="immediate">Sofort freigeben</option>
              </select></label
            ><label
              >Hinweis für wartende Benutzer<textarea
                v-model="form.registration_hint"
                rows="3"
              ></textarea>
            </label>
            <div class="form-grid">
              <label
                v-for="(label, key) in {
                  upload_mb: 'Bild/Anhang-Limit (MB)',
                  video_mb: 'Video-Limit (MB)',
                  video_seconds: 'Maximale Videolänge (Sekunden)',
                  quota_mb: 'Speicher je Nutzer (MB)',
                }"
                :key="key"
                >{{ label
                }}<input
                  v-model.number="form[key]"
                  type="number"
                  min="1"
                  required
              /></label></div
          ></template>
          <h3>Informationsblöcke</h3>
          <div v-for="(block, i) in form.info_blocks || []" :key="i">
            <label
              >Titel<input
                v-model="block.title"
                maxlength="80"
                required /></label
            ><label
              >Text<textarea
                v-model="block.body"
                maxlength="1500"
                required
              ></textarea></label
            ><button type="button" @click="form.info_blocks.splice(i, 1)">
              Entfernen</button
            ><button
              v-if="i > 0"
              type="button"
              @click="
                [form.info_blocks[i - 1], form.info_blocks[i]] = [
                  form.info_blocks[i],
                  form.info_blocks[i - 1],
                ]
              "
            >
              Nach oben
            </button>
          </div>
          <button
            type="button"
            @click="
              form.info_blocks = [
                ...(form.info_blocks || []),
                { title: '', body: '' },
              ]
            "
          >
            Infoblock hinzufügen
          </button>
          <h3>Community-Links</h3>
          <div v-for="(l, i) in form.links" :key="i" class="form-grid">
            <label
              >Bezeichnung<input
                v-model="l.label"
                required
                maxlength="60" /></label
            ><label>Adresse<input v-model="l.url" type="url" required /></label
            ><button type="button" @click="form.links.splice(i, 1)">
              Entfernen
            </button>
          </div>
          <button
            type="button"
            @click="form.links.push({ label: '', url: '' })"
          >
            Link hinzufügen</button
          ><template v-if="canAds"
            ><h3>Interne Nachrichtenvorlagen</h3>
            <p class="small muted">
              Platzhalter: {name}, {title}, {start}, {end}, {amount},
              {instructions}, {reason}
            </p>
            <label
              >Annahme<textarea
                v-model="form.accept_template"
                rows="4"
              ></textarea></label
            ><label
              >Ablehnung<textarea
                v-model="form.reject_template"
                rows="3"
              ></textarea></label
            ><label
              >Zahlungsanweisung<textarea
                v-model="form.payment_instructions"
                rows="2"
              ></textarea></label></template
          ><button class="primary" :disabled="busy || uploading">
            Änderungen speichern
          </button>
        </form>
        <form v-if="dialog === 'message'" @submit.prevent="sendMessage">
          <h2>Nachricht senden</h2>
          <p class="muted">
            Jeder Empfänger erhält eine eigene Nachricht. Antworten bleiben
            privat.
          </p>
          <div class="row">
            <input
              v-model="friendSearch"
              placeholder="Empfänger suchen"
              aria-label="Empfänger suchen"
            /><button type="button" @click="findPeople">Suchen</button>
          </div>
          <label v-for="p in people" :key="p.id" class="check"
            ><input
              type="checkbox"
              :value="Number(p.id)"
              v-model="recipients"
            />{{ p.display_name }} (@{{ p.handle }})</label
          ><label
            >Nachricht<textarea
              id="message-editor"
              v-model="messageBody"
              required
              rows="5"
              maxlength="12000"
            ></textarea>
          </label>
          <div class="format-tools">
            <button type="button" @click="formatMessage('**')">Fett</button
            ><button type="button" @click="formatMessage('*')">Kursiv</button
            ><button type="button" @click="messageBody += '\n• '">Liste</button
            ><button type="button" @click="messageBody += ' 😊'">😊</button>
          </div>
          <label
            >Anhänge<input
              type="file"
              multiple
              @change="files($event, 'message', 'messages')"
          /></label>
          <p>{{ selectedMedia.length }} Anhänge</p>
          <label class="check"
            ><input type="checkbox" v-model="withSignature" />Meine Signatur
            hinzufügen</label
          ><button
            class="primary"
            :disabled="!recipients.length || uploading || busy"
          >
            An {{ recipients.length }} Empfänger senden
          </button>
        </form>
        <form v-if="dialog === 'ad'" @submit.prevent="submitAd">
          <h2>Werbung anfragen</h2>
          <label
            >Unternehmen<select v-model="form.company_id" required>
              <option v-for="c in adCompanies" :key="c.id" :value="c.id">
                {{ c.name }}
              </option>
            </select></label
          ><label
            >Werbeplatz<select v-model="form.slot_id" required>
              <option v-for="s in slots" :key="s.id" :value="s.id">
                {{ s.name }} · {{ amount(s.price) }} RP-$
              </option>
            </select></label
          >
          <div class="form-grid">
            <label
              >Von<input
                v-model="form.starts_at"
                type="datetime-local"
                required /></label
            ><label
              >Bis<input v-model="form.ends_at" type="datetime-local" required
            /></label>
          </div>
          <label
            >Titel<input v-model="form.title" required maxlength="120" /></label
          ><label
            >Text<textarea
              id="post-editor"
              @select="inspectPostFormat"
              @keyup="inspectPostFormat"
              @click="inspectPostFormat"
              @input="inspectPostFormat"
              v-model="form.body"
              rows="3"
              maxlength="3000"
            ></textarea></label
          ><label
            >Zieladresse<input
              v-model="form.target"
              placeholder="https://… oder #/post/…" /></label
          ><label
            >Optionaler Countdown bis<input
              v-model="form.countdown_at"
              type="datetime-local" /></label
          ><label
            >Werbebild<input
              type="file"
              accept="image/png,image/jpeg,image/webp"
              @change="
                selectedMedia = [];
                files($event, 'ad', 'social');
              "
          /></label>
          <div class="ad-preview">
            <small>Vorschau · Werbung</small
            ><img
              v-for="m in selectedMedia"
              :key="m.id"
              :src="mediaUrl(m.id)"
              alt="Werbevorschau"
            />
            <h3>{{ form.title || "Deine Anzeige" }}</h3>
            <p>{{ form.body }}</p>
            <span v-if="form.countdown_at"
              >Countdown bis
              {{ new Date(form.countdown_at).toLocaleString("de-DE") }}</span
            >
          </div>
          <button class="primary" :disabled="busy || uploading">
            Zur Freigabe einreichen
          </button>
        </form>
        <form v-if="dialog === 'slot'" @submit.prevent="saveSlot">
          <h2>Werbeplatz freigeben</h2>
          <label
            >Name<input v-model="form.name" required maxlength="100" /></label
          ><label
            >Platzierung<select v-model="form.placement">
              <option value="header">Header-Banner</option>
              <option value="sidebar">Rechte Spalte</option>
              <option value="pinned">Angepinnt im Feed</option>
            </select></label
          >
          <div class="form-grid">
            <label
              >Von<input
                v-model="form.starts_at"
                type="datetime-local"
                required /></label
            ><label
              >Bis<input
                v-model="form.ends_at"
                type="datetime-local"
                required /></label
            ><label
              >Betrag (RP-$)<input
                v-model="form.price"
                type="number"
                min="0"
                step="0.01"
                required
            /></label>
          </div>
          <label
            >Bedingungen<textarea
              v-model="form.conditions"
              rows="3"
            ></textarea></label
          ><button class="primary" :disabled="busy">Freigeben</button>
        </form>
        <form v-if="dialog === 'decision'" @submit.prevent="submitDecision">
          <h2>
            {{
              form.decision === "accepted"
                ? "Werbung annehmen"
                : "Werbung ablehnen"
            }}
          </h2>
          <label v-if="form.decision === 'accepted'"
            >Betrag in RP-$<input
              v-model="form.amount"
              type="number"
              min="0"
              step="0.01" /></label
          ><label
            >Begründung<textarea
              v-model="form.reason"
              :required="form.decision === 'rejected'"
              rows="2"
            ></textarea></label
          ><label
            >Interne Bestätigung<textarea
              v-model="form.message"
              rows="5"
            ></textarea>
          </label>
          <p class="small muted">
            Platzhalter werden mit den Buchungsdaten ersetzt. Die Nachricht
            bleibt auf der Plattform.
          </p>
          <button class="primary" :disabled="busy">
            Entscheidung speichern & benachrichtigen
          </button>
        </form>
        <form
          v-if="dialog === 'company-details'"
          @submit.prevent="saveCompanyDetails"
        >
          <h2>Unternehmensinformationen</h2>
          <label
            >Beschreibung<textarea
              v-model="form.description"
              maxlength="4000"
            ></textarea></label
          ><label
            >Standort<input
              v-model="form.location"
              maxlength="200"
              placeholder="Straße, Stadtteil oder Postleitzahl" /></label
          ><label>Kontakt<input v-model="form.contact" maxlength="200" /></label
          ><CompanyFields
            :model-value="form"
            @busy="uploading = $event"
          /><button class="primary" :disabled="busy || uploading">
            Speichern
          </button>
        </form>
        <form v-if="dialog === 'company'" @submit.prevent="saveCompany">
          <h2>Unternehmensseite</h2>
          <label
            >Name<input v-model="form.name" required maxlength="100" /></label
          ><label
            >Beschreibung<textarea
              v-model="form.description"
              rows="3"
            ></textarea>
          </label>
          <label
            >Standort<input v-model="form.location" maxlength="200" /></label
          ><label>Kontakt<input v-model="form.contact" maxlength="200" /></label
          ><label class="check"
            ><input
              type="checkbox"
              :checked="Boolean(Number(form.verified))"
              @change="
                form.verified = ($event.target as HTMLInputElement).checked
              "
            />Verifiziertes Unternehmen</label
          >
          <CompanyFields :model-value="form" @busy="uploading = $event" />
          <p>Zugriffe verwaltet der Haupteigner unter „Konten &amp; Zugriffe“.</p>

          <p class="notice">
            Diese Liste ersetzt die bisherigen Seitenberechtigungen.
          </p>
          <button class="primary" :disabled="busy || uploading">
            Speichern
          </button>
        </form>
        <form v-if="dialog === 'purge'" @submit.prevent="confirmPurge">
          <h2>Moduldaten endgültig löschen</h2>
          <p>
            Betroffen: {{ form.posts }} Beiträge,
            {{ form.messages }} Nachrichten und {{ form.files }} Dateien in der
            laufenden Installation.
          </p>
          <p class="notice">
            Die Daten sind nach erneutem Aktivieren nicht wiederherstellbar.
            Geteilte Verweise werden ungültig.
          </p>
          <label
            >Zur Bestätigung „{{ form.confirmation }}“ eingeben<input
              v-model="form.confirm"
              required
              autocomplete="off" /></label
          ><button
            class="danger"
            :disabled="busy || form.confirm !== form.confirmation"
          >
            Endgültig löschen
          </button>
        </form>
        <form
          v-if="dialog === 'report' || dialog === 'appeal'"
          @submit.prevent="
            run(async () => {
              await api(dialog === 'appeal' ? 'appeal' : 'report', form);
              dialog = '';
              error = 'Deine Nachricht wurde an die Moderation übermittelt.';
            })
          "
        >
          <h2>
            {{
              dialog === "appeal" ? "Rückfrage / Einspruch" : "Inhalt melden"
            }}
          </h2>
          <label
            >Begründung<textarea
              v-model="form.reason"
              required
              maxlength="2000"
              rows="5"
            ></textarea></label
          ><button class="primary" :disabled="busy">Absenden</button>
        </form>
        <form
          v-if="dialog === 'password'"
          @submit.prevent="
            run(async () => {
              await api('password', form);
              dialog = '';
              error = 'Passwort geändert.';
            })
          "
        >
          <h2>Passwort ändern</h2>
          <label
            >Aktuelles Passwort<input
              v-model="form.current"
              type="password"
              required
              autocomplete="current-password" /></label
          ><label
            >Neues Passwort<input
              v-model="form.password"
              type="password"
              minlength="12"
              required
              autocomplete="new-password" /></label
          ><button class="primary" :disabled="busy">Speichern</button>
        </form>
        <form
          v-if="dialog === 'delete_account'"
          @submit.prevent="
            run(async () => {
              await api('delete_account', form);
              dialog = '';
              me = null;
              go('apps');
            })
          "
        >
          <h2>Konto löschen</h2>
          <p>
            Dein Profil wird anonymisiert, Beiträge werden entfernt und die
            Anmeldung gesperrt.
          </p>
          <label
            >Passwort<input
              v-model="form.password"
              type="password"
              required
              autocomplete="current-password" /></label
          ><label
            >Zur Bestätigung DELETE eingeben<input
              v-model="form.confirm"
              required /></label
          ><button class="danger" :disabled="busy || form.confirm !== 'DELETE'">
            Konto löschen
          </button>
        </form>
      </section>
    </div>
  </div>
</template>
