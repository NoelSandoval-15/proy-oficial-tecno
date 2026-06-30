<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    errors: {
        type: Object,
        default: () => ({}),
    },
    title: {
        type: String,
        default: 'Seleccionar productos',
    },
});

const emit = defineEmits(['update:items']);

const selectedCategoryId = ref('');
const selectedSubCategoryId = ref('');
const productSearch = ref('');

const formatMoney = (value) => {
    return new Intl.NumberFormat('es-BO', {
        style: 'currency',
        currency: 'BOB',
    }).format(value ?? 0);
};

const normalizedItems = computed(() => {
    return props.items
        .map((item) => ({
            products_id: Number(item.products_id),
            amount: Number(item.amount),
        }))
        .filter((item) => item.products_id > 0 && item.amount > 0);
});

const selectedCategory = computed(() => {
    return props.categories.find((category) => {
        return Number(category.id) === Number(selectedCategoryId.value);
    });
});

const subCategories = computed(() => {
    return selectedCategory.value?.sub_categories ?? [];
});

const filteredProducts = computed(() => {
    if (!selectedCategoryId.value || !selectedSubCategoryId.value) {
        return [];
    }

    const text = productSearch.value.toLowerCase().trim();

    return props.products
        .filter((product) => {
            return Number(product.sub_categories_id) === Number(selectedSubCategoryId.value);
        })
        .filter((product) => {
            if (!text) {
                return true;
            }

            return String(product.name ?? '').toLowerCase().includes(text);
        });
});

const selectedProducts = computed(() => {
    return normalizedItems.value
        .map((item) => {
            const product = props.products.find((row) => {
                return Number(row.id) === Number(item.products_id);
            });

            if (!product) {
                return null;
            }

            return {
                ...item,
                product,
                subtotal: Number(product.price) * Number(item.amount),
            };
        })
        .filter(Boolean);
});

const selectedTotal = computed(() => {
    return selectedProducts.value.reduce((total, item) => {
        return total + Number(item.subtotal);
    }, 0);
});

const selectedCount = computed(() => {
    return selectedProducts.value.reduce((total, item) => {
        return total + Number(item.amount);
    }, 0);
});

const hasSelection = computed(() => {
    return selectedProducts.value.length > 0;
});

const itemAmount = (productId) => {
    const item = normalizedItems.value.find((row) => {
        return Number(row.products_id) === Number(productId);
    });

    return item?.amount ?? 0;
};

const cleanAmount = (value, product) => {
    let amount = Number(value);

    if (!Number.isFinite(amount)) {
        amount = 0;
    }

    amount = Math.trunc(amount);

    if (amount < 0) {
        amount = 0;
    }

    const maxAmount = Number(product.amount ?? 0);

    if (amount > maxAmount) {
        amount = maxAmount;
    }

    return amount;
};

const updateItems = (nextItems) => {
    emit(
        'update:items',
        nextItems
            .map((item) => ({
                products_id: Number(item.products_id),
                amount: Number(item.amount),
            }))
            .filter((item) => item.products_id > 0 && item.amount > 0)
    );
};

const setItemAmount = (product, value) => {
    const amount = cleanAmount(value, product);

    const nextItems = normalizedItems.value.filter((item) => {
        return Number(item.products_id) !== Number(product.id);
    });

    if (amount > 0) {
        nextItems.push({
            products_id: product.id,
            amount,
        });
    }

    updateItems(nextItems);
};

const increaseProduct = (product) => {
    const currentAmount = itemAmount(product.id);

    setItemAmount(product, currentAmount + 1);
};

const decreaseProduct = (product) => {
    const currentAmount = itemAmount(product.id);

    setItemAmount(product, currentAmount - 1);
};

const removeProduct = (productId) => {
    updateItems(
        normalizedItems.value.filter((item) => {
            return Number(item.products_id) !== Number(productId);
        })
    );
};

const clearCart = () => {
    updateItems([]);
};

