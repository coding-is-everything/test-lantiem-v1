<template>
  <div class="chart-card">
    <h3>Engine Hours</h3>
    <div class="chart-container">
      <canvas ref="chart"></canvas>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue';
import { Chart } from 'chart.js/auto';

export default {
  name: 'EngineHoursChart',
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

    const createChart = () => {
      if (chartInstance) {
        chartInstance.destroy();
      }

      const ctx = chart.value.getContext('2d');
      chartInstance = new Chart(ctx, {
        type: 'bar',
        data: props.chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              mode: 'index',
              intersect: false,
              callbacks: {
                label: function(context) {
                  return context.parsed.y + ' hours';
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                display: true,
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                callback: function(value) {
                  return value + 'h';
                }
              }
            },
            x: {
              grid: {
                display: false
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
