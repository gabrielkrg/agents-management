<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button'
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogClose,
    DialogTrigger,
} from '@/components/ui/dialog'

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Skeleton } from '@/components/ui/skeleton'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { Clipboard, Trash2 } from 'lucide-vue-next';

const tokens = ref<any[]>([]);
const token = ref<string | null>(null);
const isLoading = ref(true);
const isSubmitting = ref(false);

const isOpen = ref(false)

const formToken = useForm({
    name: '',
})

const loadTokens = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(route('tokens.index'));
        tokens.value = response.data;
    } catch (error) {
        console.error('Error loading tokens:', error);
    } finally {
        isLoading.value = false;
    }
}

const submitToken = async () => {
    isSubmitting.value = true;
    try {
        const response = await axios.post(route('tokens.store'), {
            name: formToken.name,
        });
        token.value = response.data;

        // Refresh tokens list after creating new token
        await loadTokens();

        // Reset form
        formToken.reset();
        formToken.clearErrors();
    } catch (error) {
        if (error.response?.data?.errors) {
            formToken.errors = error.response.data.errors;
        }
    } finally {
        isSubmitting.value = false;
    }
}

const deleteToken = async (id: string) => {
    try {
        await router.delete(route('tokens.destroy', { id }));
        // Refresh tokens list after deletion
        await loadTokens();
    } catch (error) {
        console.error('Error deleting token:', error);
    }
}

onMounted(() => {
    loadTokens();
})

const copied = ref(false);
const clipboard_errors = ref('');
const copyToClipboard = async (token: string) => {
    if (!navigator.clipboard) {
        clipboard_errors.value = "Your browser doesn't support automatic copy. Copy it manually";
        return;
    }

    try {
        await navigator.clipboard.writeText(token);
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    } catch (e) {
        console.error('Error copying to clipboard:', e);
    }
};
</script>

<template>
    <div class="flex flex-col gap-2 p-4">
        <div class="flex justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">
                    Tokens
                </h2>
                <p class="text-muted-foreground">
                    Manage your user tokens here
                </p>
            </div>

            <Button @click="isOpen = true">
                Add Token
            </Button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading"
            class="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-2">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            <Skeleton class="h-4 w-8" />
                        </TableHead>
                        <TableHead class="w-[100px]">
                            <Skeleton class="h-4 w-16" />
                        </TableHead>
                        <TableHead>
                            <Skeleton class="h-4 w-20" />
                        </TableHead>
                        <TableHead>
                            <Skeleton class="h-4 w-24" />
                        </TableHead>
                        <TableHead class="text-right">
                            <Skeleton class="h-4 w-16 ml-auto" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="i in 3" :key="i">
                        <TableCell>
                            <Skeleton class="h-4 w-8" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-4 w-24" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-4 w-32" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-4 w-20" />
                        </TableCell>
                        <TableCell class="text-right">
                            <Skeleton class="h-4 w-8 ml-auto" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Tokens Table -->
        <div v-else-if="tokens.length > 0"
            class="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-2">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>
                            ID
                        </TableHead>
                        <TableHead class="w-[100px]">
                            Name
                        </TableHead>
                        <TableHead>Abilities</TableHead>
                        <TableHead>Last Used</TableHead>
                        <TableHead class="text-right">
                            Actions
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="token in tokens" :key="token.id">
                        <TableCell>{{ token.id }}</TableCell>
                        <TableCell class="font-medium">
                            {{ token.name }}
                        </TableCell>
                        <TableCell>{{ token.abilities.join(', ') }}</TableCell>
                        <TableCell>{{ token.last_used_at || '-' }}</TableCell>
                        <TableCell class="flex justify-end items-center gap-2">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="destructive" size="icon">
                                        <Trash2 />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px]">
                                    <DialogHeader>
                                        <DialogTitle>Delete Token</DialogTitle>
                                        <DialogDescription>
                                            Are you sure you want to delete this token?
                                        </DialogDescription>
                                    </DialogHeader>

                                    <DialogFooter>
                                        <DialogClose>
                                            <Button type="button" variant="outline">Cancel</Button>
                                        </DialogClose>
                                        <DialogClose>
                                            <Button type="button" variant="destructive"
                                                @click="deleteToken(token.id)">Delete</Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Empty State -->
        <div v-else class="flex flex-col items-center justify-center py-12 text-center">
            <div class="text-muted-foreground">
                <p class="text-lg font-medium">No tokens found</p>
                <p class="text-sm">Create your first token to get started.</p>
            </div>
        </div>
    </div>

    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[425px]">
            <form @submit.prevent="submitToken">
                <DialogHeader>
                    <DialogTitle>Add Token</DialogTitle>
                    <DialogDescription>
                        Add a new token to your account.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">
                            Name
                        </Label>
                        <div class="col-span-3">
                            <Input id="name" v-model="formToken.name" :disabled="isSubmitting" />
                            <span class="text-sm text-red-500 col-span-full" v-for="error in formToken.errors.name">
                                {{ error }}
                            </span>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit" :disabled="isSubmitting">
                        {{ isSubmitting ? 'Creating...' : 'Create' }}
                    </Button>
                    <DialogClose>
                        <Button type="button" variant="outline" :disabled="isSubmitting">Close</Button>
                    </DialogClose>
                </DialogFooter>
            </form>

            <div class="grid grid-cols-4 items-center gap-4 border-t pt-4" v-show="token">
                <Label for="token" class="text-right">
                    Token
                </Label>
                <div class="col-span-3 flex items-center gap-2">
                    <Input id="token" :value="token" class="col-span-3" readonly />
                    <Button type="button" @click="copyToClipboard(token || '')" class="cursor-pointer">
                        {{ copied ? 'Copied!' : 'Copy' }}
                        <Clipboard />
                    </Button>
                </div>
                <p class="text-sm text-red-500 col-span-full" v-if="token">
                    Copy before closing this dialog, this token will not be shown again.
                </p>
                <p class="text-sm text-red-500 col-span-full" v-if="clipboard_errors">
                    {{ clipboard_errors }}
                </p>
            </div>
        </DialogContent>
    </Dialog>
</template>
