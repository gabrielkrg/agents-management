<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import UserPrompts from './UserPrompts.vue';
import UserTokens from './UserTokens.vue';
import CardChat from '@/components/CardChat.vue';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
]

const props = defineProps({
    user: Object,
});

const showChat = ref(false);
const prompt = ref<string | null>(null);

const selectChat = (uuid: string) => {
    prompt.value = uuid;
    showChat.value = true;
};
</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
            <UserPrompts @openChat="selectChat" />
            <UserTokens />
            <CardChat v-if="showChat" :prompt="prompt" @close="showChat = false" />
        </div>
    </AppLayout>
</template>
