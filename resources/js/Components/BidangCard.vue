<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Building2,
    ClipboardList,
    Code2,
    Network,
    Shield,
    GraduationCap,
    HeartPulse,
    LineChart,
    Map,
} from 'lucide-vue-next';

const props = defineProps({
    item: { type: Object, required: true },
    detailHref: { type: String, default: '' },
    primary: { type: Object, default: null },
});

const categoryIcons = {
    'Teknologi Informasi': Code2,
    'Infrastruktur Jaringan': Network,
    'Keamanan Siber': Shield,
    'Administrasi': ClipboardList,
    'Pendidikan': GraduationCap,
    'Kesehatan': HeartPulse,
    'Perencanaan': LineChart,
    'Sistem Informasi Geografis': Map,
};

const iconFor = (kategori) => categoryIcons[kategori] || ClipboardList;

const isFull = computed(() => {
    const sisa = Number(props.item.sisa_total ?? props.item.kuota_sisa ?? 0);
    const terisi = Number(props.item.terisi_total ?? props.item.kuota_terisi ?? 0);
    const kuota = Number(props.item.kuota_total ?? props.item.batas_kuota ?? 0);
    return sisa <= 0 || (kuota > 0 && terisi >= kuota);
});
</script>

<template>
    <article class="glass-card flex flex-col gap-5 rounded-3xl p-6 transition hover:-translate-y-1 hover:border-forest-500/40 hover:shadow-card">
        <!-- Header -->
        <div class="flex flex-col items-center text-center gap-3">
            <div class="inline-grid h-12 w-12 place-items-center rounded-2xl bg-forest-600/10 text-forest-700">
                <component :is="iconFor(item.kategori)" :size="24" :stroke-width="1.8" />
            </div>
            <Link
                :href="detailHref"
                class="font-display text-base font-bold leading-snug text-ink-900 transition hover:text-forest-700"
            >
                {{ item.nama }}
            </Link>
            <p class="flex items-center justify-center gap-1.5 text-xs text-ink-500">
                <Building2 :size="13" :stroke-width="2" class="shrink-0" />
                {{ item.instansi }}
            </p>
        </div>

        <!-- Progress Kuota -->
        <div>
            <div class="flex items-center justify-between text-xs font-medium text-ink-500">
                <span v-if="Number(item.terisi_total ?? 0) >= Number(item.kuota_total ?? 1)" class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 font-semibold text-amber-700 border border-amber-200/60">
                    Kuota Penuh
                </span>
                <span v-else>{{ item.terisi_total }} / {{ item.kuota_total }} slot terisi</span>
            </div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-ink-100">
                <div
                    class="h-full rounded-full transition-all duration-500"
                    :class="Number(item.terisi_total ?? 0) >= Number(item.kuota_total ?? 1) ? 'bg-ink-400' : Number((item.terisi_total / item.kuota_total) * 100) >= 50 ? 'bg-status-success' : 'bg-gold-500'"
                    :style="{ width: Math.min(100, Math.round((Number(item.terisi_total ?? 0) / Math.max(1, Number(item.kuota_total ?? 1))) * 100)) + '%' }"
                ></div>
            </div>
        </div>

        <!-- Jurusan -->
        <div v-if="item.jurusan_tags?.length" class="flex flex-wrap gap-1.5">
            <span
                v-for="(jur, jidx) in item.jurusan_tags"
                :key="jidx"
                class="rounded-full border border-forest-100 bg-forest-50 px-2.5 py-1 text-xs font-medium text-forest-700"
            >
                {{ jur }}
            </span>
        </div>

        <!-- Aksi -->
        <div class="mt-auto grid grid-cols-2 gap-3 border-t border-ink-300/30 pt-5">
            <Link :href="detailHref" class="btn-secondary px-3 py-2.5 text-xs text-center">
                Lihat Detail
            </Link>
            <template v-if="primary">
                <button
                    v-if="isFull"
                    disabled
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl bg-ink-200/70 px-3 py-2.5 text-xs font-semibold text-ink-400 cursor-not-allowed text-center select-none"
                    title="Kuota untuk bidang ini sudah penuh"
                >
                    Kuota Penuh
                </button>
                <a
                    v-else-if="primary.external || primary.href?.includes('/auth/')"
                    :href="primary.href"
                    class="btn-primary px-3 py-2.5 text-xs text-center"
                >
                    {{ primary.label }}
                </a>
                <Link
                    v-else
                    :href="primary.href"
                    class="btn-primary px-3 py-2.5 text-xs text-center"
                >
                    {{ primary.label }}
                </Link>
            </template>
            <span
                v-else
                class="inline-flex items-center justify-center rounded-lg border border-dashed border-ink-300 px-3 py-2.5 text-xs text-ink-400"
            >
                Tidak tersedia
            </span>
        </div>
    </article>
</template>
