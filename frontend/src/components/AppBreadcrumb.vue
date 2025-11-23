<template>
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb bg-white px-3 py-2 rounded shadow-sm align-items-center">
            <li v-for="(crumb, idx) in crumbs" :key="idx" class="breadcrumb-item"
                :class="{ active: idx === crumbs.length - 1 }" aria-current="page">
                <router-link v-if="!crumb.active" :to="crumb.to" class="text-decoration-none">
                    <i v-if="crumb.icon" :class="crumb.icon + ' me-1'" />
                    {{ crumb.label }}
                </router-link>
                <span v-else>
                    <i v-if="crumb.icon" :class="crumb.icon + ' me-1'" />
                    {{ crumb.label }}
                </span>
            </li>
        </ol>
    </nav>
</template>

<script>
export default {
    name: 'AppBreadcrumb',
    props: {
        crumbs: {
            type: Array,
            required: true,
            // [{ label: 'Dashboard', to: '/admin', icon: 'bi bi-house' }, ...]
        }
    }
};
</script>

<style scoped>
.breadcrumb {
    background: #fff;
    font-size: 1rem;
    margin-bottom: 0;
}

.breadcrumb-item.active {
    color: #6366f1;
    font-weight: 500;
}
</style>
