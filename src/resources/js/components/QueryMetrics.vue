<template>
  <div><canvas ref="chart"></canvas></div>
</template>
<script>
import { onMounted, ref } from 'vue';
import Chart from 'chart.js/auto';
export default {setup(){
  const chartRef=ref(null);
  onMounted(()=>{ fetch('/api/query-optimizer/metrics').then(r=>r.json()).then(d=>{
    new Chart(chartRef.value,{type:'line',data:{labels:d.map((_,i)=>i),datasets:[{label:'Exec Time',data:d.map(x=>x.time)}]}});
  });
});
  return{chartRef};
}};
</script>