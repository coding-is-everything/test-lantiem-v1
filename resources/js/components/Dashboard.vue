<template>
  <div class="dashboard">
    <div class="header">
      <h1>Dashboard</h1>
      <div class="date-range">
        <button 
          v-for="range in dateRanges" 
          :key="range.value"
          @click="setDateRange(range.value)" 
          :class="{ active: dateRange === range.value }"
        >
          {{ range.label }}
        </button>
      </div>
    </div>
    
    <!-- Metrics Grid -->
    <div class="metrics-grid">
      <MetricsCard 
        :value="metrics.totalDistance"
        label="Total Distance"
        :trend="metrics.distanceTrend"
        format="distance"
        :loading="metrics.loading"
      />
      
      <MetricsCard 
        :value="metrics.totalEngineHours"
        label="Engine Hours"
        :trend="metrics.engineHoursTrend"
        format="time"
        :loading="metrics.loading"
      />
      
      <MetricsCard 
        :value="metrics.totalMessages"
        label="Messages"
        :trend="metrics.messagesTrend"
        :loading="metrics.loading"
      />
      
      <MetricsCard 
        :value="metrics.totalActivities"
        label="Total Activities"
        :trend="metrics.activitiesTrend"
        :loading="metrics.loading"
      />
    </div>
    
    <!-- Charts Grid -->
    <div class="charts-grid">
      <div class="chart-row">
        <DistanceChart 
          :chart-data="chartData.distance"
          :date-range="dateRange"
        />
        <EngineHoursChart 
          :chart-data="chartData.engineHours"
          :date-range="dateRange"
        />
      </div>
      
      <div class="chart-row">
        <ActivityBreakdownChart 
          :chart-data="chartData.activityBreakdown"
          :date-range="dateRange"
        />
        <MessagesReceivedChart 
          :chart-data="chartData.messagesReceived"
          :date-range="dateRange"
        />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';
import MetricsCard from './dashboard/MetricsCard.vue';
import DistanceChart from './dashboard/DistanceChart.vue';
import EngineHoursChart from './dashboard/EngineHoursChart.vue';
import ActivityBreakdownChart from './dashboard/ActivityBreakdownChart.vue';
import MessagesReceivedChart from './dashboard/MessagesReceivedChart.vue';

