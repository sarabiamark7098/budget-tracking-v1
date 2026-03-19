<template>
  <div class="bg-white rounded-xl shadow-sm p-5">
    <p class="text-sm text-gray-500 mb-1">{{ title }}</p>
    <p class="text-2xl font-bold" :class="colorClass">{{ value }}</p>
    <p v-if="subtitle" class="text-xs text-gray-400 mt-1">{{ subtitle }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  title: String,
  value: [String, Number],
  subtitle: String,
  color: { type: String, default: 'blue' },
});

const colorClass = computed(() => ({
  green: 'text-green-600',
  red: 'text-red-600',
  blue: 'text-blue-600',
  orange: 'text-orange-600',
  purple: 'text-purple-600',
}[props.color] || 'text-gray-800'));
</script>
