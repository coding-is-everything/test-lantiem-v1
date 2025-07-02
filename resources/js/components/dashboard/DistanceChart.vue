<template>
  <div class="chart-card">
    <h3>Traveled Distance</h3>
    <div class="chart-container">
      <canvas ref="chart"></canvas>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, onBeforeUnmount, watch, nextTick, onBeforeMount } from 'vue';
import { Chart } from 'chart.js/auto';
import axios from 'axios';

export default {
  name: 'DistanceChart',
  props: {
    chartData: {
      type: Object,
      required: true,
      validator: (value) => {
        return value && Array.isArray(value.labels) && Array.isArray(value.datasets);
      }
    },
    dateRange: {
      type: String,
      required: true,
      validator: (value) => ['today', 'week', 'month', 'year'].includes(value)
    }
  },
  setup(props) {
    const chart = ref(null);
    let chartInstance = null;
    
    // Function to log data to a text file
    const logChartData = async (data, dateRange) => {
      try {
        const logEntry = `=== Distance Chart Data (${new Date().toISOString()}) ===\n` +
                       `Date Range: ${dateRange}\n` +
                       `Labels: ${JSON.stringify(data.labels)}\n` +
                       `Data: ${JSON.stringify(data.datasets?.[0]?.data || [])}\n\n`;
        
        // Log to console for debugging
        console.log('Chart Data Log:', logEntry);
        
        // Create a blob and download link
        const blob = new Blob([logEntry], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'distance-chart-data.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
      } catch (error) {
        console.error('Error logging chart data:', error);
      }
    };

    const createChart = () => {
      if (!chart.value) return;
      
      const ctx = chart.value.getContext('2d');
      if (!ctx) return;

      // Destroy existing chart if it exists
      if (chartInstance) {
        chartInstance.destroy();
      }

      // Ensure we have valid data
      if (!props.chartData || !props.chartData.labels || !props.chartData.datasets) {
        console.error('Invalid chart data structure:', props.chartData);
        return;
      }

      try {
        chartInstance = new Chart(ctx, {
          type: 'line',
          data: {
            labels: [...props.chartData.labels],
            datasets: props.chartData.datasets.map(dataset => ({
              ...dataset,
              borderColor: dataset.borderColor || '#4CAF50',
              backgroundColor: dataset.backgroundColor || 'rgba(76, 175, 80, 0.1)',
              borderWidth: 2,
              tension: 0.3,
              fill: true,
              pointBackgroundColor: '#4CAF50',
              pointBorderColor: '#fff',
              pointHoverBackgroundColor: '#fff',
              pointHoverBorderColor: '#4CAF50'
            }))
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
              duration: 400,
              easing: 'easeInOutQuart'
            },
            plugins: {
              legend: {
                display: false
              },
              tooltip: {
                mode: 'index',
                intersect: false,
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleFont: { size: 12 },
                bodyFont: { size: 12 },
                padding: 10,
                displayColors: true,
                callbacks: {
                  label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) label += ': ';
                    if (context.parsed.y !== null) {
                      label += context.parsed.y.toFixed(2) + ' km';
                    }
                    return label;
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
                    return value + ' km';
                  },
                  font: {
                    size: 11
                  }
                },
                title: {
                  display: true,
                  text: 'Distance (km)'
                }
              },
              x: {
                grid: {
                  display: false
                },
                ticks: {
                  maxRotation: 45,
                  minRotation: 45,
                  font: {
                    size: 11
                  }
                }
              }
            },
            elements: {
              line: {
                tension: 0.3
              },
              point: {
                radius: 3,
                hoverRadius: 5,
                hitRadius: 10,
                borderWidth: 2
              }
            }
          }
        });
      } catch (error) {
        console.error('Error creating chart:', error);
      }
    };

    const updateChart = () => {
      if (!chartInstance || !props.chartData) return;
      
      try {
        // Update chart data
        chartInstance.data.labels = [...props.chartData.labels];
        
        // Update each dataset
        props.chartData.datasets.forEach((dataset, i) => {
          if (!chartInstance.data.datasets[i]) {
            chartInstance.data.datasets[i] = {};
          }
          chartInstance.data.datasets[i].data = [...dataset.data];
        });
        
        // Update chart
        chartInstance.update('none'); // 'none' prevents animation on data update
      } catch (error) {
        console.error('Error updating chart:', error);
        // If update fails, recreate the chart
        createChart();
      }
    };

    onMounted(() => {
      createChart();
    });

    onBeforeUnmount(() => {
      if (chartInstance) {
        chartInstance.destroy();
        chartInstance = null;
      }
    });

    // Watch for data changes
    watch(
      () => props.chartData,
      (newData, oldData) => {
        if (JSON.stringify(newData) !== JSON.stringify(oldData)) {
          // Log the data being received
          console.log('DistanceChart - New chart data received:', {
            labels: newData.labels,
            data: newData.datasets?.[0]?.data || [],
            dateRange: props.dateRange
          });
          
          // Log to file
          logChartData(newData, props.dateRange);
          
          nextTick(() => {
            if (chartInstance) {
              updateChart();
            } else {
              createChart();
            }
          });
        }
      },
      { deep: true, immediate: true }
    );

    // Watch for date range changes
    watch(
      () => props.dateRange,
      (newRange, oldRange) => {
        if (newRange !== oldRange && chartInstance) {
          nextTick(() => {
            updateChart();
          });
        }
      }
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
