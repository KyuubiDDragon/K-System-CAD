# Dokumenteditor-Browsertests

Isolierte Oberfläche mit dem echten Dokumenteditor, Vuetify und Tiptap. Der Speicheradapter ist absichtlich steuerbar, um langsame und fehlgeschlagene Antworten ohne Produktionsdaten zu testen.

1. `npm --prefix frontend run dev -- --host 127.0.0.1 --port 5186`
2. `cd social`
3. `DOCUMENT_TEST_URL=http://127.0.0.1:5186/tests/documents/index.html npx playwright test documents.spec.ts`

Ohne DOCUMENT_TEST_URL werden diese Tests beim Social-Testlauf übersprungen. Der Testeinstieg wird nicht in den Produktionsbuild aufgenommen.
