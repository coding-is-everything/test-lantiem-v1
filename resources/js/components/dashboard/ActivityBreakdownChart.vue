<template>
  <div class="chart-card">
    <h3>Activity Breakdown</h3>
    <div class="chart-container">
      <canvas ref="chart"></canvas>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Chart, ArcElement, Tooltip, Legend } from 'chart.js';

export default {
  name: 'ActivityBreakdownChart',
  props: {
    chartData: {
      type: Object,
      required: true
    },
    dateRange: {
      type: String,
      required: true
    }
  },
  setup(props) {
    const chart = ref(null);
    let chartInstance = null;

    // Register required Chart.js components
    Chart.register(ArcElement, Tooltip, Legend);

    const createChart = () => {
      if (chartInstance) {
        chartInstance.destroy();
      }

      const ctx = chart.value.getContext('2d');
      chartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: props.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: {
              position: 'right',
              labels: {
                usePointStyle: true,
                pointStyle: 'circle',
                padding: 20
              }
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.parsed || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        }
      });
    };

    onMounted(() => {
      createChart();
    });

    onBeforeUnmount(() => {
      if (chartInstance) {
        chartInstance.destroy();
      }
    });

    watch(
      () => [props.chartData, props.dateRange],
      () => {
        createChart();
      },
      { deep: true }
    );

    return {
      chart
    };
  }
};
</script>

<style scoped>
.chart-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}

.chart-card h3 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
  color: #333;
}

.chart-container {
  position: relative;
  height: 250px;
  width: 100%;
}
</style>
