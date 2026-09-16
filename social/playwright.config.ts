import { defineConfig } from "@playwright/test";
export default defineConfig({
  testDir: "./tests/browser",
  workers: 1,
  use: { baseURL: process.env.SOCIAL_TEST_BROWSER_URL || "http://127.0.0.1:5174", headless: true },
  reporter: "list",
  outputDir: "test-results",
});
