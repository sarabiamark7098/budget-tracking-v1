<template>
  <div class="relative inline-flex items-center justify-center" :style="{ width: size + 'px', height: size + 'px' }">
    <svg :viewBox="`0 0 36 36`" :width="size" :height="size" class="block -rotate-90">
      <!-- Background ring -->
      <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#F3F4F6" stroke-width="3.8" />
      <!-- Empty state ring -->
      <circle v-if="!processedSegments.length"
        cx="18" cy="18" r="15.9155" fill="none"
        stroke="#E5E7EB" stroke-width="3.8"
        stroke-dasharray="100 0"
      />
      <!-- Data segments -->
      <circle
        v-for="(seg, i) in processedSegments"
        :key="i"
        cx="18" cy="18"
        r="15.9155"
        fill="none"
        :stroke="seg.color"
        stroke-width="3.8"
        stroke-linecap="butt"
        :stroke-dasharray="`${seg.dash < 0.5 ? 0 : seg.dash} 100`"
        :stroke-dashoffset="seg.offset"
        style="transition: stroke-dasharray 0.4s ease"
      />
    </svg>
    <!-- Center slot -->
    <div class="absolute inset-0 flex flex-col items-center justify-center text-center pointer-events-none rotate-0">
      <slot />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  // [{ label, value, color }]
  segments: { type: Array, default: () => [] },
  size:     { type: Number, default: 160 },
});

// circumference = 2π × 15.9155 ≈ 100, so percentages map 1:1 to dash lengths
const processedSegments = computed(() => {
  const total = props.segments.reduce((s, seg) => s + (seg.value || 0), 0);
  if (!total) return [];

  let cumulative = 0;
  return props.segments.map(seg => {
    const pct    = (seg.value / total) * 100;
    const offset = -cumulative;   // negative = shift forward (clockwise)
    cumulative  += pct;
    return { ...seg, dash: pct, offset };
  });
});
</script>
