import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {resolve} from 'node:path';
const cli=(...args:string[])=>execFileSync('docker-compose',['-f','backend/social/tests/compose.yml','exec','-T','backend',...args],{cwd:resolve(process.cwd(),'..'),encoding:'utf8'});
test('CAD iframe bridge confirms ownership, rejects foreign messages and logs in with one-use codes',async({page,context,baseURL})=>{
 const fixture=JSON.parse(cli('cat','/tmp/accounts-test-fixture'));
 const token=cli('cat','/tmp/accounts-cad-token').trim();
 const cad=new URL(process.env.SOCIAL_TEST_URL||'http://127.0.0.1:8087/api/social/index.php').origin;
 await context.addCookies([{name:'auth_token',value:token,url:cad}]);
 const login=await context.request.post(baseURL+'/api/social/index.php?action=login',{headers:{'X-Social-Request':'1'},data:{handle:fixture.second.handle,password:fixture.password}});expect(login.ok()).toBeTruthy();
 await page.goto(cad+'/api/social/tests/bridge.html?social='+encodeURIComponent(baseURL!));await expect(page.locator('#status')).toHaveText('ready');
 const social=page.frameLocator('iframe');await expect(social.getByRole('heading',{name:'Deine Stadt. Deine Plattformen.'})).toBeVisible();
 // A message sent from Social itself is not a trusted parent message.
 const child=page.frames().find(f=>f.url().startsWith(baseURL!))!;
 await child.evaluate(()=>window.postMessage({type:'cad-social-code',purpose:'link',state:'forged',code:'forged'},location.origin));
 await expect(social.getByRole('dialog',{name:'CAD-Kontoverbindung'})).toHaveCount(0);
 await page.getByRole('button',{name:'Verbinden',exact:true}).click();const dialog=social.getByRole('dialog',{name:'CAD-Kontoverbindung'});await expect(dialog).toBeVisible();
 await dialog.getByLabel('Social-Passwort').fill(fixture.password);await dialog.getByRole('button',{name:'Verbindung bestätigen'}).click();await expect(dialog).toHaveCount(0);
 const list=await context.request.get(cad+'/api/social/cad.php?action=list');expect((await list.json()).items).toHaveLength(1);
 await social.getByLabel('Profilmenü').click();await social.getByRole('button',{name:'Abmelden',exact:true}).click();await expect(social.getByRole('button',{name:'Anmelden',exact:true})).toBeVisible();
 await page.getByRole('button',{name:'Konto öffnen',exact:true}).click();await dialog.getByRole('button',{name:'Konto öffnen'}).click();await expect(social.getByLabel('Profilmenü')).toBeVisible();
 cli('php','-r',`$p=new PDO('mysql:host=db;dbname=social_test',getenv('DB_USERNAME'),getenv('DB_PASSWORD'));$q=$p->prepare('UPDATE kdd_sessions SET is_active=0 WHERE token_hash=?');$q->execute([hash('sha256',file_get_contents('/tmp/accounts-cad-token'))]);`);
 await child.evaluate(()=>location.reload());await expect(social.getByRole('button',{name:'Anmelden',exact:true})).toBeVisible();
});
