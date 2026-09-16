<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import BidangCard from '@/Components/BidangCard.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Search,
    Building2,
    LayoutGrid,
    Users,
    Database,
    Slice,
    SearchX,
    RotateCcw,
} from 'lucide-vue-next';

const props = defineProps({
    divisions:       { type: Array,  default: () => [] },
    groupedInstansi: { type: Array,  default: () => [] },
    instansi:        { type: Array,  default: () => [] },
    stats:           { type: Object, default: () => ({}) },
    filters:         { type: Object, default: () => ({ search: '', instansi: '', status: '', only_available: false }) },
});

const search        = ref(props.filters.search || '');
const selectedInst  = ref(props.filters.instansi || '');
const status        = ref(props.filters.status || '');
const onlyAvailable = ref(Boolean(props.filters.only_available));

// Grouped list: prefer server-side groupedInstansi, fall back to client-side derivation
const groupedList = computed(() => {
    if (props.groupedInstansi && props.groupedInstansi.length > 0) {
        return props.groupedInstansi;
    }
    const map = {};
    for (const item of props.divisions) {
        const key = item.nama_instansi || item.instansi || '—';
        if (!map[key]) {
            map[key] = { nama_instansi: key, total_bidang: 0, total_slot_tersisa: 0, total_slot_terisi: 0, divisions: [] };
        }
        map[key].divisions.push(item);
        map[key].total_bidang       += 1;
        map[key].total_slot_tersisa += Math.max(0, Number(item.kuota_sisa ?? 0));
        map[key].total_slot_terisi  += Number(item.kuota_terisi ?? 0);
    }
    return Object.values(map);
});

const totalDivisions = computed(() => props.divisions.length);

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
const layout = computed(() => (isAuthenticated.value ? AppLayout : PublicLayout));
const layoutProps = computed(() => (isAuthenticated.value ? { title: 'Katalog Bidang PKL' } : {}));

const getPrimaryCta = (item) => {
    if (isAuthenticated.value) {
        return {
            label: 'Daftar Sekarang',
            href: route('pengajuan.index', { division: item.id }),
            external: false,
        };
    }
    return {
        label: 'Daftar Sekarang',
        href: route('auth.google'),
        external: true,
    };
};

const hasActiveFilter = computed(
    () => search.value !== '' || selectedInst.value !== '' || status.value !== '' || onlyAvailable.value
);

const statusOptions = [
    { value: '', label: 'Semua Status' },
    { value: 'tersedia', label: 'Tersedia (>50% slot)' },
    { value: 'menipis', label: 'Menipis (20-50% slot)' },
    { value: 'penuh', label: 'Penuh (<20% slot)' },
];

const statCards = computed(() => [
    { label: 'Total Bidang PKL', value: props.stats.total_bidang ?? 0, icon: LayoutGrid, hint: 'Bidang yang tersedia' },
    { label: 'Total Instansi', value: props.stats.total_instansi ?? 0, icon: Building2, hint: 'Penyelenggara PKL' },
    { label: 'Total Slot Tersisa', value: props.stats.total_slot_tersisa ?? 0, icon: Users, hint: 'Akumulasi semua bidang' },
    { label: 'Total Slot Terisi', value: props.stats.total_slot_terisi ?? 0, icon: Database, hint: 'Peserta diterima' },
]);

