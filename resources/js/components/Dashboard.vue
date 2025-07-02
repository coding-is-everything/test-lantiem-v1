<template>
  <div class="dashboard">
    <div class="header">
      <h1>Dashboard</h1>
      <div class="date-range">
        <button v-for="range in dateRanges" :key="range.value" @click="setDateRange(range.value)"
          :class="{ active: dateRange === range.value }">
          {{ range.label }}
        </button>
      </div>
    </div>

    <!-- Metrics Grid -->
    <div class="metrics-grid">
      <MetricsCard :value="metrics.totalDistance" label="Total Distance" :trend="metrics.distanceTrend"
        format="distance" :loading="metrics.loading" />

      <MetricsCard :value="metrics.totalEngineHours" label="Engine Hours" :trend="metrics.engineHoursTrend"
        format="time" :loading="metrics.loading" />

      <MetricsCard :value="metrics.totalMessages" label="Messages" :trend="metrics.messagesTrend"
        :loading="metrics.loading" />

      <MetricsCard :value="metrics.totalActivities" label="Total Activities" :trend="metrics.activitiesTrend"
        :loading="metrics.loading" />
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <div class="chart-row">
        <DistanceChart :chart-data="chartData.distance" :date-range="dateRange" />
        <EngineHoursChart :chart-data="chartData.engineHours" :date-range="dateRange" />
      </div>

      <div class="chart-row">
        <ActivityBreakdownChart :chart-data="chartData.activityBreakdown" :date-range="dateRange" />
        <MessagesReceivedChart :chart-data="chartData.messagesReceived" :date-range="dateRange" />
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

    const dateRanges = reactive([
      { label: 'Today', value: 'today' },
      { label: 'Week', value: 'week' },
      { label: 'Month', value: 'month' },
      { label: 'Year', value: 'year' }
    ]);

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

    // Helper function to handle API errors
    const handleApiError = (endpoint) => (error) => {
      console.error(`Error fetching ${endpoint} data:`, error);
      // Return appropriate empty response based on endpoint
      const emptyResponses = {
        'distance': { data: { success: false, data: [] } },
        'engine-hours': { data: { success: false, data: [] } },
        'activity': { data: { success: false, data: [] } },
        'messages': { data: { success: false, data: { by_type: [], daily: [] } } }
      };
      return emptyResponses[endpoint] || { data: { success: false, data: [] } };
    };

    // Process distance data from API response
    const processDistanceData = (response) => {
      try {
        if (response?.data?.success && response.data.data) {
          const distanceData = response.data.data;
          const labels = [];
          const data = [];

          // Reset metrics first
          metrics.totalDistance = 0;
          metrics.distanceTrend = 0;

          distanceData.forEach(item => {
            if (item && item.date) {
              // Format and add the date label
              labels.push(formatDate(item.date));

              // Safely parse the value, defaulting to 0 if invalid
              let value = 0;
              if (typeof item.value === 'number') {
                value = item.value;
              } else if (typeof item.value === 'string') {
                value = parseFloat(item.value) || 0;
              }

              // Ensure value is a valid number
              data.push(Math.max(0, Number.isFinite(value) ? value : 0));
            }
          });

          // Update chart data
          chartData.distance.labels = [...labels];
          chartData.distance.datasets[0].data = [...data];

          // Calculate total distance
          if (data.length > 0) {
            // Use reduce with proper type checking
            const total = data.reduce((sum, val) => {
              const num = Number(val) || 0;
              return sum + (Number.isFinite(num) ? num : 0);
            }, 0);

            // Ensure total is a valid number
            metrics.totalDistance = Number.isFinite(total) ? parseFloat(total.toFixed(2)) : 0;

            // Calculate trend (simple comparison of first and last value)
            if (data.length >= 2) {
              const first = Number(data[0]) || 0;
              const last = Number(data[data.length - 1]) || 0;
              const trend = first !== 0 ? ((last - first) / first * 100) : 0;
              metrics.distanceTrend = Number.isFinite(trend) ? parseFloat(trend.toFixed(1)) : 0;
            } else {
              metrics.distanceTrend = 0;
            }
          } else {
            metrics.totalDistance = 0;
            metrics.distanceTrend = 0;
          }

          console.log('Processed distance data:', {
            data,
            total: metrics.totalDistance,
            trend: metrics.distanceTrend,
            rawResponse: response.data.data
          });

          return true;
        } else {
          console.error('Invalid response format in processDistanceData:', response);
          metrics.totalDistance = 0;
          metrics.distanceTrend = 0;
          return false;
        }
      } catch (error) {
        console.error('Error processing distance data:', error);
        metrics.totalDistance = 0;
        metrics.distanceTrend = 0;
        return false;
      }
    };

    // Process engine hours data from API response
    const processEngineHoursData = (response) => {
      try {
        if (response?.data?.success && response.data.data) {
          const engineData = response.data.data;
          const labels = [];
          const data = [];

          engineData.forEach(item => {
            if (item && item.date && (item.hours !== undefined || item.value !== undefined)) {
              labels.push(formatDate(item.date));
              const value = parseFloat(item.hours || item.value);
              data.push(isNaN(value) ? 0 : value);
            }
          });

          chartData.engineHours.labels = [...labels];
          chartData.engineHours.datasets[0].data = [...data];

          // Calculate total engine hours
          if (data.length > 0) {
            const total = data.reduce((sum, val) => sum + (Number(val) || 0), 0);
            metrics.totalEngineHours = !isNaN(total) ? parseFloat(total.toFixed(1)) : 0;

            // Calculate trend (simple comparison of first and last value)
            if (data.length >= 2) {
              const first = Number(data[0]) || 0;
              const last = Number(data[data.length - 1]) || 0;
              const trend = first !== 0 ? ((last - first) / first * 100) : 0;
              metrics.engineHoursTrend = !isNaN(trend) ? parseFloat(trend.toFixed(1)) : 0;
            } else {
              metrics.engineHoursTrend = 0;
            }
          } else {
            metrics.totalEngineHours = 0;
            metrics.engineHoursTrend = 0;
          }
          return true;
        }
      } catch (error) {
        console.error('Error processing engine hours data:', error);
        metrics.totalEngineHours = 0;
        metrics.engineHoursTrend = 0;
      }
      return false;
    };

    // Process activity data from API response
    const processActivityData = (response) => {
      if (response?.data?.success && response.data.data) {
        const activityData = response.data.data;
        const labels = [];
        const data = [];

        // Process activity breakdown data
        activityData.forEach(item => {
          labels.push(item.activity_type);
          data.push(parseFloat(item.total_hours || item.avg_percentage || 0));
        });

        chartData.activityBreakdown.labels = [...labels];
        chartData.activityBreakdown.datasets[0].data = [...data];

        // Calculate total activities (sum of all hours/percentages)
        if (data.length > 0) {
          metrics.totalActivities = data.reduce((sum, val) => sum + val, 0).toFixed(1);
        }
      }
    };

    // Process messages data from API response
    const processMessagesData = (response) => {
      if (response?.data?.success && response.data.data) {
        const messagesData = response.data.data;

        // Handle different response formats
        if (messagesData.by_type && messagesData.daily) {
          // Process by_type data
          const alertData = [];
          const notificationData = [];
          const labels = [];

          // Process daily data
          messagesData.daily.forEach(item => {
            labels.push(formatDate(item.date));

            // Find corresponding alert and notification counts
            const alertItem = messagesData.by_type.find(x => x.date === item.date && x.type === 'alert');
            const notificationItem = messagesData.by_type.find(x => x.date === item.date && x.type === 'notification');

            alertData.push(alertItem ? parseInt(alertItem.count) : 0);
            notificationData.push(notificationItem ? parseInt(notificationItem.count) : 0);
          });

          // Update chart data
          chartData.messages.labels = [...labels];
          chartData.messages.datasets[0].data = [...alertData];
          chartData.messages.datasets[1].data = [...notificationData];

          // Calculate total messages
          metrics.totalAlerts = alertData.reduce((sum, val) => sum + val, 0);
          metrics.totalNotifications = notificationData.reduce((sum, val) => sum + val, 0);
        } else if (Array.isArray(messagesData)) {
          // Fallback for simpler response format
          const alertData = [];
          const notificationData = [];
          const labels = [];

          messagesData.forEach(item => {
            labels.push(formatDate(item.date));
            alertData.push(parseInt(item.alerts || 0));
            notificationData.push(parseInt(item.notifications || 0));
          });

          chartData.messages.labels = [...labels];
          chartData.messages.datasets[0].data = [...alertData];
          chartData.messages.datasets[1].data = [...notificationData];

          metrics.totalAlerts = alertData.reduce((sum, val) => sum + val, 0);
          metrics.totalNotifications = notificationData.reduce((sum, val) => sum + val, 0);
        }
      }
    };

    // Reset all chart data to initial state
    const resetChartData = () => {
      // Create a deep copy of the initial chart data structure
      const initialChartData = {
        distance: {
          labels: [],
          datasets: [{
            label: 'Distance (km)',
            data: [],
            borderColor: '#4CAF50',
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
          }]
        },
        engineHours: {
          labels: [],
          datasets: [{
            label: 'Engine Hours',
            data: [],
            backgroundColor: '#2196F3',
            borderRadius: 4
          }]
        },
        activityBreakdown: {
          labels: [],
          datasets: [{
            data: [],
            backgroundColor: ['#4CAF50', '#2196F3', '#FFC107', '#FF5722', '#9C27B0'],
            borderWidth: 1
          }]
        },
        messages: {
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
      };

      // Reset the chart data
      Object.keys(chartData).forEach(key => {
        if (initialChartData[key]) {
          chartData[key] = JSON.parse(JSON.stringify(initialChartData[key]));
        }
      });
    };

    const formatDateForAPI = (date) => {
      // Format date as YYYY-MM-DDTHH:mm:ss for API
      const pad = (num) => String(num).padStart(2, '0');
      const year = date.getFullYear();
      const month = pad(date.getMonth() + 1);
      const day = pad(date.getDate());
      const hours = pad(date.getHours());
      const minutes = pad(date.getMinutes());
      const seconds = pad(date.getSeconds());

      return `${year}-${month}-${day}T${hours}:${minutes}:${seconds}`;
    };

    const logApiCall = (endpoint, params, response) => {
      try {
        const logEntry = `=== API Call: ${endpoint} (${new Date().toISOString()}) ===\n` +
          `Params: ${JSON.stringify(params, null, 2)}\n` +
          `Response Status: ${response?.status || 'N/A'}\n` +
          `Response Data: ${JSON.stringify(response?.data || {}, null, 2)}\n\n`;

        console.log(`API Call to ${endpoint}:`, { params, response });

        // Save to file
        const blob = new Blob([logEntry], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'api-calls-log.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      } catch (error) {
        console.error('Error in logApiCall:', error);
      }
    };

    const fetchDashboardData = async () => {
      try {
        metrics.loading = true;

        // Reset all chart data
        resetChartData();

        // Reset metrics
        Object.keys(metrics).forEach(key => {
          if (key !== 'loading') {
            metrics[key] = 0;
          }
        });

        // Calculate date range based on selected period
        const { startDate, endDate } = getDateRange(dateRange.value);

        // Convert dates to local timezone strings for API
        const startDateObj = new Date(startDate);
        const endDateObj = new Date(endDate);

        // Format dates for API request
        const formattedStartDate = formatDateForAPI(startDateObj);
        const formattedEndDate = formatDateForAPI(endDateObj);

        const apiParams = {
          start_date: formattedStartDate,
          end_date: formattedEndDate,
          timezone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };

        console.log('Fetching data for range:', {
          ...apiParams,
          period: dateRange.value,
          now: new Date().toISOString()
        });

        // Fetch all data in parallel with timeout
        const API_TIMEOUT = 30000; // 30 seconds
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), API_TIMEOUT);

        const axiosConfig = {
          params: { ...apiParams },
          signal: controller.signal,
          headers: {
            'Cache-Control': 'no-cache',
            'Pragma': 'no-cache'
          }
        };

        // Add a timestamp to prevent caching
        const timestamp = new Date().getTime();
        const endpoints = [
          { url: `/api/distance/chart-data?_=${timestamp}`, name: 'distance' },
          { url: `/api/engine-hours/chart-data?_=${timestamp}`, name: 'engine-hours' },
          { url: `/api/activity-breakdown/chart-data?_=${timestamp}`, name: 'activity' },
          { url: `/api/messages-received/chart-data?_=${timestamp}`, name: 'messages' }
        ];

        const requests = endpoints.map(async (endpoint) => {
          try {
            const response = await axios.get(endpoint.url, axiosConfig);
            logApiCall(endpoint.name, apiParams, response);
            return response;
          } catch (error) {
            const errorResponse = handleApiError(endpoint.name)(error);
            logApiCall(endpoint.name, apiParams, {
              error: error.message,
              response: errorResponse
            });
            return errorResponse;
          }
        });

        const [distanceRes, engineHoursRes, activityRes, messagesRes] = await Promise.all(requests);
        clearTimeout(timeoutId);

        // Process responses
        await Promise.all([
          processDistanceData(distanceRes),
          processEngineHoursData(engineHoursRes),
          processActivityData(activityRes),
          processMessagesData(messagesRes)
        ]);

        // Process Engine Hours data
        if (engineHoursRes?.data?.success && engineHoursRes.data.data) {
          const engineData = engineHoursRes.data.data;
          const labels = [];
          const data = [];

          engineData.forEach(item => {
            if (item && item.date) {
              labels.push(formatDate(item.date));
              data.push(parseFloat(item.hours) || 0);
            }
          });

          if (chartData.engineHours?.datasets?.[0]) {
            chartData.engineHours.labels = [...labels];
            chartData.engineHours.datasets[0].data = [...data];

            // Calculate total engine hours
            if (data.length > 0) {
              const total = data.reduce((sum, val) => sum + (Number(val) || 0), 0);
              metrics.totalEngineHours = Number.isFinite(total) ? parseFloat(total.toFixed(2)) : 0;

              // Calculate trend (simple comparison of first and last value)
              if (data.length >= 2) {
                const first = Number(data[0]) || 0;
                const last = Number(data[data.length - 1]) || 0;
                const trend = first !== 0 ? ((last - first) / first * 100) : 0;
                metrics.engineHoursTrend = Number.isFinite(trend) ? parseFloat(trend.toFixed(1)) : 0;
              } else {
                metrics.engineHoursTrend = 0;
              }
            } else {
              metrics.totalEngineHours = 0;
              metrics.engineHoursTrend = 0;
            }
          }
        }

      } catch (error) {
        console.error('Error in fetchDashboardData:', error);
        // Show error to user
        if (error.name === 'AbortError') {
          alert('Request timed out. Please try again.');
        } else {
          alert('Failed to load dashboard data. Please try again later.');
        }
      } finally {
        metrics.loading = false;
      }

      // Process Activity Breakdown data
      if (activityRes?.data?.success && activityRes.data.data) {
        try {
          const activityData = activityRes.data.data;
          const labels = [];
          const data = [];

          // Process activity breakdown data
          if (Array.isArray(activityData)) {
            activityData.forEach(item => {
              if (item && item.activity_type) {
                labels.push(String(item.activity_type));
                data.push(Number(item.total_hours || item.avg_percentage || 0));
              }
            });

            if (chartData.activityBreakdown?.datasets?.[0]) {
              chartData.activityBreakdown.labels = [...labels];
              chartData.activityBreakdown.datasets[0].data = [...data];

              // Calculate total activities (sum of all hours/percentages)
              const total = data.reduce((sum, val) => sum + (Number(val) || 0), 0);
              metrics.totalActivities = Number.isFinite(total) ? parseFloat(total.toFixed(1)) : 0;
            }
          }
        } catch (error) {
          console.error('Error processing activity data:', error);
          metrics.totalActivities = 0;
        }
      }

      // Process Messages data
      if (messagesRes?.data?.success && messagesRes.data.data) {
        try {
          const messagesData = messagesRes.data.data;

          // Process by_type data for the donut chart
          if (messagesData.by_type && Array.isArray(messagesData.by_type)) {
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
              if (item && item.message_type) {
                labels.push(String(item.message_type));
                data.push(Number(item.total_count) || 0);

                // Ensure dataset exists and has backgroundColor array
                if (chartData.activityBreakdown?.datasets?.[0]) {
                  if (!chartData.activityBreakdown.datasets[0].backgroundColor) {
                    chartData.activityBreakdown.datasets[0].backgroundColor = [];
                  }
                  // Use predefined colors or generate random ones
                  if (!chartData.activityBreakdown.datasets[0].backgroundColor[index]) {
                    chartData.activityBreakdown.datasets[0].backgroundColor[index] =
                      backgroundColors[index % backgroundColors.length];
                  }
                }
              }
            });

            if (chartData.activityBreakdown?.datasets?.[0]) {
              chartData.activityBreakdown.labels = [...labels];
              chartData.activityBreakdown.datasets[0].data = [...data];
            }
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
        } catch (error) {
          console.error('Error fetching dashboard data:', error);
          // You might want to add user-facing error handling here
        } finally {
          metrics.loading = false;
        }
      }
    };

    // Helper function to get date range based on selected period
    const getDateRange = (range) => {
      // Create dates in local timezone
      const now = new Date();
      const end = new Date(now);
      const start = new Date(now);

      // Set time to start of day (00:00:00.000)
      start.setHours(0, 0, 0, 0);

      switch (range) {
        case 'today':
          // Set end to end of day (23:59:59.999)
          end.setHours(23, 59, 59, 999);
          break;

        case 'week':
          // Set to start of the current week (Monday)
          const day = start.getDay();
          const diff = start.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
          start.setDate(diff);
          // Keep end time as current time for today
          break;

        case 'month':
          // Set to first day of current month at 00:00:00.000
          start.setDate(1);
          start.setHours(0, 0, 0, 0);
          // Keep end time as current time for today
          break;

        case 'year':
          // Set to first day of current year at 00:00:00.000
          start.setMonth(0, 1);
          start.setHours(0, 0, 0, 0);
          // Keep end time as current time for today
          break;

        default:
          // Default to last 7 days
          start.setDate(end.getDate() - 7);
          start.setHours(0, 0, 0, 0);
      }

      // Format dates to YYYY-MM-DD
      const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      };

      // Ensure end date is not in the future
      const today = new Date();
      const effectiveEndDate = end > today ? today : end;

      return {
        startDate: formatDate(start),
        endDate: formatDate(effectiveEndDate)
      };
    };

    /**
     * Format date for display
     * @param {Date|string} dateInput - Date to format
     * @returns {string} Formatted date string
     */
    const formatDate = (dateInput) => {
      try {
        // Handle both Date objects and date strings
        const date = dateInput instanceof Date ? new Date(dateInput) : new Date(dateInput);

        // Return empty string for invalid dates
        if (isNaN(date.getTime())) return '';

        const options = {
          month: 'short',
          day: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
          hour12: false,
          timeZone: Intl.DateTimeFormat().resolvedOptions().timeZone
        };

        return date.toLocaleDateString('en-US', options);
      } catch (error) {
        console.error('Error formatting date:', error, dateInput);
        return '';
      }
    };

    /**
     * Set the date range and trigger data refresh
     * @param {'today'|'week'|'month'|'year'} range - The date range to set
     */
    const setDateRange = (range) => {
      try {
        if (!['today', 'week', 'month', 'year'].includes(range)) {
          console.warn(`Invalid date range: ${range}. Using 'today'.`);
          range = 'today';
        }

        if (dateRange.value !== range) {
          dateRange.value = range;

          // Reset metrics and chart data
          metrics.loading = true;
          resetChartData();

          // Fetch new data for the selected range
          fetchDashboardData();
        }
      } catch (error) {
        console.error('Error in setDateRange:', error);
        metrics.loading = false;
      }
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
            label: function (context) {
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

return {
  // Refs
  DistanceChart,
  EngineHoursChart,
  activityChart,
  messagesChart,

  // State
  metrics,
  dateRange,
  dateRanges,

  // Methods
  setDateRange,

  // Chart refs
  distanceChart,
  engineHoursChart,
  activityChart,
  messagesChart
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
