export type Row = Record<string, any>;
export const mediaUrl = (id: number | string) =>
  `/api/social/index.php?action=media&id=${encodeURIComponent(id)}`;
export async function api(
  action: string,
  data?: Row,
  query: Row = {},
): Promise<Row> {
  const qs = new URLSearchParams({
    action,
    ...Object.fromEntries(
      Object.entries(query)
        .filter(([, v]) => v !== undefined && v !== null)
        .map(([k, v]) => [k, String(v)]),
    ),
  });
  const response = await fetch(`/api/social/index.php?${qs}`, {
    method: data ? "POST" : "GET",
    credentials: "same-origin",
    headers: data
      ? { "Content-Type": "application/json", "X-Social-Request": "1" }
      : {},
    body: data ? JSON.stringify(data) : undefined,
  });
  const result = await response
    .json()
    .catch(() => ({ error: "Server nicht erreichbar." }));
  if (!response.ok) throw new Error(result.error || "Anfrage fehlgeschlagen.");
  return result;
}
export async function upload(
  file: File,
  module: string,
  purpose = "post",
): Promise<Row> {
  const body = new FormData();
  body.set("file", file);
  body.set("module", module);
  body.set("purpose", purpose);
  const response = await fetch("/api/social/index.php?action=upload", {
    method: "POST",
    credentials: "same-origin",
    headers: { "X-Social-Request": "1" },
    body,
  });
  const result = await response.json();
  if (!response.ok) throw new Error(result.error || "Upload fehlgeschlagen.");
  return result;
}
