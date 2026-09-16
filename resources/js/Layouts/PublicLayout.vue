<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roles = computed(() => page.props.auth?.roles || []);

const portalRoute = computed(() => {
    if (roles.value.includes('super_admin')) return route('superadmin.dashboard');
    if (roles.value.includes('agency_admin')) return route('admin.dashboard');
    return route('home');
});
</script>

<template>
    <div class="flex min-h-screen flex-col bg-surface">
        <!-- Public Header -->
        <header class="sticky top-0 z-30 border-b border-ink-300/40 bg-white/80 backdrop-blur-md">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6">
                <Link :href="route('katalog.index')" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-b from-forest-950 via-forest-800 to-forest-600 text-gold-400 shadow-inner">
                        <svg viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="leading-tight">
                        <span class="block font-display text-base font-bold tracking-tight text-ink-900">SIAP-PKL</span>
                        <span class="block text-[9px] font-semibold uppercase tracking-widest text-forest-700">Diskominfo Kaltim</span>
                    </div>
                </Link>

                <div class="flex items-center gap-3">
                    <template v-if="user">
                        <Link :href="portalRoute" class="flex items-center gap-2 rounded-xl bg-forest-50 px-3.5 py-2 text-xs font-semibold text-forest-800 transition hover:bg-forest-100">
                            <span class="grid h-6 w-6 place-items-center rounded-lg bg-forest-700 text-white font-bold text-[10px]">
                                {{ (user.nama_lengkap || user.name || 'U')[0] }}
                            </span>
                            <span class="hidden sm:inline">Portal Saya ({{ user.nama_lengkap || user.name }})</span>
                            <span class="sm:hidden">Portal</span>
                        </Link>
                        <Link :href="route('logout')" method="post" as="button" class="btn-secondary px-3.5 py-2 text-xs">
                            Keluar
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="btn-primary px-5 py-2.5 text-sm">
                            Login
                        </Link>
                    </template>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Public Footer -->
        <footer class="border-t border-ink-300/40 bg-white/60 py-6">
            <div class="mx-auto max-w-6xl px-4 text-center text-xs text-ink-500 sm:px-6">
                © 2026 Dinas Komunikasi dan Informatika Provinsi Kalimantan Timur
            </div>
        </footer>
    </div>
</template>