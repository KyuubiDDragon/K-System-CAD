import { test, expect } from "@playwright/test";
import { execFileSync } from "node:child_process";
import { resolve } from "node:path";
const password = "Browser-test-password-123";
test("real launcher, configurable labels, theme, post and mobile layout", async ({
  page,
}) => {
  const errors: string[] = [];
  page.on("pageerror", (e) => errors.push(e.message));
  const handle = "browser_" + Date.now().toString(36);
  const registration = await page.request.post(
    "/api/social/index.php?action=register",
    {
      headers: { "X-Social-Request": "1" },
      data: { handle, display_name: "Mia Bennett", password },
    },
  );
  expect(registration.ok()).toBeTruthy();
  execFileSync(
    "docker-compose",
    [
      "-f",
      "backend/social/tests/compose.yml",
      "exec",
      "-T",
      "backend",
      "php",
      "social/manage.php",
      "admin",
      handle,
    ],
    { cwd: resolve(process.cwd(), "..") },
  );
  await page.goto("/");
  await expect(
    page.getByRole("heading", { name: "Deine Stadt. Deine Plattformen." }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Community anpassen" }).click();
  await expect(page.getByRole("dialog")).toBeVisible();
  await page.getByLabel("Community-Name").fill("Los Santos RP");
  await page
    .getByLabel("Anzeigename", { exact: true })
    .first()
    .fill("Stadtgespräch");
  await page.getByRole("button", { name: "Änderungen speichern" }).click();
  await expect(page.getByRole("dialog")).not.toBeVisible();
  await expect(
    page.getByRole("button", { name: "Stadtgespräch", exact: true }),
  ).toBeVisible();
  await page.screenshot({ path: "test-results/launcher.png", fullPage: true });
  await page
    .getByRole("button", { name: "Stadtgespräch", exact: true })
    .click();
  await expect(
    page.getByRole("heading", { name: "Stadtgespräch", exact: true }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Was gibt es Neues?" }).click();
  await page
    .getByLabel("Text", { exact: true })
    .fill(`Heute am Pier. ${handle} #LosSantos <img src=x onerror=alert(1)>`);
  const editor = page.getByRole("dialog");
  await editor
    .getByLabel("Kategorie", { exact: true })
    .selectOption("Veranstaltungen");
  await editor.getByLabel("Als KI-generiert kennzeichnen").check();
  await page.getByLabel("Sichtbarkeit", { exact: true }).selectOption("public");
  await page.getByRole("button", { name: "Speichern", exact: true }).click();
  await expect(page.getByRole("dialog")).not.toBeVisible();
  await expect(
    page.getByText(
      `Heute am Pier. ${handle} #LosSantos <img src=x onerror=alert(1)>`,
      {
        exact: true,
      },
    ),
  ).toBeVisible();
  const newPost = page.locator("article.post").filter({ hasText: handle });
  await expect(
    newPost.getByText("KI-generiert", { exact: true }),
  ).toBeVisible();
  await expect(
    newPost.getByText("Veranstaltungen", { exact: true }),
  ).toBeVisible();
  await page.getByRole("button", { name: "Dunkler Modus" }).click();
  await expect(page.locator(".suite")).toHaveClass(/dark/);
  await page.screenshot({ path: "test-results/feed-dark.png", fullPage: true });
  await page.getByRole("button", { name: "Heller Modus" }).click();
  await expect(page.locator(".suite")).toHaveClass(/light/);
  await page.screenshot({
    path: "test-results/feed-light.png",
    fullPage: true,
  });
  await page.setViewportSize({ width: 390, height: 844 });
  await expect
    .poll(() =>
      page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
    )
    .toBeTruthy();
  await page.screenshot({ path: "test-results/mobile.png", fullPage: true });
  await page.goto("/#/account");
  await page.getByRole("button", { name: "Profil bearbeiten" }).click();
  await page.getByLabel("Beschreibung").fill("Unterwegs an der Küste.");
  await page.getByLabel("Persönliche Signatur").fill("Liebe Grüße, Mia");
  await page.getByRole("button", { name: "Speichern", exact: true }).click();
  await expect(page.getByRole("dialog")).not.toBeVisible();
  await page.request.post('/api/social/index.php?action=logout',{headers:{'X-Social-Request':'1'},data:{}});
  await page.goto('/#/companies');
  await page.reload();
  await page.getByRole('banner').getByRole('button',{name:'Anmelden',exact:true}).click();
  await page.getByLabel('Benutzername',{exact:true}).fill(handle);
  await page.getByLabel('Passwort',{exact:true}).fill(password);
  await page.locator('.auth-form').getByRole('button',{name:'Anmelden',exact:true}).click();
  await expect(page).toHaveURL(/#\/companies$/);
  await expect(page.locator('.auth-form')).toHaveCount(0);
  expect(errors).toEqual([]);
});

test('guest feed invites participation and login can return to reading', async ({page}) => {
  await page.goto('/#/social');
  await expect(page.getByRole('heading', {name:'Mach mit', exact:true})).toBeVisible();
  await expect(page.getByRole('heading', {name:'Willkommen zurück'})).toHaveCount(0);
  await expect(page.getByRole('button',{name:'Freunde',exact:true})).toHaveCount(0);
  await page.locator('.guest-invitation').getByRole('button', {name:'Anmelden', exact:true}).click();
  await expect(page.getByRole('heading', {name:'Willkommen zurück'})).toBeVisible();
  const intro=await page.locator('.auth-intro').boundingBox();
  const form=await page.locator('.auth-form').boundingBox();
  expect(Math.abs((intro!.y+intro!.height/2)-(form!.y+form!.height/2))).toBeLessThan(10);
  await page.screenshot({path:'test-results/social-login.png',fullPage:true});
  await page.getByRole('button', {name:'Ohne Anmeldung weiterlesen'}).click();
  await expect(page.getByRole('heading', {name:'Mach mit', exact:true})).toBeVisible();
  await page.reload();
  await expect(page.getByRole('heading', {name:'Mach mit', exact:true})).toBeVisible();
});