const handleFilter = () => {
    router.get(route('katalog.index'), {
        search:         search.value || undefined,
        instansi:       selectedInst.value || undefined,
        status:         status.value || undefined,
        only_available: onlyAvailable.value ? '1' : undefined,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    search.value       = '';
    selectedInst.value = '';
    status.value       = '';
    onlyAvailable.value = false;
    router.get(route('katalog.index'), {}, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Katalog Bidang PKL" />
    <component :is="layout" v-bind="layoutProps">
        <!-- Hero -->
        <section class="bg-gradient-to-br from-forest-950 via-forest-900 to-forest-800 text-white">
            <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
                <p class="text-xs font-semibold uppercase tracking-widest text-gold-400">
                    SIAP-PKL · Dinas Komunikasi dan Informatika Provinsi Kalimantan Timur
                </p>
                <h1 class="mt-3 font-display text-3xl font-extrabold tracking-tight sm:text-4xl">
                    Katalog Bidang Praktik Kerja Lapangan
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80 sm:text-base">
                    <template v-if="isAuthenticated">
                        Jelajahi daftar bidang PKL beserta kuota dan ketersediaannya untuk mengajukan permohonan PKL Anda.
                    </template>
                    <template v-else>
                        Jelajahi daftar bidang PKL beserta kuota, jurusan yang dicari, dan ketersediaannya. Untuk mendaftar, silakan masuk terlebih dahulu menggunakan akun Google Anda.
                    </template>
                </p>
            </div>
        </section>

        <!-- Statistik -->
        <section class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="glass-panel -mt-6 p-4 shadow-lg sm:p-5">
                <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
                    <div
                        v-for="stat in statCards"
                        :key="stat.label"
                        class="flex items-center gap-3 rounded-2xl bg-white/70 p-4"
                    >
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-forest-600/10 text-forest-700">
                            <component :is="stat.icon" :size="22" :stroke-width="1.8" />
                        </div>
                        <div class="min-w-0">
                            <p class="font-display text-2xl font-extrabold leading-none text-ink-900">{{ stat.value }}</p>
                            <p class="mt-1 truncate text-xs font-medium text-ink-500">{{ stat.label }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Filter -->
        <section class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
            <form @submit.prevent="handleFilter" class="glass-panel rounded-3xl p-4 shadow-lg sm:p-5">
                <div class="grid grid-cols-1 gap-3 lg:grid-cols-[1fr_240px_260px_auto]">
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center text-ink-400">
                            <Search :size="18" :stroke-width="2" />
                        </div>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama bidang, instansi, atau posisi..."
                            class="field-input pl-10"
                        />
                    </div>

                    <label class="relative block">
                        <span class="sr-only">Filter Instansi</span>
                        <select v-model="selectedInst" class="field-input appearance-none pr-8">
                            <option value="">Semua Instansi</option>
                            <option v-for="nama in instansi" :key="nama" :value="nama">{{ nama }}</option>
                        </select>
                        <Building2 :size="16" :stroke-width="2" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-400" />
                    </label>

                    <label class="relative block">
                        <span class="sr-only">Filter Status Kuota</span>
                        <select v-model="status" class="field-input appearance-none pr-8">
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                        <Slice :size="16" :stroke-width="2" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-ink-400" />
                    </label>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="btn-primary w-full lg:max-w-none">
                            <Search :size="16" :stroke-width="2" />
                            Cari
                        </button>
                        <button
                            v-if="hasActiveFilter"
                            type="button"
                            @click="resetFilters"
                            class="btn-secondary shrink-0 px-3"
                            title="Atur ulang filter"
                        >
                            <RotateCcw :size="16" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-3 border-t border-ink-300/20 pt-3 text-xs">
                    <label class="flex items-center gap-2 cursor-pointer select-none font-medium text-ink-700">
                        <input
                            type="checkbox"
                            v-model="onlyAvailable"
                            @change="handleFilter"
                            class="rounded border-ink-300 text-forest-600 focus:ring-forest-500 h-4 w-4"
                        />
                        <span>Hanya Slot Tersedia</span>
                    </label>
                    <span class="text-ink-400">Menampilkan {{ totalDivisions }} bidang dari {{ groupedList.length }} instansi</span>
                </div>
            </form>
        </section>

        <!-- Grouped Instansi > SubInstansi Grid -->
        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <template v-if="groupedList.length > 0">
                <div
                    v-for="group in groupedList"
                    :key="group.nama_instansi"
                    class="mb-14"
                >
                    <!-- Parent Instansi Header -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b-2 border-forest-200/60 pb-4">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-forest-700/10 text-forest-800">
                                <Building2 :size="24" :stroke-width="1.8" />
                            </div>
                            <div>
                                <h2 class="font-display text-xl font-extrabold text-ink-900 sm:text-2xl">
                                    {{ group.nama_instansi }}
                                </h2>
                                <p class="mt-0.5 text-xs font-medium text-ink-500">Instansi Penyelenggara PKL</p>
                            </div>
                        </div>

                        <!-- Instansi Summary Badges -->
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-xl border border-forest-200/60 bg-forest-50 px-3 py-1.5 text-xs font-semibold text-forest-700">
                                {{ group.total_bidang }} Bidang
                            </span>
                            <span
                                v-if="group.total_slot_tersisa > 0"
                                class="rounded-xl border border-emerald-200/60 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700"
                            >
                                {{ group.total_slot_tersisa }} Slot Tersisa
                            </span>
                            <span
                                v-else
                                class="rounded-xl border border-amber-200/70 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700"
                            >
                                ✦ Kuota Penuh
                            </span>
                        </div>
                    </div>

                    <!-- SubInstansi Cards Grid -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3">
                        <BidangCard
                            v-for="item in group.divisions"
                            :key="item.id"
                            :item="item"
                            :detail-href="route('katalog.show', item.slug ?? item.id)"
                            :primary="getPrimaryCta(item)"
                        />
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div v-else class="glass-panel rounded-3xl p-14 text-center">
                <div class="mx-auto grid h-16 w-16 place-items-center rounded-2xl bg-ink-100 text-ink-400">
                    <SearchX :size="30" :stroke-width="1.8" />
                </div>
                <h2 class="mt-5 font-display text-xl font-bold text-ink-900">
                    Tidak ada bidang yang sesuai dengan pencarian Anda
                </h2>
                <p class="mx-auto mt-2 max-w-md text-sm text-ink-500">
                    Coba ubah kata kunci, ganti instansi, atau pilih status kuota lainnya.
                </p>
                <button @click="resetFilters" class="btn-secondary mt-6">
                    <RotateCcw :size="16" :stroke-width="2" />
                    Atur Ulang Filter
                </button>
            </div>
        </section>
    </component>
</template>