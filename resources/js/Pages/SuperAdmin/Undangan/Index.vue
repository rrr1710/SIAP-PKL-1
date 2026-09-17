<script setup>
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    instansiOptions: { type: Array, default: () => [] },
    riwayatUndangan: { type: Array, default: () => [] },
});

const form = useForm({
    id_instansi: props.instansiOptions.length ? props.instansiOptions[0].id : '',
    email: '',
});

const submitUndangan = () => {
    form.post(route('superadmin.undangan.store'), {
        onSuccess: () => {
            form.email = '';
        },
    });
};
</script>

<template>
    <Head title="Undangan Admin Instansi" />
    <SuperAdminLayout title="Undangan Admin Instansi">
        <div class="mb-6">
            <h2 class="font-display text-xl font-bold text-ink-900">Undang Admin Instansi</h2>
            <p class="mt-1 text-sm text-ink-500">Kirim undangan ke calon admin untuk mengelola portal instansi.</p>
        </div>

        <!-- Success Alert -->
        <div v-if="$page.props.flash?.success" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 shadow-sm">
            {{ $page.props.flash.success }}
        </div>

        <!-- Form Undangan -->
        <div class="glass-panel mb-6 p-6 sm:p-8">
            <h3 class="mb-4 font-display text-base font-bold text-ink-900">Kirim Undangan Baru</h3>
            <form @submit.prevent="submitUndangan" class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="field-label">Pilih Instansi</label>
                    <select v-model="form.id_instansi" class="field-input">
                        <option v-for="inst in instansiOptions" :key="inst.id" :value="inst.id">{{ inst.nama_instansi }}</option>
                    </select>
                </div>
                <div>
                    <label class="field-label">Email Calon Admin</label>
                    <input v-model="form.email" type="email" required placeholder="calon.admin@instansi.co.id" class="field-input" :class="{'!border-red-500': form.errors.email}" />
                    <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
                </div>
                <div class="md:col-span-2 flex justify-end">
                    <button type="submit" class="btn-primary" :disabled="form.processing">
                        <svg viewBox="0 0 24 24" class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" /><polyline points="22,6 12,13 2,6" />
                        </svg>
                        {{ form.processing ? 'Menyimpan...' : 'Atur Admin' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Riwayat Undangan -->
        <section class="glass-panel p-6 sm:p-8">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-display text-base font-bold text-ink-900">Daftar Admin Instansi</h3>
                <span class="badge badge-info">{{ riwayatUndangan.length }} Admin</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="border-b border-ink-300/35 text-xs uppercase tracking-wider text-ink-500">
                        <tr>
                            <th class="px-4 py-3 w-12">No</th>
                            <th class="px-4 py-3">Email Admin</th>
                            <th class="px-4 py-3">Nama</th>
                            <th class="px-4 py-3">Instansi</th>
                            <th class="px-4 py-3">Status OAuth</th>
                            <th class="px-4 py-3">Tanggal Dibuat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-300/20">
                        <tr v-for="(item, index) in riwayatUndangan" :key="item.id" class="transition hover:bg-forest-50/60">
                            <td class="px-4 py-4 text-ink-500">{{ index + 1 }}</td>
                            <td class="px-4 py-4 font-semibold text-ink-900">{{ item.email }}</td>
                            <td class="px-4 py-4 text-ink-700">{{ item.nama_lengkap }}</td>
                            <td class="px-4 py-4 text-ink-700">{{ item.instansi }}</td>
                            <td class="px-4 py-4">
                                <span :class="item.oauth_connected ? 'badge-success' : 'badge-warning'" class="badge">
                                    {{ item.oauth_connected ? 'Terhubung (Google)' : 'Belum Login / Draft' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-ink-500 whitespace-nowrap">{{ item.tanggal }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </SuperAdminLayout>
</template>
