<script setup lang="ts">
import { ref,onMounted,onUnmounted } from 'vue';
const loaded=ref(false), frame=ref<HTMLIFrameElement|null>(null),links=ref<any[]>([]),error=ref(''),state=ref(''),selected=ref(0),manage=ref(false),password=ref('');
const configured=import.meta.env.VITE_SOCIAL_ENABLED==='true'?import.meta.env.VITE_SOCIAL_URL:'';
const source=(()=>{try{const u=new URL(configured);return ['https:','http:'].includes(u.protocol)?u.href:'';}catch{return '';}})();
const endpoint=(import.meta.env.VITE_API_URL||'/api').replace(/\/$/,'')+'/social/cad.php';
const mapping=ref<any>(null),mapCompany=ref(0),mapAuthority=ref(0),canMap=ref(false);
let autoAttempted=false;
async function request(action:string,data?:any){const r=await fetch(endpoint+'?action='+action,{method:data?'POST':'GET',credentials:'include',headers:data?{'Content-Type':'application/json','X-Social-Cad-Request':'1'}:{},body:data?JSON.stringify(data):undefined});const body=await r.json();if(!r.ok)throw Error(body.error||'Verbindung fehlgeschlagen.');return body;}
async function refresh(){try{const result=await request('list');links.value=result.items;canMap.value=result.can_manage_mapping;selected.value=Number(links.value.find(l=>Number(l.is_default))?.id||links.value[0]?.id||0);}catch(e){error.value=(e as Error).message;}}
async function issue(purpose:string){error.value='';try{const r=await request('issue',{purpose,id:selected.value,state:state.value});if(r.origin!==new URL(source).origin)throw Error('Die Social-Adresse stimmt nicht mit der Serverkonfiguration überein.');frame.value?.contentWindow?.postMessage({type:'cad-social-code',purpose,code:r.code,state:state.value},r.origin);}catch(e){error.value=(e as Error).message;}}
async function mappings(save=false){try{if(save)await request('company_mapping',{company_id:mapCompany.value,authority_id:mapAuthority.value});mapping.value=await request('company_mapping');}catch(e){error.value=(e as Error).message;}}
async function change(action:string){try{await request(action,{id:selected.value,password:password.value});await refresh();}catch(e){error.value=(e as Error).message;}finally{password.value='';}}
async function receive(e:MessageEvent){if(!source||e.source!==frame.value?.contentWindow||e.origin!==new URL(source).origin)return;
 if(e.data?.type==='social-linked'){await refresh();return;}
 if(e.data?.type!=='social-ready'||typeof e.data.state!=='string')return;
 state.value=e.data.state;await refresh();
 if(!autoAttempted&&!e.data.signedIn&&!e.data.signedOut){autoAttempted=true;if(links.value.length===1||links.value.some(l=>Number(l.is_default)))await issue('login');}
}
onMounted(()=>window.addEventListener('message',receive));onUnmounted(()=>window.removeEventListener('message',receive));
</script>
<template><section class="social-desktop-app">
<p v-if="!source" class="social-status">Die Social-Plattform ist für diese Installation nicht eingerichtet.</p>
<template v-else>
<div class="social-toolbar"><button @click="manage=!manage">Kontoverbindungen</button><span v-if="!loaded">Social wird geladen …</span></div>
<div v-if="manage" class="social-connections"><p>Verbinde deine eigenen Social-Konten. Unternehmen und Mitarbeiterrechte verwaltest du in Social unter „Konten &amp; Zugriffe“.</p>
<select v-if="links.length" v-model="selected" aria-label="Verbundenes Social-Konto"><option v-for="l in links" :key="l.id" :value="Number(l.id)">{{l.display_name}} (@{{l.handle}}){{Number(l.is_default)?' · Standard':''}}</option></select>
<button v-if="links.length" :disabled="!state" @click="issue('login')">Konto öffnen</button><button :disabled="!state" @click="issue('link')">Aktuelles Social-Konto verbinden</button>
<template v-if="links.length"><label>CAD-Passwort für Änderungen<input v-model="password" type="password" autocomplete="current-password" /></label><button :disabled="!password" @click="change('default')">Als Startkonto verwenden</button><button :disabled="!password" @click="change('unlink')">Verbindung trennen</button></template>
<details v-if="canMap"><summary @click="mappings()">Unternehmen mit CAD zuordnen</summary><p>Diese Zuordnung wird von der Systemverwaltung bestätigt. Mitarbeiterrechte vergibt weiterhin der Haupteigner in Social.</p><template v-if="mapping"><select v-model="mapCompany" @change="mapAuthority=Number(mapping.companies.find((c:any)=>Number(c.id)===mapCompany)?.cad_authority_id||0)" aria-label="Social-Unternehmen"><option :value="0">Unternehmen wählen</option><option v-for="c in mapping.companies" :key="c.id" :value="Number(c.id)">{{c.name}}</option></select><select v-model="mapAuthority" aria-label="CAD-Unternehmen"><option :value="0">Keine Zuordnung</option><option v-for="a in mapping.authorities" :key="a.id" :value="Number(a.id)">{{a.display_name}}</option></select><button :disabled="!mapCompany" @click="mappings(true)">Zuordnung speichern</button></template></details>
</div>
<p v-if="error" class="social-status" role="alert">{{error}}</p>
<iframe ref="frame" :src="source" title="Social-Plattform" allow="fullscreen" referrerpolicy="strict-origin-when-cross-origin" @load="loaded=true" />
</template></section></template>
<style scoped>
.social-desktop-app{display:flex;flex-direction:column;flex:1;min-height:0;height:100%;width:100%;overflow:hidden;background:rgb(var(--v-theme-background))}.social-desktop-app iframe{display:block;border:0;width:100%;flex:1;min-height:0}.social-toolbar,.social-connections,.social-status{padding:12px 20px}.social-toolbar{display:flex;gap:16px;border-bottom:1px solid #8883}.social-connections{display:flex;gap:12px;flex-wrap:wrap;align-items:center}.social-connections p{width:100%;margin-bottom:4px}.social-connections input,.social-connections select{border:1px solid #8885;padding:6px;border-radius:6px}button{padding:6px 10px;border:1px solid #8885;border-radius:6px}button:disabled{opacity:.5}.social-status{margin:0;color:rgb(var(--v-theme-error))}
</style>
