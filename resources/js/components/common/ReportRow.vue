<template>
  <div class="flex items-center justify-between py-1">
    <span :class="['text-sm text-gray-500', bold ? 'font-semibold text-gray-700' : '']">{{ label }}</span>
    <span :class="['text-sm font-medium', colorClass, bold ? 'text-base font-bold' : '']">{{ value }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  label: String,
  value: String,
  color: String,
  bold:  Boolean,
});

const colorClass = computed(() => ({
  green:  'text-green-600',
  red:    'text-red-600',
  blue:   'text-blue-600',
  orange: 'text-orange-500',
  purple: 'text-purple-600',
  violet: 'text-violet-600',
  teal:   'text-teal-600',
}[props.color] ?? 'text-gray-700'));
</script>