export default {
  name: 'Dashboard',
  components: {
    MetricsCard,
    DistanceChart,
    EngineHoursChart,
    ActivityBreakdownChart,
    MessagesReceivedChart
  },
  setup() {
    const dateRange = ref('week');
    const isLoading = ref(false);
    
    const dateRanges = [
      { label: 'Today', value: 'today' },
      { label: 'Week', value: 'week' },
      { label: 'Month', value: 'month' },
      { label: 'Year', value: 'year' }
    ];
    
    const metrics = reactive({
      loading: true,
      totalDistance: 0,
      totalEngineHours: 0,
      totalMessages: 0,
      totalActivities: 0,
      distanceTrend: 0,
      engineHoursTrend: 0,
      messagesTrend: 0,
      activitiesTrend: 0
    });
    
    const chartData = reactive({
      distance: {
        labels: [],
        datasets: [{
          label: 'Distance (km)',
          data: [],
          borderColor: '#4CAF50',
          backgroundColor: 'rgba(76, 175, 80, 0.1)',
          tension: 0.3,
          fill: true
        }]
      },
      engineHours: {
        labels: [],
        datasets: [{
          label: 'Engine Hours',
          data: [],
          tension: 0.4,
          fill: true
        }]
      },
      activityBreakdown: {
        labels: ['Idling', 'Moving', 'Stopped'],
        datasets: [{
          data: [],
          backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
        }]
      },
      messagesReceived: {
        labels: [],
        datasets: [
          {
            label: 'Alerts',
            data: [],
            backgroundColor: '#FF6384',
            borderRadius: 4
          },
          {
            label: 'Notifications',
            data: [],
            backgroundColor: '#36A2EB',
            borderRadius: 4
          }
        ]
      }
    });
    
    const fetchDashboardData = async () => {
      try {
        metrics.loading = true;
        
        // Calculate date range based on selected period
        const { startDate, endDate } = getDateRange(dateRange.value);
        
        // Fetch all data in parallel
        const [distanceRes, engineHoursRes, activityRes, messagesRes] = await Promise.all([
          axios.get('/api/distance/chart-data', { 
            params: { start_date: startDate, end_date: endDate }
          }),
          axios.get('/api/engine-hours/chart-data', { 
            params: { start_date: startDate, end_date: endDate }
          }),
          axios.get('/api/activity-breakdown/chart-data', { 
            params: { start_date: startDate, end_date: endDate }
          }),
          axios.get('/api/messages-received/chart-data', { 
            params: { start_date: startDate, end_date: endDate }
          })
        ]);
        
        // Process Distance data
        if (distanceRes.data?.success && distanceRes.data.data) {
          const distanceData = distanceRes.data.data;
          const labels = [];
          const data = [];
          
          distanceData.forEach(item => {
            labels.push(formatDate(item.date));
            data.push(parseFloat(item.value));
          });
          
          chartData.distance.labels = labels;
          chartData.distance.datasets[0].data = data;
          
          // Calculate total distance
          metrics.totalDistance = data.reduce((sum, val) => sum + val, 0).toFixed(2);
          
          // Calculate trend (simple comparison of first and last value)
          if (data.length >= 2) {
            const first = data[0];
            const last = data[data.length - 1];
            metrics.distanceTrend = ((last - first) / first * 100).toFixed(1);
          }
        }
        
        // Process Engine Hours data
        if (engineHoursRes.data?.success && engineHoursRes.data.data) {
          const engineData = engineHoursRes.data.data;
          const labels = [];
          const data = [];
          
          engineData.forEach(item => {
            labels.push(formatDate(item.date));
            data.push(parseFloat(item.hours));
          });
          
          chartData.engineHours.labels = labels;
          chartData.engineHours.datasets[0].data = data;
          
          // Calculate total engine hours
          metrics.totalEngineHours = data.reduce((sum, val) => sum + val, 0).toFixed(2);
          
          // Calculate trend (simple comparison of first and last value)
          if (data.length >= 2) {
            const first = data[0];
            const last = data[data.length - 1];
            metrics.engineHoursTrend = ((last - first) / first * 100).toFixed(1);
          }
        }
        
        // Process Activity Breakdown data
        if (activityRes.data?.success && activityRes.data.data) {
          const activityData = activityRes.data.data;
          const labels = [];
          const data = [];
          
          // Process activity breakdown data
          activityData.forEach(item => {
            labels.push(item.activity_type);
            data.push(parseFloat(item.total_hours || item.avg_percentage || 0));
          });
          
          chartData.activityBreakdown.labels = labels;
          chartData.activityBreakdown.datasets[0].data = data;
          
          // Calculate total activities (sum of all hours/percentages)
          metrics.totalActivities = data.reduce((sum, val) => sum + val, 0).toFixed(1);
        }
        
        // Process Messages data
        if (messagesRes.data?.success && messagesRes.data.data) {
          const messagesData = messagesRes.data.data;
          
          // Process by_type data for the donut chart
          if (messagesData.by_type) {
            const labels = [];
            const data = [];
            const backgroundColors = [
              '#FF6384', // Red
              '#36A2EB', // Blue
              '#FFCE56', // Yellow
              '#4BC0C0', // Teal
              '#9966FF'  // Purple
            ];
            
            messagesData.by_type.forEach((item, index) => {
              labels.push(item.message_type);
              data.push(parseInt(item.total_count));
              
              // Use predefined colors or generate random ones
              if (!chartData.activityBreakdown.datasets[0].backgroundColor[index]) {
                chartData.activityBreakdown.datasets[0].backgroundColor[index] = 
                  backgroundColors[index % backgroundColors.length];
              }
            });
            
            chartData.activityBreakdown.labels = labels;
            chartData.activityBreakdown.datasets[0].data = data;
          }
          
          // Process daily data for the messages chart
          if (messagesData.daily) {
            const labels = [];
            const alertData = [];
            const notificationData = [];
            
            // Group by date and message type
            const dailyMessages = {};
            
            messagesData.daily.forEach(item => {
              if (!dailyMessages[item.date]) {
                dailyMessages[item.date] = { alerts: 0, notifications: 0 };
              }
              
              if (item.message_type.toLowerCase().includes('alert')) {
                dailyMessages[item.date].alerts += parseInt(item.count);
              } else {
                dailyMessages[item.date].notifications += parseInt(item.count);
              }
            });
            
            // Convert to arrays for chart
            Object.entries(dailyMessages).forEach(([date, counts]) => {
              labels.push(formatDate(date));
              alertData.push(counts.alerts);
              notificationData.push(counts.notifications);
            });
            
            chartData.messagesReceived.labels = labels;
            chartData.messagesReceived.datasets = [
              {
                label: 'Alerts',
                data: alertData,
                backgroundColor: '#FF6384',
                borderRadius: 4
              },
              {
                label: 'Notifications',
                data: notificationData,
                backgroundColor: '#36A2EB',
                borderRadius: 4
              }
            ];
            
            // Calculate total messages
            metrics.totalMessages = alertData.reduce((a, b) => a + b, 0) + 
                                  notificationData.reduce((a, b) => a + b, 0);
            
            // Calculate trend (simple comparison of first and last value)
            if (alertData.length >= 2) {
              const first = alertData[0] + notificationData[0];
              const last = alertData[alertData.length - 1] + notificationData[notificationData.length - 1];
              metrics.messagesTrend = ((last - first) / (first || 1) * 100).toFixed(1);
            }
          }
        }
        
      } catch (error) {
        console.error('Error fetching dashboard data:', error);
        // You might want to add user-facing error handling here
      } finally {
        metrics.loading = false;
      }
    };
    
    // Helper function to get date range based on selected period
    const getDateRange = (range) => {
      const now = new Date();
      const start = new Date();
      
      switch (range) {
        case 'today':
          start.setHours(0, 0, 0, 0);
          break;
        case 'week':
          start.setDate(now.getDate() - 7);
          break;
        case 'month':
          start.setMonth(now.getMonth() - 1);
          break;
        case 'year':
          start.setFullYear(now.getFullYear() - 1);
          break;
        default:
          start.setDate(now.getDate() - 7); // Default to 1 week
      }
      
      return {
        startDate: start.toISOString().split('T')[0],
        endDate: now.toISOString().split('T')[0]
      };
    };
    
    // Format date for display
    const formatDate = (dateString) => {
      const options = { 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      };
      return new Date(dateString).toLocaleDateString('en-US', options);
    };
    
    const setDateRange = (range) => {
      dateRange.value = range;
      fetchDashboardData();
    };
    
    onMounted(() => {
      fetchDashboardData();
    });
    
    return {
      dateRange,
      dateRanges,
      metrics,
      chartData,
      setDateRange,
      fetchDashboardData
    }
  }
};
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --primary: #4f46e5;
  --primary-light: #eef2ff;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --gray-100: #f9fafb;
  --gray-200: #f3f4f6;
  --gray-300: #e5e7eb;
  --gray-400: #9ca3af;
  --gray-500: #6b7280;
  --gray-600: #4b5563;
  --gray-700: #374151;
  --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
  --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
  --radius-sm: 0.375rem;
  --radius: 0.5rem;
  --radius-lg: 0.75rem;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  background-color: var(--gray-100);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.dashboard {
  padding: 1.5rem;
  max-width: 1440px;
  margin: 0 auto;
  min-height: 100vh;
}

