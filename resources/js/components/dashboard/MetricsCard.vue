<template>
  <div class="metric-card">
    <div class="metric-content">
      <div class="metric-value" :class="{ 'loading': loading }">
        <template v-if="loading">
          <div class="loading-skeleton"></div>
        </template>
        <template v-else>{{ formattedValue }}</template>
      </div>
      <div class="metric-label">{{ label }}</div>
    </div>
    <div class="metric-trend" :class="trendClass">
      <template v-if="!loading">
        <span v-if="trendValue !== null" class="trend-content">
          <i :class="trendIcon"></i>
          <span class="trend-text">{{ Math.abs(trendValue) }}% {{ trendText }}</span>
        </span>
        <span v-else class="no-data">No data</span>
      </template>
      <div v-else class="trend-skeleton"></div>
    </div>
  </div>
</template>

<script>
import { computed } from 'vue';

export default {
  name: 'MetricsCard',
  props: {
    value: {
      type: [Number, String],
      required: true
    },
    label: {
      type: String,
      required: true
    },
    trend: {
      type: Number,
      default: null
    },
    format: {
      type: String,
      default: 'number', // 'number', 'distance', 'time', 'currency'
      validator: (value) => ['number', 'distance', 'time', 'currency'].includes(value)
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  setup(props) {
    const formattedValue = computed(() => {
      const numValue = Number(props.value);
      if (isNaN(numValue)) return props.value;
      
      switch (props.format) {
        case 'distance':
          return `${numValue.toLocaleString()} km`;
        case 'time':
          return `${numValue.toLocaleString()}h`;
        case 'currency':
          return `$${numValue.toLocaleString()}`;
        default:
          return numValue.toLocaleString();
      }
    });

    const trendClass = computed(() => {
      if (props.trend === null) return '';
      return props.trend >= 0 ? 'up' : 'down';
    });

    const trendIcon = computed(() => {
      if (props.trend === null) return '';
      return props.trend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down';
    });

    const trendText = computed(() => {
      if (props.trend === null) return '';
      return props.trend >= 0 ? 'from last period' : 'from last period';
    });

    const trendValue = computed(() => {
      return props.trend !== null ? Math.round(props.trend * 10) / 10 : null;
    });

    return {
      formattedValue,
      trendClass,
      trendIcon,
      trendText,
      trendValue
    };
  }
};
</script>

<style scoped>
.metric-card {
  background: white;
  padding: 1.5rem;
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  border: 1px solid var(--gray-200);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
  position: relative;
  overflow: hidden;
}

.metric-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.metric-content {
  margin-bottom: 1rem;
}

.metric-value {
  font-size: 1.875rem;
  font-weight: 700;
  color: var(--gray-900);
  line-height: 1.2;
  margin-bottom: 0.5rem;
  min-height: 2.5rem;
  display: flex;
  align-items: center;
}

.metric-value.loading {
  color: transparent;
}

.metric-label {
  font-size: 0.875rem;
  color: var(--gray-500);
  font-weight: 500;
}

.metric-trend {
  display: flex;
  align-items: center;
  font-size: 0.8125rem;
  font-weight: 500;
  margin-top: auto;
  padding-top: 0.75rem;
  border-top: 1px solid var(--gray-100);
}

.metric-trend .trend-content {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
}

.metric-trend i {
  font-size: 0.75em;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.25em;
  height: 1.25em;
  border-radius: 50%;
  color: white;
}

.metric-trend .trend-text {
  white-space: nowrap;
}

.metric-trend.up {
  color: var(--success);
}

.metric-trend.up i {
  background-color: var(--success);
}

.metric-trend.down {
  color: var(--danger);
}

.metric-trend.down i {
  background-color: var(--danger);
  transform: rotate(180deg);
}

.metric-trend .no-data {
  color: var(--gray-400);
  font-style: italic;
}

/* Loading States */
.loading-skeleton {
  background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: var(--radius);
  height: 1.5rem;
  width: 80%;
}

.trend-skeleton {
  background: linear-gradient(90deg, var(--gray-100) 25%, var(--gray-200) 50%, var(--gray-100) 75%);
  background-size: 200% 100%;
  animation: shimmer 1.5s infinite;
  border-radius: var(--radius);
  height: 1rem;
  width: 60%;
}

@keyframes shimmer {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .metric-card {
    padding: 1.25rem;
  }
  
  .metric-value {
    font-size: 1.5rem;
  }
  
  .metric-label {
    font-size: 0.8125rem;
  }
}

@media (max-width: 480px) {
  .metric-value {
    font-size: 1.375rem;
  }
}
</style>
