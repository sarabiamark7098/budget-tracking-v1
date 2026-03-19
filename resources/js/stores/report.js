import { defineStore } from 'pinia';
import { ref } from 'vue';
import { reportService } from '@/services/index';

export const useReportStore = defineStore('report', () => {
    const incomeExpenseReport = ref(null);
    const netWorth = ref(null);
    const loading = ref(false);

    async function fetchIncomeExpense(params = {}) {
        loading.value = true;
        try {
            const { data } = await reportService.getIncomeExpense(params);
            incomeExpenseReport.value = data.data;
        } finally {
            loading.value = false;
        }
    }

    async function fetchNetWorth() {
        const { data } = await reportService.getNetWorth();
        netWorth.value = data.data;
    }

    async function exportCsv(params = {}) {
        const response = await reportService.exportCsv(params);
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'report.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    async function exportPdf(params = {}) {
        const response = await reportService.exportPdf(params);
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'report.pdf');
        document.body.appendChild(link);
        link.click();
        link.remove();
    }

    return { incomeExpenseReport, netWorth, loading, fetchIncomeExpense, fetchNetWorth, exportCsv, exportPdf };
});
