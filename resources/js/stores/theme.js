import { ref, watch } from 'vue';
import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', () => {
    const stored      = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark      = ref(stored ? stored === 'dark' : prefersDark);

    function applyTheme(dark) {
        document.documentElement.classList.toggle('dark', dark);
    }

    function toggle() {
        isDark.value = !isDark.value;
    }

    watch(isDark, (val) => {
        localStorage.setItem('theme', val ? 'dark' : 'light');
        applyTheme(val);
    }, { immediate: true });

    return { isDark, toggle };
});