const blockInvalidNumber = (event) => {
    if (['-', '+', 'e', 'E', '.', ','].includes(event.key)) {
        event.preventDefault();
    }
};

const selectCategory = (category) => {
    selectedCategoryId.value = category.id;
    selectedSubCategoryId.value = '';
    productSearch.value = '';
};

const selectSubCategory = (subCategory) => {
    selectedSubCategoryId.value = subCategory.id;
    productSearch.value = '';
};

watch(selectedCategoryId, () => {
    selectedSubCategoryId.value = '';
    productSearch.value = '';
});
</script>

<template>
    <section class="space-y-5">
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--app-primary)]">
                    Catálogo
                </p>

                <h3 class="mt-1 text-2xl font-black text-[var(--app-text)]">
                    {{ title }}
                </h3>

                <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                    Elige una categoría, luego una subcategoría y añade cantidades al pedido.
                </p>
            </div>

            <div class="rounded-2xl bg-[var(--app-primary-soft)] px-4 py-3 text-right">
                <p class="text-xs font-black uppercase tracking-[0.16em] text-[var(--app-primary-text)]">
                    Carrito
                </p>

                <p class="text-sm font-black text-[var(--app-primary-text)]">
                    {{ selectedCount }} producto(s) · {{ formatMoney(selectedTotal) }}
                </p>
            </div>
        </div>

        <div class="grid gap-5 xl:grid-cols-[1fr_360px]">
            <div class="space-y-5">
                <div class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h4 class="text-lg font-black text-[var(--app-text)]">
                                Categorías
                            </h4>

                            <p class="text-sm font-semibold text-[var(--app-muted)]">
                                Primero selecciona el grupo del producto.
                            </p>
                        </div>

                        <button
                            v-if="selectedCategoryId"
                            type="button"
                            class="rounded-xl border border-[var(--app-border)] bg-[var(--app-card)] px-3 py-2 text-xs font-black text-[var(--app-muted)] transition hover:bg-[var(--app-primary-soft)]"
                            @click="selectedCategoryId = ''; selectedSubCategoryId = ''; productSearch = ''"
                        >
                            Limpiar selección
                        </button>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                        <button
                            v-for="category in categories"
                            :key="category.id"
                            type="button"
                            class="rounded-2xl border px-4 py-4 text-left transition hover:-translate-y-0.5 hover:shadow-md"
                            :class="Number(selectedCategoryId) === Number(category.id)
                                ? 'border-[var(--app-primary)] bg-[var(--app-primary-soft)] text-[var(--app-primary-text)]'
                                : 'border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] hover:border-[var(--app-primary)]'"
                            @click="selectCategory(category)"
                        >
                            <p class="font-black">
                                {{ category.name }}
                            </p>

                            <p class="mt-1 text-xs font-bold opacity-80">
                                {{ category.sub_categories?.length ?? 0 }} subcategoría(s)
                            </p>
                        </button>
                    </div>

                    <p
                        v-if="categories.length === 0"
                        class="mt-4 rounded-2xl bg-[var(--app-card)] px-4 py-3 text-sm font-bold text-[var(--app-muted)]"
                    >
                        No hay categorías registradas.
                    </p>
                </div>

                <div
                    v-if="selectedCategoryId"
                    class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-5"
                >
                    <h4 class="text-lg font-black text-[var(--app-text)]">
                        Subcategorías
                    </h4>

                    <p class="text-sm font-semibold text-[var(--app-muted)]">
                        Ahora elige una subcategoría para mostrar sus productos.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <button
                            v-for="subCategory in subCategories"
                            :key="subCategory.id"
                            type="button"
                            class="rounded-2xl border px-4 py-2 text-sm font-black transition"
                            :class="Number(selectedSubCategoryId) === Number(subCategory.id)
                                ? 'border-[var(--app-primary)] bg-[var(--app-primary)] text-white'
                                : 'border-[var(--app-border)] bg-[var(--app-card)] text-[var(--app-text)] hover:border-[var(--app-primary)]'"
                            @click="selectSubCategory(subCategory)"
                        >
                            {{ subCategory.name }}
                        </button>
                    </div>

                    <p
                        v-if="subCategories.length === 0"
                        class="mt-4 rounded-2xl bg-[var(--app-card)] px-4 py-3 text-sm font-bold text-[var(--app-muted)]"
                    >
                        Esta categoría todavía no tiene subcategorías.
                    </p>
                </div>

                <div
                    v-if="selectedSubCategoryId"
                    class="rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] p-5"
                >
                    <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <h4 class="text-lg font-black text-[var(--app-text)]">
                                Productos disponibles
                            </h4>

                            <p class="text-sm font-semibold text-[var(--app-muted)]">
                                Agrega cantidades. No se aceptan números negativos.
                            </p>
                        </div>

                        <div class="w-full md:w-72">
                            <label class="text-xs font-black uppercase tracking-[0.14em] text-[var(--app-muted)]">
                                Buscar producto
                            </label>

                            <input
                                v-model="productSearch"
                                type="text"
                                placeholder="Nombre del producto"
                                class="mt-2 w-full rounded-2xl border-[var(--app-border)] bg-[var(--app-surface-soft)] text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                            />
                        </div>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                        <article
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="group rounded-[1.6rem] border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4 transition hover:-translate-y-0.5 hover:border-[var(--app-primary)] hover:shadow-lg"
                        >
                            <div class="flex gap-3">
                                <img
                                    v-if="product.url_photo"
                                    :src="product.url_photo"
                                    class="h-20 w-20 rounded-2xl object-cover"
                                />

                                <div
                                    v-else
                                    class="flex h-20 w-20 items-center justify-center rounded-2xl bg-[var(--app-primary)] text-lg font-black text-white"
                                >
                                    PR
                                </div>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-base font-black text-[var(--app-text)]">
                                        {{ product.name }}
                                    </p>

                                    <p class="mt-1 text-sm font-black text-[var(--app-primary)]">
                                        {{ formatMoney(product.price) }}
                                    </p>

                                    <p class="mt-1 text-xs font-bold text-[var(--app-muted)]">
                                        Disponible: {{ product.amount }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2">
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-500/10 text-lg font-black text-red-500 transition hover:bg-red-500/20 disabled:opacity-40"
                                    :disabled="itemAmount(product.id) <= 0"
                                    @click="decreaseProduct(product)"
                                >
                                    −
                                </button>

                                <input
                                    type="number"
                                    min="0"
                                    :max="product.amount"
                                    :value="itemAmount(product.id)"
                                    class="h-10 flex-1 rounded-xl border-[var(--app-border)] bg-[var(--app-card)] text-center text-sm font-black text-[var(--app-text)] focus:border-[var(--app-primary)] focus:ring-[var(--app-primary)]"
                                    @keydown="blockInvalidNumber"
                                    @input="setItemAmount(product, $event.target.value)"
                                />

                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-green-500/10 text-lg font-black text-green-600 transition hover:bg-green-500/20 disabled:opacity-40"
                                    :disabled="itemAmount(product.id) >= Number(product.amount)"
                                    @click="increaseProduct(product)"
                                >
                                    +
                                </button>
                            </div>

                            <div
                                v-if="itemAmount(product.id) > 0"
                                class="mt-3 rounded-2xl bg-[var(--app-primary-soft)] px-4 py-3 text-sm font-black text-[var(--app-primary-text)]"
                            >
                                Subtotal:
                                {{ formatMoney(Number(product.price) * Number(itemAmount(product.id))) }}
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="filteredProducts.length === 0"
                        class="mt-5 rounded-2xl border border-dashed border-[var(--app-border)] bg-[var(--app-surface-soft)] px-5 py-10 text-center"
                    >
                        <p class="text-lg font-black text-[var(--app-text)]">
                            No hay productos para esta subcategoría
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Cambia la subcategoría o revisa el stock disponible.
                        </p>
                    </div>
                </div>

                <div
                    v-if="selectedCategoryId && !selectedSubCategoryId"
                    class="rounded-[2rem] border border-dashed border-[var(--app-border)] bg-[var(--app-card)] px-5 py-10 text-center"
                >
                    <p class="text-lg font-black text-[var(--app-text)]">
                        Selecciona una subcategoría
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Después de seleccionar la subcategoría aparecerán los productos.
                    </p>
                </div>

                <div
                    v-if="!selectedCategoryId"
                    class="rounded-[2rem] border border-dashed border-[var(--app-border)] bg-[var(--app-card)] px-5 py-10 text-center"
                >
                    <p class="text-lg font-black text-[var(--app-text)]">
                        Empieza seleccionando una categoría
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                        Así el pedido será más rápido y ordenado.
                    </p>
                </div>

                <p v-if="errors.items" class="text-sm font-bold text-red-500">
                    {{ errors.items }}
                </p>
            </div>

            <aside class="xl:sticky xl:top-6 xl:self-start">
                <div class="overflow-hidden rounded-[2rem] border border-[var(--app-border)] bg-[var(--app-card)] shadow-lg">
                    <div class="border-b border-[var(--app-border)] bg-[var(--app-surface-soft)] px-5 py-4">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-black uppercase tracking-[0.2em] text-[var(--app-primary)]">
                                    Pedido actual
                                </p>

                                <h4 class="mt-1 text-xl font-black text-[var(--app-text)]">
                                    Productos seleccionados
                                </h4>
                            </div>

                            <button
                                v-if="hasSelection"
                                type="button"
                                class="rounded-xl bg-red-500/10 px-3 py-2 text-xs font-black text-red-500 transition hover:bg-red-500/20"
                                @click="clearCart"
                            >
                                Vaciar
                            </button>
                        </div>
                    </div>

                    <div v-if="hasSelection" class="max-h-[420px] overflow-y-auto p-4">
                        <div class="space-y-3">
                            <article
                                v-for="item in selectedProducts"
                                :key="item.products_id"
                                class="rounded-2xl border border-[var(--app-border)] bg-[var(--app-surface-soft)] p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-[var(--app-text)]">
                                            {{ item.product.name }}
                                        </p>

                                        <p class="mt-1 text-xs font-semibold text-[var(--app-muted)]">
                                            {{ formatMoney(item.product.price) }} x {{ item.amount }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-lg bg-red-500/10 px-2 py-1 text-xs font-black text-red-500"
                                        @click="removeProduct(item.products_id)"
                                    >
                                        Quitar
                                    </button>
                                </div>

                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-500/10 text-lg font-black text-red-500"
                                            @click="decreaseProduct(item.product)"
                                        >
                                            −
                                        </button>

                                        <span class="min-w-10 text-center text-sm font-black text-[var(--app-text)]">
                                            {{ item.amount }}
                                        </span>

                                        <button
                                            type="button"
                                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-green-500/10 text-lg font-black text-green-600 disabled:opacity-40"
                                            :disabled="item.amount >= Number(item.product.amount)"
                                            @click="increaseProduct(item.product)"
                                        >
                                            +
                                        </button>
                                    </div>

                                    <p class="text-sm font-black text-[var(--app-text)]">
                                        {{ formatMoney(item.subtotal) }}
                                    </p>
                                </div>
                            </article>
                        </div>
                    </div>

                    <div v-else class="px-5 py-12 text-center">
                        <p class="text-lg font-black text-[var(--app-text)]">
                            Carrito vacío
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[var(--app-muted)]">
                            Los productos seleccionados aparecerán aquí.
                        </p>
                    </div>

                    <div class="border-t border-[var(--app-border)] bg-[var(--app-surface-soft)] px-5 py-5">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-[var(--app-muted)]">
                                Total
                            </p>

                            <p class="text-3xl font-black text-[var(--app-text)]">
                                {{ formatMoney(selectedTotal) }}
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</template>
