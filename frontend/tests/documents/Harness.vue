<script setup lang="ts">
import {ref} from 'vue';
import {useRouter} from 'vue-router';
const router=useRouter();
(window as any).navigateAway=()=>router.push('/other');
import DocumentEditor from '../../src/components/document/DocumentEditor.vue';
const closed=ref(false);
const canEdit = !location.search.includes('readonly');
const document=ref<any>({id:location.search.includes('new')?-1:1,category_id:1,title:'Dienstanweisung',content:'<p>Wichtiger Inhalt</p>',notes:'Interne Notiz',view_type:location.search.includes('sheet')?'spreadsheet':'document',default_view:location.search.includes('sheet')?'spreadsheet':'document',sort_order:0});
const categories:any=[{id:1,name:'Allgemeines'}];
const saveDocument=async(payload:any)=>{
  (window as any).savedPayload=payload;
  return await new Promise<boolean>(resolve=>{(window as any).finishSave=resolve});
};
</script>
<template><v-app><main style="height:100dvh;display:flex;flex-direction:column" data-window-id="document-test"><p v-if="closed">Dokument geschlossen</p><DocumentEditor v-else :selected-document="document" :selected-document-preview="null" :selected-document-employee-document="null" :categories="categories" :can-edit="canEdit" :save-document="saveDocument" window-id="document-test" @close-document="closed=true" /></main></v-app></template>
