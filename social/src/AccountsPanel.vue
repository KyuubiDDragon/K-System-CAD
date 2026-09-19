<script setup lang="ts">
import {onMounted,ref,reactive} from 'vue';
import {api,type Row} from './api';
const props=defineProps<{me:Row|null}>();
const emit=defineEmits<{switch:[id:number,acting?:number];company:[id:number]}>();
const data=ref<Row>({own:[],delegated:[],companies:[],invitations:[],transfers:[],links:[],members:[]});
const error=ref(''),busy=ref(false),password=ref(''),handle=ref(''),kind=ref('profile'),target=ref(0),members=ref<Row[]>([]);
const cadCandidates=ref<Row[]>([]);
const rights=ref(['posts']);const credentials=reactive({handle:'',password:''});
async function run(fn:()=>Promise<void>){busy.value=true;error.value='';try{await fn();}catch(e){error.value=(e as Error).message;}finally{busy.value=false;}}
async function load(){data.value=await api('accounts');if(props.me){const result=await api('access_members',{kind:kind.value,id:target.value});members.value=result.items;cadCandidates.value=result.cad_candidates||[];}}
function editRights(m:Row){handle.value=m.handle;rights.value=m.rights_json?JSON.parse(m.rights_json):['posts','profile','ads'];}
async function scope(){if(kind.value==='profile')rights.value=rights.value.filter(r=>r!=='ads');await run(load);}
async function add(){await run(async()=>{const r=await api('login',credentials);credentials.password='';await load();emit('switch',r.profile_id);});}
async function change(action:string,extra:Row={}){const confirmation=password.value;password.value='';await run(async()=>{await api(action,{kind:kind.value,id:target.value,password:confirmation,handle:handle.value,rights:rights.value,...extra});await load();});}
onMounted(()=>run(load));
</script>
<template>
<section class="accounts-panel panel">
 <h2>Konten &amp; Zugriffe</h2>
 <p>Eigene Konten und Unternehmenszugriffe bleiben getrennt. Der Wechsel gilt für dieses Fenster.</p>
 <p v-if="error" role="alert">{{ error }}</p>
 <h3>Gespeicherte Konten</h3>
 <div v-for="p in data.own" :key="p.id" class="account-row">
  <span><strong>{{p.display_name}}</strong> @{{p.handle}} <small v-if="Number(p.id)===Number(me?.id)">· Aktiv</small></span>
  <button :disabled="busy" @click="emit('switch',Number(p.id))">Öffnen</button>
  <button :disabled="busy || !me || Number(p.id)===Number(me?.id)" @click="change('forget_account',{id:p.id})">Aus Liste entfernen</button>
 </div>
 <p v-if="!data.own.length">Noch keine Konten gespeichert.</p>
 <details><summary>Weiteres eigenes Konto anmelden</summary>
  <form @submit.prevent="add"><label>Benutzername<input v-model="credentials.handle" autocomplete="username" required /></label><label>Passwort<input v-model="credentials.password" type="password" autocomplete="current-password" required /></label><button :disabled="busy">Anmelden und speichern</button></form>
 </details>
 <template v-if="me">
 <h3>Für mich freigegeben</h3>
 <div v-for="p in data.delegated" :key="p.id" class="account-row"><span>{{p.display_name}} · Freigegebener Zugang</span><button @click="emit('switch',Number(me.id),Number(p.id))">Als dieses Profil arbeiten</button></div>
 <p v-if="!data.delegated.length">Keine weiteren Profilzugriffe.</p>
 <h3>Meine Unternehmen</h3>
 <div v-for="c in data.companies" :key="c.id" class="account-row"><span>{{c.name}} · {{Number(c.owner_id)===Number(me.id)?'Haupteigner':'Mitarbeiter'}}</span><button @click="emit('company',Number(c.id))">Unternehmensseite</button></div>
 <p v-if="!data.companies.length">Noch keine Unternehmensmitgliedschaften.</p>
 <h3 v-if="data.invitations.length">Einladungen</h3>
 <div v-for="i in data.invitations" :key="i.kind+i.id" class="account-row"><span>{{i.name}}</span><button @click="change('accept_access',{kind:i.kind,id:i.id,accept:true})">Annehmen</button><button @click="change('accept_access',{kind:i.kind,id:i.id,accept:false})">Ablehnen</button></div>
 <h3 v-if="data.transfers?.length">Angebotene Unternehmensübernahmen</h3>
 <div v-for="t in data.transfers" :key="t.id" class="account-row"><span>{{t.name}}</span><button :disabled="!password" @click="change('accept_owner',{id:t.id})">Haupteigner werden</button></div>
 <h3>Zugriffe verwalten</h3>
 <label>Konto<select :value="kind+':'+target" @change="kind=($event.target as HTMLSelectElement).value.split(':')[0];target=Number(($event.target as HTMLSelectElement).value.split(':')[1]);scope()"><option value="profile:0">Mein Profil</option><option v-for="c in data.companies.filter((c:Row)=>Number(c.owner_id)===Number(me?.id))" :key="c.id" :value="'company:'+c.id">{{c.name}}</option></select></label>
 <p>Nur der Haupteigner kann Personen einladen oder entfernen. Private Nachrichten und Sicherheitseinstellungen werden nicht freigegeben. Geänderte Rechte müssen erneut angenommen werden.</p>
 <label>Dein Passwort zur Bestätigung<input v-model="password" type="password" autocomplete="current-password" /></label>
 <div v-for="m in members" :key="m.member_id" class="account-row"><span>{{m.display_name}} (@{{m.handle}}) · {{m.status==='active'?'Aktiv':'Einladung offen'}}</span><button @click="editRights(m)">Rechte ändern</button><button :disabled="busy||!password" @click="change('revoke_access',{handle:m.handle})">Zugriff entziehen</button></div>
 <label v-if="cadCandidates.length">Verknüpfte CAD-Mitarbeiter<select @change="handle=($event.target as HTMLSelectElement).value"><option value="">Person auswählen</option><option v-for="c in cadCandidates" :key="c.handle" :value="c.handle">{{c.username}} → {{c.display_name}} (@{{c.handle}})</option></select></label>
 <form @submit.prevent="change('grant_access')"><label>Benutzername der Person<input v-model="handle" required /></label>
 <label class="check"><input v-model="rights" type="checkbox" value="posts" />Beiträge veröffentlichen und verwalten</label>
 <label class="check"><input v-model="rights" type="checkbox" value="profile" />{{kind==='company'?'Profil und Öffnungsstatus pflegen':'Name, Beschreibung und Profilbilder pflegen'}}</label>
 <label v-if="kind==='company'" class="check"><input v-model="rights" type="checkbox" value="ads" />Werbung beantragen</label>
 <button :disabled="busy||!password||!rights.length">Mit diesen Rechten einladen</button></form>
 <details v-if="kind==='company'"><summary>Haupteigner wechseln</summary><p>Die oben angegebene Person muss die Übernahme bestätigen. Bis dahin bleibst du Haupteigner. Die Übertragung ersetzt bestehende Übernahmeangebote.</p><button :disabled="busy||!password||!handle" @click="change('transfer_owner')">Übernahme anbieten</button></details>
 <h3>Verbundene CAD-Konten</h3><p v-if="!data.links.length">Öffne Social im CAD und wähle dort „Konto verbinden“.</p>
 <div v-for="l in data.links" :key="l.id" class="account-row"><span>{{l.username}} · {{l.authority_name}}</span><button :disabled="busy||!password" @click="change('unlink_cad',{id:l.id})">Verbindung trennen</button></div>
 </template>
</section>
</template>
<style scoped>
.accounts-panel{box-sizing:border-box;width:calc(100% - 32px);max-width:780px;margin:24px auto;padding:clamp(18px,3vw,32px)}h3{margin-top:28px}.account-row{display:flex;align-items:center;gap:12px;flex-wrap:wrap;padding:14px 0;border-bottom:1px solid var(--line)}.account-row span{flex:1;min-width:180px}form{display:grid;gap:14px;margin-top:16px}details{margin-top:20px}p{color:var(--muted);line-height:1.6}label{display:grid;gap:8px;margin:12px 0}label.check{display:flex}
</style>
