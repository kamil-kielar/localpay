<script setup>
import { Chart, registerables } from 'chart.js';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
Chart.register(...registerables);
const props = defineProps({ type: { type: String, default: 'line' }, data: { type: Object, required: true } });
const canvas = ref(null);
let chart;
function render() {
    chart?.destroy();
    chart = new Chart(canvas.value, {
        type: props.type,
        data: props.data,
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { labels: { color: '#8794aa' } } },
            scales: props.type === 'doughnut' ? {} : {
                x: { ticks: { color: '#8794aa' }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { color: '#8794aa' }, grid: { color: 'rgba(130,150,180,.12)' } },
            },
        },
    });
}
onMounted(render);
watch(() => props.data, render, { deep: true });
onBeforeUnmount(() => chart?.destroy());
</script>
<template><div class="chart-box"><canvas ref="canvas"></canvas></div></template>
