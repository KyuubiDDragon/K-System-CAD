<script setup lang="ts">
import {onMounted,onUnmounted,ref,watch} from 'vue';
import {api,type Row} from './api';
const props=defineProps<{me:Row|null;cadUrl?:string;ready:boolean}>();
const emit=defineEmits<{login:[id:number]}>();
const state=crypto.randomUUID(),pending=ref<Row|null>(null),password=ref(''),error=ref(''),busy=ref(false);
function origin(){try{return new URL(props.cadUrl||'').origin;}catch{return '';}}
function send(data:Row){const o=origin();if(o&&window.parent!==window)window.parent.postMessage({...data,state},o);}
function ready(){if(props.ready)send({type:'social-ready',signedIn:!!props.me,signedOut:sessionStorage.getItem('social-account')==='-1'});}
async function submit(){if(!pending.value||busy.value)return;busy.value=true;error.value='';try{
 const p=pending.value;const r=await api(p.purpose==='link'?'bridge_link':'bridge_login',{code:p.code,state,password:password.value});
 pending.value=null;send({type:'social-linked'});if(r.profile_id)emit('login',r.profile_id);
}catch(e){error.value=(e as Error).message;}finally{busy.value=false;password.value='';}}
function receive(e:MessageEvent){if(e.source!==window.parent||!origin()||e.origin!==origin()||e.data?.type!=='cad-social-code'||e.data.state!==state)return;
 if(!['login','link'].includes(e.data.purpose)||typeof e.data.code!=='string')return;
 pending.value=e.data;error.value='';
 if(e.data.purpose==='login'&&!props.me&&sessionStorage.getItem('social-account')!=='-1')void submit();
}
watch(()=>props.ready,ready);watch(()=>props.me?.id,ready);
onMounted(()=>{window.addEventListener('message',receive);ready();});onUnmounted(()=>window.removeEventListener('message',receive));
</script>
<template><section v-if="pending" class="bridge-confirm panel" role="dialog" aria-label="CAD-Kontoverbindung">
<h2>{{pending.purpose==='link'?'Dieses Konto mit CAD verbinden?':'Zum verbundenen Konto wechseln?'}}</h2>
<p v-if="pending.purpose==='link' && me">{{me.display_name}} wird mit deinem aktuell geöffneten CAD-Konto verbunden. Danach kannst du es dort ohne erneute Anmeldung öffnen.</p>
<p v-else-if="pending.purpose==='link'">Melde zuerst das gewünschte Social-Konto an und wähle anschließend im CAD erneut „Konto verbinden“.</p>
<p v-else>Der Wechsel gilt nur für dieses Fenster. Offene Eingaben werden verworfen.</p>
<p v-if="error" role="alert">{{error}}</p>
<form @submit.prevent="submit"><label v-if="pending.purpose==='link' && me">Social-Passwort<input v-model="password" type="password" autocomplete="current-password" required /></label>
<button v-if="pending.purpose==='login'||me" :disabled="busy">{{pending.purpose==='link'?'Verbindung bestätigen':'Konto öffnen'}}</button><button type="button" @click="pending=null">Abbrechen</button></form>
</section></template>
<style scoped>.bridge-confirm{position:fixed;z-index:500;top:90px;left:50%;transform:translateX(-50%);width:min(540px,calc(100% - 32px));padding:28px;box-shadow:0 12px 70px #0005}form{display:flex;gap:12px;flex-wrap:wrap}label{width:100%;display:grid;gap:8px}</style>
