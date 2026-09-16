<script setup lang="ts">
defineProps<{ version: Record<string, any>; anchorPrefix?: string }>();
const amount = (value: string | number) => new Intl.NumberFormat('de-DE', {maximumFractionDigits:2}).format(Number(value));
</script>
<template>
 <div class="version-text law-text">{{version.body}}</div>
 <section v-for="part in version.subsections || []" :key="part.number" :id="anchorPrefix ? anchorPrefix+'-'+part.number : undefined" class="law-subsection">
  <span class="subsection-number">({{part.number}})</span><div><h4 v-if="part.title">{{part.title}}</h4><div class="version-text">{{part.body}}</div></div>
 </section>
 <div v-if="version.amount_min !== null && version.amount_min !== undefined && version.amount_min !== ''" class="law-amount">
  <span>{{version.amount_kind==='fee'?'Gebühr':'Geldstrafe'}}</span><strong>{{amount(version.amount_min)}}<template v-if="version.amount_max!==null && version.amount_max!==undefined && version.amount_max!=='' && Number(version.amount_max)!==Number(version.amount_min)"> – {{amount(version.amount_max)}}</template> RP-$</strong>
 </div>
</template>
<style scoped>
.version-text{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.8;margin:16px 0}.law-subsection{scroll-margin-top:100px;display:grid;grid-template-columns:44px minmax(0,1fr);gap:12px;margin:18px 0}.subsection-number{font-weight:600;opacity:.65;padding-top:3px}.law-subsection h4{font-size:15px;margin:0}.law-subsection .version-text{margin:4px 0}.law-amount{display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;border-top:1px solid var(--law-line,#d7dee2);padding-top:16px;margin-top:22px;font-size:14px}.law-amount span{opacity:.7}.law-amount strong{font-variant-numeric:tabular-nums}
</style>
