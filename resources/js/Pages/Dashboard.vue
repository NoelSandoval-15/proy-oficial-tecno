<script setup>
import { Head } from '@inertiajs/vue3';
import SidebarLayout from '@/Layouts/SidebarLayout.vue';
</script>

<template>
    <Head title="Dashboard" />

    <SidebarLayout
        title="Dashboard"
        subtitle="Panel general de Churrasquería Roberto"
        v-slot="{ theme }"
    >
        <section
            class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-hero)] p-8 text-[var(--app-hero-text)] shadow-xl"
        >
            <div class="absolute -right-10 -top-10 h-72 w-72 rounded-full bg-[var(--app-primary)]/20 blur-3xl"></div>

            <div class="relative grid gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:items-center">
                <div>
                    <span class="rounded-full bg-[var(--app-primary-soft)] px-4 py-2 text-sm font-black text-[var(--app-primary-text)]">
                        {{ theme.displayName }}
                    </span>

                    <h1 class="mt-6 max-w-3xl text-5xl font-black leading-tight">
                        Churrasquería Roberto
                    </h1>

                    <p class="mt-5 max-w-2xl text-base leading-7 opacity-80">
                        Sistema de administración para ventas, reservas, mesas, clientes, empleados e inventario.
                    </p>
                </div>

                <div class="rounded-[2rem] border border-white/10 bg-white/10 p-6 backdrop-blur">
                    <p class="text-sm opacity-70">Tema actual</p>
                    <p class="mt-1 text-2xl font-black">{{ theme.displayName }}</p>

                    <div class="mt-6 grid grid-cols-3 gap-3">
                        <div class="h-20 rounded-2xl bg-[var(--app-primary)]/60"></div>
                        <div class="h-20 rounded-2xl bg-[var(--app-accent)]/60"></div>
                        <div class="h-20 rounded-2xl bg-white/20"></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-4">
            <article class="relative overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm xl:col-span-2">
                <div class="absolute -right-12 -top-12 h-40 w-40 rounded-full bg-[var(--app-primary)]/20 blur-3xl"></div>

                <div class="relative">
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                        Revenue Today
                    </p>

                    <h3 class="mt-5 text-5xl font-black tracking-tight text-[var(--app-text)]">
                        Bs. 42,500
                    </h3>

                    <p class="mt-3 text-sm font-black text-[var(--app-primary)]">
                        +15% vs último viernes
                    </p>

                    <div class="mt-8 flex h-28 items-end gap-3">
                        <div class="h-[45%] flex-1 rounded-t-xl bg-[var(--app-surface-soft)]"></div>
                        <div class="h-[60%] flex-1 rounded-t-xl bg-[var(--app-surface-soft)]"></div>
                        <div class="h-[52%] flex-1 rounded-t-xl bg-[var(--app-surface-soft)]"></div>
                        <div class="h-[78%] flex-1 rounded-t-xl bg-[var(--app-surface-soft)]"></div>
                        <div class="h-[65%] flex-1 rounded-t-xl bg-[var(--app-primary-soft)]"></div>
                        <div class="h-[70%] flex-1 rounded-t-xl bg-[var(--app-primary)]"></div>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                    Stock crítico
                </p>

                <div class="mt-6 space-y-5">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-black text-[var(--app-text)]">Picanha Gold</p>
                            <p class="text-sm font-semibold text-[var(--app-muted)]">Se acaba en 2 hrs</p>
                        </div>
                        <span class="rounded-xl bg-[var(--app-primary-soft)] px-3 py-2 text-sm font-black text-[var(--app-primary-text)]">
                            4 kg
                        </span>
                    </div>

                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-black text-[var(--app-text)]">Bife de Chorizo</p>
                            <p class="text-sm font-semibold text-[var(--app-muted)]">Inventario bajo</p>
                        </div>
                        <span class="rounded-xl bg-[var(--app-surface-soft)] px-3 py-2 text-sm font-black text-[var(--app-text)]">
                            12 kg
                        </span>
                    </div>
                </div>
            </article>

            <article class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-6 shadow-sm">
                <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-muted)]">
                    Ocupación
                </p>

                <div class="flex flex-col items-center justify-center py-6">
                    <div class="relative h-36 w-36">
                        <svg class="h-full w-full -rotate-90" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="42" fill="none" stroke="var(--app-surface-soft)" stroke-width="10" />
                            <circle cx="50" cy="50" r="42" fill="none" stroke="var(--app-primary)" stroke-width="10" stroke-dasharray="264" stroke-dashoffset="53" stroke-linecap="round" />
                        </svg>

                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <p class="text-4xl font-black text-[var(--app-text)]">80%</p>
                            <p class="text-sm font-black text-[var(--app-muted)]">Full</p>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </SidebarLayout>
</template>
