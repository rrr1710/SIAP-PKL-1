<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getStatusLabel, getStatusBadgeClass } from '@/utils/statusLabel';

const props = defineProps({
    stats: { type: Object, default: () => ({}) },
    pengajuanTerbaru: { type: Array, default: () => [] },
    bidangAktif: { type: Array, default: () => [] },
});

const stats = computed(() => props.stats);
const pengajuanTerbaru = computed(() => props.pengajuanTerbaru.map((item) => ({
    ...item,
    nama: item.nama ?? item.nama_pemohon ?? item.user?.name ?? '-',
    bidang: item.bidang ?? item.posisi ?? item.division?.nama ?? '-',
    posisi: item.bidang ?? item.posisi ?? item.division?.nama ?? '-',
    tanggal: item.created_at,
})));
const bidangAktif = computed(() => props.bidangAktif.map((item) => ({
    ...item,
    nama: item.nama ?? item.nama_sub_instansi ?? 'Bidang #' + item.id,
    terisi: Number(item.terisi ?? 0),
    kuota: Number(item.kuota ?? item.batas_kuota ?? 0),
})));
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout title="Dashboard Admin">
        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-ink-900">Selamat Datang, Admin Instansi</h2>
            <p class="mt-1 text-sm text-ink-500">Ringkasan aktivitas PKL hari ini.</p>
        </div>

        <!-- 4 Stat Cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="glass-card p-5 relative overflow-hidden">
                <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Total Bidang</p>
                <p class="mt-3 font-display text-3xl font-bold text-ink-900">{{ stats.total_bidang ?? 0 }}</p>
            </div>

            <div class="glass-card p-5 relative overflow-hidden">
                <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Pengajuan Baru</p>
                <p class="mt-3 font-display text-3xl font-bold text-ink-900">{{ stats.pengajuan_baru ?? 0 }}</p>
            </div>

            <div class="glass-card p-5 relative overflow-hidden">
                <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Menunggu Verifikasi</p>
                <p class="mt-3 font-display text-3xl font-bold text-gold-500">{{ stats.menunggu_verifikasi ?? 0 }}</p>
            </div>

            <div class="glass-card p-5 relative overflow-hidden">
                <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Peserta Aktif</p>
                <p class="mt-3 font-display text-3xl font-bold text-status-success">{{ stats.peserta_aktif ?? 0 }}</p>
            </div>
        </div>

        <!-- 2 Columns Below -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Left: Pengajuan Terbaru -->
            <div class="glass-panel p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-display text-base font-bold text-ink-900">Pengajuan Terbaru</h3>
                    <Link :href="route('admin.pengajuan.index')" class="text-xs font-semibold text-forest-700 hover:underline">Lihat semua</Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[480px] text-left text-sm">
                        <thead class="border-b border-ink-300/35 text-xs uppercase tracking-wider text-ink-500">
                            <tr>
                                <th class="px-3 py-2">Nama</th>
                                <th class="px-3 py-2">Bidang</th>
                                <th class="px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-300/20">
                            <tr v-for="item in pengajuanTerbaru" :key="item.id" class="transition hover:bg-forest-50/60">
                                <td class="px-3 py-3 font-medium text-ink-900">{{ item.nama }}</td>
                                <td class="px-3 py-3 text-ink-700">{{ item.bidang }}</td>
                                <td class="px-3 py-3">
                                    <span :class="getStatusBadgeClass(item.status)" class="badge">
                                        {{ getStatusLabel(item.status) }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="pengajuanTerbaru.length === 0">
                                <td colspan="3" class="px-3 py-8 text-center text-xs text-ink-400">
                                    Belum ada pengajuan terbaru.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right: Bidang Aktif -->
            <div class="glass-panel p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="font-display text-base font-bold text-ink-900">Bidang Aktif</h3>
                    <Link :href="route('admin.bidang.index')" class="text-xs font-semibold text-forest-700 hover:underline">Lihat semua</Link>
                </div>

                <ul class="space-y-4">
                    <li v-for="item in bidangAktif" :key="item.id" class="rounded-xl border border-ink-300/40 bg-white/50 p-4 shadow-sm">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-ink-900">{{ item.nama }}</p>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium text-ink-600">
                                    {{ item.terisi }} / {{ item.kuota }} terisi
                                </span>
                                <span v-if="item.kuota > 0 && item.terisi > item.kuota" class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-800">
                                    +{{ item.terisi - item.kuota }} manual
                                </span>
                            </div>
                        </div>
                        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-ink-100">
                            <div
                                class="h-full rounded-full transition-all"
                                :class="item.kuota > 0 && item.terisi > item.kuota ? 'bg-amber-500' : 'bg-forest-500'"
                                :style="{ width: `${item.kuota > 0 ? Math.min(100, Math.round((item.terisi / item.kuota) * 100)) : (item.terisi > 0 ? 100 : 0)}%` }"
                            />
                        </div>
                    </li>
                    <li v-if="bidangAktif.length === 0" class="py-6 text-center text-xs text-ink-400">
                        Belum ada bidang aktif.
                    </li>
                </ul>
            </div>
        </div>
    </AdminLayout>
</template>