/* Header Styles */
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.header h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--gray-900);
  letter-spacing: -0.025em;
}

.date-range {
  display: flex;
  gap: 0.5rem;
  background: var(--gray-100);
  padding: 0.25rem;
  border-radius: var(--radius);
  border: 1px solid var(--gray-200);
}

.date-range button {
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  border-radius: calc(var(--radius) - 2px);
  cursor: pointer;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--gray-600);
  transition: all 0.15s ease;
}

.date-range button:hover {
  background: white;
  color: var(--gray-900);
}

.date-range button.active {
  background: white;
  color: var(--primary);
  box-shadow: var(--shadow-sm);
  font-weight: 600;
}

/* Metrics Grid */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1.25rem;
  margin-bottom: 1.5rem;
}

/* Charts Grid */
.charts-grid {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.chart-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
}

/* Chart Card */
.chart-card {
  background: white;
  padding: 1.5rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--gray-200);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.chart-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.chart-card h3 {
  margin: 0 0 1.25rem 0;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--gray-900);
  letter-spacing: -0.01em;
}

.chart-container {
  position: relative;
  height: 300px;
  width: 100%;
}

/* Responsive Adjustments */
@media (max-width: 1280px) {
  .metrics-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-row {
    grid-template-columns: 1fr;
  }
  
  .chart-container {
    height: 280px;
  }
}

