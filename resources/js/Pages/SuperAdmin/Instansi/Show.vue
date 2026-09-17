<script setup>
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    instansi: { type: Object, default: () => ({}) },
    bidangPkl: { type: Array, default: () => [] },
    adminList: { type: Array, default: () => [] },
});

const tipeBadge = () => props.instansi?.tipe === 'pemerintah' ? 'badge-info' : 'badge-warning';
const tipeLabel = () => props.instansi?.tipe === 'pemerintah' ? 'Pemerintah' : 'Swasta';

const statusBadge = (status) => {
    return status === 'aktif' ? 'badge-success' : status === 'penuh' ? 'badge-warning' : 'badge-danger';
};

const statusLabel = (status) => {
    return status === 'aktif' ? 'Aktif' : status === 'penuh' ? 'Penuh' : 'Nonaktif';
};
</script>

<template>
    <Head title="Detail Instansi" />
    <SuperAdminLayout title="Detail Instansi">
        <div class="mb-4">
            <Link :href="route('superadmin.instansi.index')" class="inline-flex items-center gap-1 text-sm font-medium text-forest-700 hover:underline">
                <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Kembali ke Manajemen Instansi
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left: Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Info Lengkap -->
                <div class="glass-panel p-6 sm:p-8">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex min-w-0 items-center gap-4">
                            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-forest-600/10 text-forest-700">
                                <svg viewBox="0 0 24 24" class="h-7 w-7" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"/><path d="M9 22v-4h6v4"/><path d="M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M8 10h.01M16 10h.01M8 14h.01M16 14h.01"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="font-display text-xl sm:text-2xl font-bold text-ink-900">{{ instansi.nama }}</h2>
                                <p class="mt-1 text-sm text-ink-500">Terdaftar sejak {{ instansi.created_at }}</p>
                            </div>
                        </div>
                        <span :class="tipeBadge()" class="badge w-fit shrink-0">
                            {{ tipeLabel() }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Alamat</p>
                            <p class="mt-1 font-medium text-ink-900">{{ instansi.alamat }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Email Kontak</p>
                            <p class="mt-1 text-ink-800">{{ instansi.email }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-xs font-medium text-ink-500 uppercase tracking-wider">Deskripsi</p>
                            <p class="mt-1 text-sm text-ink-800 leading-relaxed">{{ instansi.deskripsi }}</p>
                        </div>
                    </div>
                </div>

                <!-- List Bidang PKL -->
                <div class="glass-panel p-6">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-ink-900">Bidang PKL</h3>
                        <span class="badge badge-info">{{ bidangPkl.length }} Bidang</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[560px] text-left text-sm">
                            <thead class="border-b border-ink-300/35 text-xs uppercase tracking-wider text-ink-500">
                                <tr>
                                    <th class="px-4 py-3">No</th>
                                    <th class="px-4 py-3">Nama Bidang</th>
                                    <th class="px-4 py-3">Kuota</th>
                                    <th class="px-4 py-3">Terisi</th>
                                    <th class="px-4 py-3">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-300/20">
                                <tr v-for="(bidang, index) in bidangPkl" :key="bidang.id" class="transition hover:bg-forest-50/60">
                                    <td class="px-4 py-3.5 text-ink-500">{{ index + 1 }}</td>
                                    <td class="px-4 py-3.5 font-semibold text-ink-900">{{ bidang.nama }}</td>
                                    <td class="px-4 py-3.5 text-ink-700">{{ bidang.kuota }}</td>
                                    <td class="px-4 py-3.5 text-ink-700">{{ bidang.terisi }}</td>
                                    <td class="px-4 py-3.5">
                                        <span :class="statusBadge(bidang.status)" class="badge">
                                            {{ statusLabel(bidang.status) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Sidebar -->
            <div class="space-y-6">
                <!-- Aksi -->
                <div class="glass-card p-6 space-y-4 h-fit">
                    <h3 class="font-display text-base font-bold text-ink-900 border-b border-ink-300/30 pb-3">
                        Aksi Instansi
                    </h3>
                    <Link :href="route('superadmin.undangan.index')" class="btn-primary w-full text-center text-sm">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" />
                        </svg>
                        Undang Admin Baru untuk Instansi Ini
                    </Link>
                </div>

                <!-- Admin Terdaftar -->
                <div class="glass-card p-6">
                    <div class="mb-4 border-b border-ink-300/30 pb-3 flex items-center justify-between">
                        <h3 class="font-display text-base font-bold text-ink-900">Admin Instansi</h3>
                        <span class="badge badge-info">{{ adminList.length }} Admin</span>
                    </div>
                    <ul class="space-y-4">
                        <li v-for="admin in adminList" :key="admin.id" class="rounded-xl border border-ink-300/40 bg-white/50 p-4 shadow-sm">
                            <div class="flex items-center justify-between gap-2">
                                <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-forest-700 text-xs font-semibold text-white">
                                    {{ admin.nama.split(' ').map((w) => w[0]).slice(0, 2).join('') }}
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-ink-900">{{ admin.nama }}</p>
                                    <p class="truncate text-xs text-ink-500">{{ admin.email }}</p>
                                </div>
                                <span :class="admin.oauth_connected ? 'badge-success' : 'badge-warning'" class="badge text-[10px]">
                                    {{ admin.oauth_connected ? 'Terhubung (Google)' : 'Belum Login / Draft' }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
