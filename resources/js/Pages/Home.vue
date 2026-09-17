<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getStatusLabel, getStatusBadgeClass, getTimelineSteps } from '@/utils/statusLabel';
import {
    FileText,
    Search,
    ArrowRight,
    MapPin,
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const props = defineProps({
    pendaftaranAktif: { type: Object, default: null },
});

const steps = computed(() => getTimelineSteps(props.pendaftaranAktif));

const firstName = computed(() => {
    const name = user.value?.name ?? user.value?.nama_lengkap ?? '';
    return name.split(' ')[0] || 'Mahasiswa';
});
</script>

<template>
    <Head title="Home" />
    <AppLayout title="Beranda">
        <!-- Greeting -->
        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-ink-900">
                Halo, {{ firstName }}
            </h2>
            <p class="mt-1 text-sm text-ink-500">
                Selamat datang di Portal PKL — Dinas Komunikasi dan Informatika Provinsi Kalimantan Timur.
            </p>
        </div>

        <!-- State-driven Task Center -->
        <!-- State: No active application -->
        <div v-if="!pendaftaranAktif" class="glass-panel p-8 rounded-3xl">
            <div class="flex flex-col items-center text-center gap-5">
                <div class="grid h-16 w-16 place-items-center rounded-2xl bg-ink-100 text-ink-400">
                    <FileText :size="30" :stroke-width="1.6" />
                </div>
                <div>
                    <h3 class="font-display text-xl font-bold text-ink-900">Belum Ada Pengajuan Aktif</h3>
                    <p class="mt-2 max-w-md text-sm leading-relaxed text-ink-500">
                        Kamu belum memiliki pengajuan PKL yang sedang berjalan. Mulai dengan menjelajahi katalog bidang yang tersedia.
                    </p>
                </div>
                <Link :href="route('katalog.index')" class="btn-primary flex items-center gap-2 px-6">
                    <Search :size="16" :stroke-width="2" />
                    Jelajahi Katalog Bidang
                    <ArrowRight :size="16" :stroke-width="2" />
                </Link>
            </div>
        </div>

        <!-- State: Active application (menunggu / diterima) -->
        <div v-else class="space-y-6">
            <!-- Application Summary Card -->
            <div class="glass-panel p-6 rounded-3xl">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-ink-400">Pengajuan Aktif</p>
                        <h3 class="mt-1 font-display text-xl font-bold text-ink-900">
                            {{ pendaftaranAktif.judul }}
                        </h3>
                        <div class="mt-1.5 flex items-center gap-1.5 text-sm text-ink-500">
                            <MapPin :size="13" :stroke-width="2" />
                            {{ pendaftaranAktif.instansi }}
                        </div>
                    </div>
                    <span :class="getStatusBadgeClass(pendaftaranAktif.status)" class="badge shrink-0">
                        {{ getStatusLabel(pendaftaranAktif.status) }}
                    </span>
                </div>

                <!-- Progress Stepper -->
                <div class="relative flex items-start justify-between px-2 pt-2">
                    <!-- Background connector track -->
                    <div class="absolute left-8 right-8 top-[18px] h-0.5 bg-ink-200" />

                    <template v-for="(step, idx) in steps" :key="idx">
                        <div class="relative z-10 flex flex-col items-center gap-2 text-center" style="min-width:4rem;">
                            <!-- Filled connector segment (left side of node, except first) -->
                            <div
                                v-if="idx > 0"
                                class="absolute top-[18px] h-0.5 transition-colors duration-500"
                                :class="step.status === 'completed' || step.status === 'in_progress' ? 'bg-forest-500' : 'bg-ink-200'"
                                :style="`right: 50%; width: calc(100% * ${idx} / ${steps.length - 1} - 50%)`"
                            />

                            <!-- Step node circle -->
                            <div
                                class="grid h-9 w-9 place-items-center rounded-full border-2 transition-colors duration-300"
                                :class="
                                    step.status === 'in_progress'
                                        ? 'border-forest-600 bg-forest-600 text-white ring-4 ring-forest-400/25'
                                        : step.status === 'completed'
                                            ? 'border-forest-500 bg-forest-50 text-forest-600'
                                            : step.status === 'rejected'
                                                ? 'border-red-500 bg-red-50 text-red-600'
                                                : step.status === 'revision'
                                                    ? 'border-amber-500 bg-amber-50 text-amber-600'
                                                    : 'border-ink-300 bg-white text-ink-400'
                                "
                            >
                                <!-- Completed: checkmark -->
                                <svg v-if="step.status === 'completed'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                <!-- In Progress: pulse dot -->
                                <span v-else-if="step.status === 'in_progress'" class="h-2 w-2 rounded-full bg-white animate-pulse" />
                                <!-- Rejected: X -->
                                <svg v-else-if="step.status === 'rejected'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                <!-- Revision: exclamation -->
                                <svg v-else-if="step.status === 'revision'" viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3"><line x1="12" y1="8" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <!-- Pending: step number -->
                                <span v-else class="text-[10px] font-bold">{{ idx + 1 }}</span>
                            </div>

                            <!-- Label -->
                            <p
                                class="text-[11px] font-medium leading-tight"
                                :class="
                                    step.status === 'in_progress' ? 'text-forest-700'
                                    : step.status === 'completed' ? 'text-ink-700'
                                    : step.status === 'rejected' ? 'text-red-600'
                                    : step.status === 'revision' ? 'text-amber-700'
                                    : 'text-ink-400'
                                "
                            >
                                {{ step.label }}
                            </p>
                        </div>
                    </template>
                </div>

                <!-- CTA -->
                <div class="mt-6 flex justify-end">
                    <Link :href="route('status.index')" class="btn-primary flex items-center gap-2 text-sm">
                        Lihat Detail Status
                        <ArrowRight :size="15" :stroke-width="2" />
                    </Link>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Link
                    :href="route('katalog.index')"
                    class="glass-panel flex items-center gap-4 rounded-2xl p-5 transition hover:bg-white/80"
                >
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-forest-600/10 text-forest-700">
                        <Search :size="20" :stroke-width="1.8" />
                    </div>
                    <div>
                        <p class="font-semibold text-ink-900">Katalog Bidang</p>
                        <p class="text-xs text-ink-500 mt-0.5">Jelajahi semua bidang PKL tersedia</p>
                    </div>
                </Link>
                <Link
                    :href="route('riwayat.index')"
                    class="glass-panel flex items-center gap-4 rounded-2xl p-5 transition hover:bg-white/80"
                >
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-forest-600/10 text-forest-700">
                        <Clock :size="20" :stroke-width="1.8" />
                    </div>
                    <div>
                        <p class="font-semibold text-ink-900">Riwayat Pengajuan</p>
                        <p class="text-xs text-ink-500 mt-0.5">Lihat pengajuan sebelumnya</p>
                    </div>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