@media (max-width: 768px) {
  .dashboard {
    padding: 1rem;
  }
  
  .metrics-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  .header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }
  
  .date-range {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
  }
  
  .date-range button {
    text-align: center;
    padding: 0.5rem;
    font-size: 0.8125rem;
  }
  
  .chart-card {
    padding: 1.25rem;
  }
  
  .chart-card h3 {
    font-size: 1rem;
    margin-bottom: 1rem;
  }
  
  .chart-container {
    height: 260px;
  }
}

@media (max-width: 480px) {
  .date-range {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .chart-container {
    height: 240px;
  }
}
</style>
                  return `${label}: ${value}%`;
                }
              }
            }
          }
        }
      });
    };

    const initMessagesChart = () => {
      if (messagesChart.value === null) return;
      
      // Destroy existing chart if it exists
      if (messagesChart.value.chart) {
        messagesChart.value.chart.destroy();
      }
      
      const ctx = messagesChart.value.getContext('2d');
      
      // Sample data that matches the reference image
      messagesChart.value.chart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Alert', 'Warning', 'Info', 'Error'],
          datasets: [{
            label: 'Messages',
            data: [12, 8, 5, 3],
            backgroundColor: [
              'rgba(255, 99, 132, 0.8)',
              'rgba(255, 206, 86, 0.8)',
              'rgba(54, 162, 235, 0.8)',
              'rgba(75, 192, 192, 0.8)'
            ],
            borderColor: [
              'rgba(255, 99, 132, 1)',
              'rgba(255, 206, 86, 1)',
              'rgba(54, 162, 235, 1)',
              'rgba(75, 192, 192, 1)'
            ],
            borderWidth: 1,
            borderRadius: 4,
            barThickness: 'flex',
            maxBarThickness: 30,
            minBarLength: 2
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: 'rgba(0, 0, 0, 0.8)',
              titleFont: { size: 14 },
              bodyFont: { size: 14 },
              padding: 12,
              displayColors: false,
              callbacks: {
                label: function(context) {
                  const label = context.dataset.label || '';
                  const value = context.raw || 0;
                  return `${label}: ${value}`;
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0, 0, 0, 0.05)'
              },
              ticks: {
                font: {
                  size: 12
                },
                stepSize: 5
              }
            },
            x: {
              grid: {
                display: false
              },
              ticks: {
                font: {
                  size: 12
                }
              }
            }
          }
        }
      });
    };

    return {
      // Refs
      distanceChart,
      engineHoursChart,
      activityChart,
      messagesChart,
      
      // State
      metrics,
      dateRange,
      
      // Methods
      setDateRange
    };
  }
};
</script>

<style scoped>
.dashboard {
  padding: 20px;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.header h1 {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
  color: #333;
}

.date-range button {
  padding: 8px 16px;
  margin-left: 8px;
  border: 1px solid #ddd;
  background: white;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.date-range button.active {
  background: #4CAF50;
  color: white;
  border-color: #4CAF50;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.metric-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.metric-value {
  font-size: 28px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.metric-label {
  font-size: 14px;
  color: #666;
  margin-bottom: 8px;
}

.metric-trend {
  font-size: 12px;
  display: flex;
  align-items: center;
}

.metric-trend.up {
  color: #4CAF50;
}

.metric-trend.down {
  color: #F44336;
}

.charts-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 20px;
  margin-bottom: 24px;
}

.chart-card {
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
