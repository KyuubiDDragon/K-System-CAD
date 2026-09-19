import {test,expect} from '@playwright/test';
const url=process.env.DOCUMENT_TEST_URL || 'http://127.0.0.1:5186/tests/documents/index.html';
test.skip(!process.env.DOCUMENT_TEST_URL, 'Requires the CAD document test harness (see frontend/tests/documents/README.md).');
test.beforeEach(async({page})=>{await page.route('**/api/**',route=>route.fulfill({json:{success:true,shortcuts:[],data:[]}}));});
test('reader mode and save response control draft lifecycle',async({page})=>{
 await page.goto(url);
 await expect(page.getByRole('button',{name:'Bearbeiten',exact:true})).toBeVisible();
 await expect(page.getByRole('button',{name:'Speichern',exact:true})).toHaveCount(0);
 await expect(page.locator('[contenteditable=true]')).toHaveCount(0);
 await page.getByRole('button',{name:'Bearbeiten',exact:true}).click();
 await page.getByRole('textbox',{name:'Dokumenttitel'}).fill('Geänderter Entwurf');
 await page.getByRole('button',{name:'Speichern',exact:true}).click();
 await expect(page.getByRole('textbox',{name:'Dokumenttitel'})).toBeDisabled();
 await expect(page.getByText('Dokument geschlossen',{exact:true})).toHaveCount(0);
 await page.evaluate(()=> (window as any).finishSave(false));
 await expect(page.getByRole('alert')).toContainText('Nicht gespeichert');
 await expect(page.getByRole('textbox',{name:'Dokumenttitel'})).toHaveValue('Geänderter Entwurf');
 await page.getByRole('button',{name:'Speichern',exact:true}).click();
 await page.evaluate(()=> (window as any).finishSave(true));
 await expect(page.getByText('Dokument geschlossen',{exact:true})).toBeVisible();
});
test('unsaved guard targets only the owning desktop window',async({page})=>{
 await page.goto(url);await page.getByRole('button',{name:'Bearbeiten',exact:true}).click();
 await page.getByRole('textbox',{name:'Dokumenttitel'}).fill('Nicht gespeichert');
 page.on('dialog',dialog=>dialog.dismiss());
 await page.getByRole('button',{name:'Dokument schließen',exact:true}).click();
 await expect(page.getByRole('textbox',{name:'Dokumenttitel'})).toHaveValue('Nicht gespeichert');
 expect(await page.evaluate(()=>window.dispatchEvent(new CustomEvent('cad-before-close',{cancelable:true,detail:{windowId:'other'}})))).toBe(true);
 expect(await page.evaluate(()=>window.dispatchEvent(new CustomEvent('cad-before-close',{cancelable:true,detail:{windowId:'document-test'}})))).toBe(false);
});
test('read-only users and narrow windows',async({page},testInfo)=>{
 await page.setViewportSize({width:480,height:720});await page.goto(url+'?readonly');
 await expect(page.getByText('Dienstanweisung',{exact:true})).toBeVisible();
 await expect(page.getByRole('button',{name:'Bearbeiten',exact:true})).toHaveCount(0);
 await expect(page.getByRole('button',{name:'Speichern',exact:true})).toHaveCount(0);
 await page.getByRole('button',{name:'Weitere Dokumentaktionen'}).click();
 await page.getByText('Dokumentdetails',{exact:true}).click();
 await expect(page.getByRole('textbox',{name:'Notizen'})).toHaveAttribute('readonly','');
 expect(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth)).toBe(true);
 await page.getByRole('textbox',{name:'Notizen'}).click();
 await expect(page.locator('.v-menu .v-overlay__content')).not.toBeVisible();
 await page.screenshot({path:testInfo.outputPath('reader.png'),fullPage:true});
});

test('route navigation preserves dirty drafts and confirms discarding',async({page})=>{
 await page.goto(url);await page.getByRole('button',{name:'Bearbeiten',exact:true}).click();
 await page.getByRole('textbox',{name:'Dokumenttitel'}).fill('Entwurf');
 page.once('dialog',dialog=>dialog.dismiss());
 await page.evaluate(()=>(window as any).navigateAway());
 await expect(page.getByRole('textbox',{name:'Dokumenttitel'})).toHaveValue('Entwurf');
 page.once('dialog',dialog=>dialog.accept());
 await page.evaluate(()=>(window as any).navigateAway());
 await expect(page.getByText('Andere Seite')).toBeVisible();
});
test('new document validation and narrow dark editor',async({page},testInfo)=>{
 await page.setViewportSize({width:480,height:720});await page.goto(url+'?new&dark');
 await page.getByRole('button',{name:'Speichern',exact:true}).click();
 await expect(page.getByRole('alert')).toContainText('Titel');
 await expect(page.getByRole('textbox',{name:'Dokumenttitel'})).toBeVisible();
 await page.getByRole('textbox',{name:'Dokumenttitel'}).fill('Neues Dokument');
 expect(await page.evaluate(()=>document.documentElement.scrollWidth <= innerWidth)).toBe(true);
 await page.screenshot({path:testInfo.outputPath('editor-dark.png'),fullPage:true});
});
test('spreadsheet cell changes reach the save payload',async({page})=>{
 await page.setViewportSize({width:1100,height:820});await page.goto(url+'?sheet');
 await page.getByRole('button',{name:'Bearbeiten',exact:true}).click();
 await expect(page.locator('canvas').first()).toBeVisible();
 // Univer draws cells on a canvas; let its first frame and grid layout finish.
 await page.waitForTimeout(1500);
 await page.mouse.dblclick(180,224);await page.keyboard.type('Testwert');await page.keyboard.press('Enter');
 await expect(page.getByText('Ungespeicherte Änderungen',{exact:true})).toBeVisible();
 await page.getByRole('button',{name:'Speichern',exact:true}).click();
 await expect.poll(()=>page.evaluate(()=>(window as any).savedPayload?.spreadsheet_data?.sheets?.['sheet-01']?.cellData?.[0]?.[0]?.v)).toBe('Testwert');
 await page.evaluate(()=>(window as any).finishSave(true));
 await expect(page.getByText('Dokument geschlossen',{exact:true})).toBeVisible();
});
