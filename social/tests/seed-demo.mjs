// Local fixtures for the disposable kyuubi-social-test stack only.
import {execFileSync} from 'node:child_process';
import {readFileSync} from 'node:fs';
const root=new URL('../../',import.meta.url);
const base='http://127.0.0.1:8088/api/social/index.php';
const password='Nur-lokale-Demo-2026!';
const cookies={};
async function call(who,action,data){
 const r=await fetch(base+'?action='+action,{method:data?'POST':'GET',headers:{Cookie:cookies[who]||'','X-Social-Request':'1',...(data?{'Content-Type':'application/json'}:{})},body:data?JSON.stringify(data):undefined});
 const jar=Object.fromEntries((cookies[who]||'').split('; ').filter(Boolean).map(v=>v.split('=')));for(const line of r.headers.getSetCookie()){const [k,v]=line.split(';')[0].split('=');jar[k]=v;}cookies[who]=Object.entries(jar).map(([k,v])=>k+'='+v).join('; ');
 const value=await r.json();if(!r.ok)throw Error(JSON.stringify(value));return value;
}
await call('demo','register',{handle:'demo',display_name:'Mia Bennett',password});
execFileSync('docker-compose',['-f','backend/social/tests/compose.yml','exec','-T','backend','php','social/manage.php','admin','demo'],{cwd:root});
let b=await call('demo','bootstrap');
await call('demo','save_settings',{revision:b.revision,settings:{community:'Los Santos RP',registration:'immediate',guest:true,names:{social:'Stadtgespräch',gram:'Pacific Gram',market:'Marktplatz',video:'Vinewood Video'},info_blocks:[{title:'Deine Stadt, dein Moment',body:'Neu in Los Santos? Stelle dich vor, finde Menschen und entdecke, was heute in der Stadt passiert.'}]}});
await call('garage','register',{handle:'bennys',display_name:'Alex Rivera',password});
const garage=(await call('garage','bootstrap')).me.id;
const company=(await call('demo','save_company',{name:'Benny’s Motorworks',description:'Werkstatt, Fahrzeugpflege und gute Gespräche in Strawberry.',location:'Strawberry',contact:'Nachricht an Alex Rivera',verified:true,members:[garage]})).id;
await call('demo','grant_access',{kind:'company',id:company,handle:'bennys',rights:['posts','profile','ads'],password});
await call('garage','accept_access',{kind:'company',id:company,accept:true});
await call('garage','company_open',{id:company,minutes:120});
await call('garage','save_post',{module:'social',body:'Samstag ist Werkstatttag. Kommt mit euren Klassikern vorbei – wir kümmern uns um den Rest. Ab 18 Uhr in Strawberry. #CarMeet #LosSantos',visibility:'public',company_id:company});
async function photo(who,module,purpose='post'){const f=new FormData();f.set('file',new Blob([readFileSync(new URL('../public/city.png',import.meta.url))]),'los-santos.png');f.set('module',module);f.set('purpose',purpose);const r=await fetch(base+'?action=upload',{method:'POST',headers:{Cookie:cookies[who],'X-Social-Request':'1'},body:f});const v=await r.json();if(!r.ok)throw Error(JSON.stringify(v));return v.id;}
await call('demo','save_post',{module:'social',body:'Noch eine Runde am Pier, bevor die Stadt aufwacht. Wer ist heute Abend dabei? 🌴 #Vespucci #LosSantos',visibility:'public',media:[await photo('demo','social')]});
await call('demo','save_post',{module:'gram',body:'Die Küste hat einfach das beste Licht. #Vespucci',visibility:'public',media:[await photo('demo','gram')]});
await call('garage','save_post',{module:'market',title:'Werkstattstellplatz in Strawberry',body:'Überdachter Stellplatz, monatlich verfügbar. Schreib mir für einen Besichtigungstermin.',price:350,category:'Immobilien',sale_state:'available',visibility:'public'});
await call('demo','save_profile',{display_name:'Mia Bennett',bio:'Fotografie, Küstenstraßen und das Leben in Los Santos.',privacy:'public',signature:'Liebe Grüße, Mia',messages:'public',requests:'public',wall:'friends',default_visibility:'public'});
await call('garage','company_details',{id:company,services:'Reparaturen, Fahrzeugpflege und Abschleppdienst',service_area:'Los Santos',opening_hours:'Mo–Fr: 18:00–23:00 Uhr\nSa: nach Vereinbarung',map_x:44.4,map_y:80,photo_id:await photo('garage','social','company')});
console.log('Lokale Demo bereit: http://127.0.0.1:5174 — demo / '+password);
