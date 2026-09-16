<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { getStatusLabel, getStatusBadgeClass, getTimelineSteps } from '@/utils/statusLabel';
import {
    FileText,
    CheckCircle,
    Clock,
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
                <div class="relative flex items-start justify-between px-2">
                    <template v-for="(step, idx) in steps" :key="idx">
                        <!-- Connector line -->
                        <div
                            v-if="idx > 0"
                            class="absolute top-4 h-0.5 flex-1 transition-colors duration-500"
                            :class="step.done ? 'bg-forest-500' : 'bg-ink-200'"
                            :style="`left: calc(${(idx / (steps.length - 1)) * 100}% * ${idx} / ${steps.length - 1} + ${idx * (100 / (steps.length - 1))}%); width: calc(100% / ${steps.length - 1} - 2rem)`"
                        />
                        <div class="relative z-10 flex flex-col items-center gap-2 text-center" style="min-width:4rem;">
                            <div
                                class="grid h-8 w-8 place-items-center rounded-full border-2 transition-colors duration-300"
                                :class="step.active
                                    ? 'border-forest-600 bg-forest-600 text-white'
                                    : step.done
                                        ? 'border-forest-500 bg-forest-50 text-forest-600'
                                        : 'border-ink-300 bg-white text-ink-400'"
                            >
                                <CheckCircle v-if="step.done && !step.active" :size="14" :stroke-width="2.5" />
                                <Clock v-else-if="step.active" :size="14" :stroke-width="2.5" />
                                <span v-else class="text-[10px] font-bold">{{ idx + 1 }}</span>
                            </div>
                            <p class="text-[11px] font-medium leading-tight" :class="step.active ? 'text-forest-700' : step.done ? 'text-ink-600' : 'text-ink-400'">
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
