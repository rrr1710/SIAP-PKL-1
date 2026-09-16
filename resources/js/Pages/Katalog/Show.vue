<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const layout = computed(() => (user.value ? AppLayout : PublicLayout));
const layoutProps = computed(() => (user.value ? { title: props.division.nama } : {}));

const props = defineProps({
    division: { type: Object, required: true },
});

const isFull = computed(() => {
    const sisa = Number(props.division.sisa_total ?? props.division.kuota_sisa ?? 0);
    const terisi = Number(props.division.terisi_total ?? props.division.kuota_terisi ?? 0);
    const kuota = Number(props.division.kuota_total ?? props.division.batas_kuota ?? 0);
    return sisa <= 0 || (kuota > 0 && terisi >= kuota);
});

const statusMeta = (division) => {
    const sisa = Number(division.sisa_total ?? division.kuota_sisa ?? 0);
    const terisi = Number(division.terisi_total ?? division.kuota_terisi ?? 0);
    const quota = Number(division.kuota_total ?? division.batas_kuota ?? 0);

    if (sisa <= 0 || (quota > 0 && terisi >= quota)) {
        return {
            label: 'Kuota Penuh',
            badge: 'bg-amber-100 text-amber-700 font-semibold',
            bar: 'bg-ink-400',
        };
    }

    return { label: 'Buka / Tersedia', badge: 'badge-success', bar: 'bg-status-success' };
};
</script>

<template>
    <Head :title="props.division.nama" />
    <component :is="layout" v-bind="layoutProps">
        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 sm:py-10">
            <!-- Breadcrumb -->
            <nav class="mb-6 flex items-center gap-2 text-sm text-ink-500">
                <Link :href="route('katalog.index')" class="hover:text-forest-700 hover:underline">Katalog Bidang PKL</Link>
                <span>&gt;</span>
                <span class="font-medium text-ink-900">Detail Bidang</span>
            </nav>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left Column (2/3 width) -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Bidang Header -->
                    <div class="glass-panel p-6 sm:p-8">
                        <div class="flex items-start gap-4">
                            <div class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-forest-600/10 text-forest-700">
                                <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                                    <path d="M16 21V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v16"/>
                                </svg>
                            </div>
                            <div>
                                <h1 class="font-display text-2xl font-bold text-ink-900">{{ props.division.nama }}</h1>
                                <p class="mt-1 text-sm font-medium text-forest-700">{{ props.division.instansi }}</p>
                            </div>
                        </div>
                        <p class="mt-5 text-sm leading-relaxed text-ink-700">
                            {{ props.division.deskripsi }}
                        </p>
                    </div>

                    <!-- Posisi PKL Tersedia -->
                    <div>
                        <h2 class="mb-4 font-display text-xl font-bold text-ink-900">Posisi PKL Tersedia</h2>

                        <div class="space-y-5">
                            <div v-for="(posisi, idx) in props.division.positions" :key="posisi.id" class="glass-panel p-6 sm:p-7">
                                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-ink-300/30">
                                    <div class="flex items-center gap-3">
                                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-forest-600/10 text-sm font-bold text-forest-700">
                                            {{ idx + 1 }}
                                        </span>
                                        <h3 class="font-display text-lg font-bold text-ink-900">{{ posisi.nama }}</h3>
                                    </div>
                                    <span v-if="posisi.terisi >= posisi.kuota" class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                                        Kuota Penuh
                                    </span>
                                    <span v-else class="badge badge-success font-medium">
                                        Kuota {{ posisi.terisi }}/{{ posisi.kuota }}
                                    </span>
                                </div>

                                <p class="mt-4 text-sm leading-relaxed text-ink-700">{{ posisi.deskripsi }}</p>

                                <!-- Jurusan yang Diutamakan -->
                                <div v-if="posisi.jurusan?.length" class="mt-5">
                                    <h4 class="text-xs font-semibold uppercase tracking-wider text-ink-500 mb-2">Jurusan yang Diutamakan</h4>
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="(jur, jidx) in posisi.jurusan"
                                            :key="jidx"
                                            class="rounded-full bg-forest-50 px-3 py-1 text-xs font-medium text-forest-700"
                                        >
                                            {{ jur }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (1/3 width) -->
                <div class="space-y-6">
                    <div class="glass-panel p-6 space-y-5 h-fit">
                        <h2 class="font-display text-lg font-bold text-ink-900 pb-3 border-b border-ink-300/30">
                            Informasi Bidang
                        </h2>

                        <div class="space-y-4 text-sm">
                            <div class="flex justify-between items-center">
                                <span class="text-ink-500">Instansi</span>
                                <span class="ml-4 text-right font-semibold text-ink-900">{{ props.division.instansi }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-ink-500">Kategori Keahlian</span>
                                <span class="font-semibold text-ink-900">{{ props.division.kategori || 'Teknologi Informasi' }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-ink-500">Total Kuota</span>
                                <span v-if="Number(props.division.terisi_total ?? 0) > Number(props.division.kuota_total ?? 0)" class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 border border-amber-200/60">
                                    Kuota Penuh
                                </span>
                                <span v-else class="font-bold text-forest-700">{{ props.division.terisi_total }} / {{ props.division.kuota_total }} Terisi</span>
                            </div>

                            <div>
                                <div class="flex items-center justify-between text-xs font-medium text-ink-500">
                                    <span v-if="isFull" class="font-semibold text-amber-700">Kuota Penuh</span>
                                    <span v-else>Slot tersisa: {{ props.division.sisa_total ?? props.division.kuota_sisa }}</span>
                                </div>
                                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-ink-100">
                                    <div
                                        class="h-full rounded-full transition-all duration-500"
                                        :class="statusMeta(props.division).bar"
                                        :style="{ width: Math.min(100, Math.round((Number(props.division.terisi_total ?? 0) / Math.max(1, Number(props.division.kuota_total ?? 1))) * 100)) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <div class="flex justify-between items-center">
                                <span class="text-ink-500">Status</span>
                                <span :class="['badge', statusMeta(props.division).badge]">
                                    {{ statusMeta(props.division).label }}
                                </span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-ink-300/30">
                            <button
                                v-if="isFull"
                                disabled
                                type="button"
                                class="w-full inline-flex items-center justify-center rounded-xl bg-ink-200/70 px-4 py-3 text-sm font-semibold text-ink-400 cursor-not-allowed text-center select-none"
                            >
                                Kuota Penuh
                            </button>
                            <Link v-else-if="user" :href="route('pengajuan.index', { division: props.division.id })" class="btn-primary w-full text-center text-sm">
                                Ajukan Sekarang
                            </Link>
                            <a v-else :href="route('auth.google')" class="btn-primary w-full text-center text-sm">
                                Daftar Sekarang
                            </a>
                            <p class="mt-3 text-center text-[11px] leading-relaxed text-ink-500">
                                {{ isFull ? 'Pendaftaran ditutup karena kuota untuk bidang ini sudah terpenuhi.' : (user ? 'Lanjutkan mengisi formulir pengajuan PKL untuk bidang ini.' : 'Anda akan diarahkan ke halaman login Google untuk melanjutkan pendaftaran.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </component>
</template>