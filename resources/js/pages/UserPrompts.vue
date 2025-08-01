<script setup lang="ts">
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
import { Textarea } from '@/components/ui/textarea'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Pencil, Trash2, MessageCircle } from 'lucide-vue-next';
import { Skeleton } from '@/components/ui/skeleton'

import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3'
import axios from 'axios'

interface Prompt {
    uuid: string;
    name: string;
    description: string;
    json_schema: string | null;
    count_usage: number;
}

const prompts = ref<Prompt[]>([]);
const isLoading = ref(true)
const isOpen = ref(false)
const isOpenEdit = ref(false)

const placeholderJsonSchema = `{
   "name": { "type": "STRING" },
    "ingredients": {
        "type": "ARRAY",
        "items": { "type": "STRING" }
    }
}`;

const formPrompt = useForm({
    name: '',
    description: '',
    json_schema: ''
})

const loadPrompts = async () => {
    isLoading.value = true
    try {
        const response = await axios.get(route('prompts.index'))
        prompts.value = response.data
    } catch (error) {
        console.error('Error loading prompts:', error)
    } finally {
        isLoading.value = false
    }
}

const submitPrompt = () => {
    formPrompt.post(route('prompts.store'), {
        onSuccess: () => {
            isOpen.value = false
            formPrompt.reset()

            loadPrompts()
        }
    })
}

const selectedPrompt = ref<Prompt | null>(null);

const editPrompt = (prompt: any) => {
    selectedPrompt.value = prompt;

    isOpenEdit.value = true
    editPromptForm.name = prompt.name
    editPromptForm.description = prompt.description
    editPromptForm.json_schema = prompt.json_schema
}

const editPromptForm = useForm({
    name: '',
    description: '',
    json_schema: '',
})

const updatePrompt = (uuid: string | null) => {
    if (!uuid) {
        return
    }

    editPromptForm.put(route('prompts.update', { prompt: uuid }), {
        onSuccess: () => {
            editPromptForm.reset()
            isOpenEdit.value = false
            selectedPrompt.value = null

            loadPrompts()
        },
        onError: (errors) => {
            console.log(errors)
        }
    })
}

const deletePrompt = (uuid: string) => {
    router.delete(route('prompts.destroy', { prompt: uuid }), {
        onSuccess: () => {
            loadPrompts()
        }
    })
}

onMounted(() => {
    loadPrompts()
})

const emit = defineEmits(['openChat'])

const startChat = (uuid: string) => {
    emit('openChat', uuid)
}
</script>

<template>
    <div class="flex flex-col gap-2 p-4">
        <div class="flex justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight">
                    Prompts
                </h2>
                <p class="text-muted-foreground">
                    Manage your user prompts here
                </p>
            </div>

            <Button @click="isOpen = true">
                Add Prompt
            </Button>
        </div>

        <div class="flex flex-col gap-2 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border p-2"
            v-if="prompts.length > 0 || isLoading">
            <Table class="overflow-hidden">
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Link</TableHead>
                        <TableHead>Usage</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <!-- Loading skeleton -->
                    <TableRow v-if="isLoading" v-for="i in 3" :key="`skeleton-${i}`">
                        <TableCell>
                            <Skeleton class="h-4 w-32" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-4 w-48" />
                        </TableCell>
                        <TableCell>
                            <Skeleton class="h-4 w-64" />
                        </TableCell>
                        <TableCell class="flex justify-end items-center gap-2">
                            <Skeleton class="h-8 w-8 rounded" />
                            <Skeleton class="h-8 w-8 rounded" />
                        </TableCell>
                    </TableRow>

                    <!-- Actual prompts -->
                    <TableRow v-else v-for="prompt in prompts" :key="prompt.uuid">
                        <TableCell class="font-medium">{{ prompt.name }}</TableCell>
                        <TableCell class="max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ prompt.description }}
                        </TableCell>
                        <TableCell>
                            {{ route('api.ai', { prompt: prompt.uuid }) }}
                        </TableCell>
                        <TableCell>
                            {{ prompt.count_usage }}
                        </TableCell>
                        <TableCell class="flex justify-end items-center gap-2">
                            <Button size="icon" class="cursor-pointer" @click="startChat(prompt.uuid)">
                                <MessageCircle />
                            </Button>
                            <Dialog v-model:open="isOpenEdit">
                                <DialogTrigger as-child>
                                    <Button size="icon" variant="secondary" class="cursor-pointer"
                                        @click="editPrompt(prompt)">
                                        <Pencil />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[600px]">
                                    <form @submit.prevent="updatePrompt(selectedPrompt?.uuid || null)">
                                        <DialogHeader>
                                            <DialogTitle>Edit Prompt</DialogTitle>
                                            <DialogDescription>
                                                Edit the prompt here.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div class="grid gap-4 py-4">
                                            <div class="grid grid-cols-4 items-center gap-4">
                                                <Label for="name" class="text-right">
                                                    Name
                                                </Label>
                                                <Input id="name" v-model="editPromptForm.name" class="col-span-3" />
                                            </div>
                                            <div class="grid grid-cols-4 items-center gap-4">
                                                <Label for="description" class="text-right">
                                                    Description
                                                </Label>
                                                <Textarea id="description" v-model="editPromptForm.description"
                                                    class="col-span-3" />
                                            </div>
                                            <div class="grid grid-cols-4 items-center gap-4">
                                                <Label for="json_schema" class="text-right">
                                                    JSON Schema
                                                </Label>
                                                <div class="col-span-3">
                                                    <Textarea id="json_schema" v-model="editPromptForm.json_schema"
                                                        type="json" :placeholder="placeholderJsonSchema" />
                                                    <span class="text-red-500 text-xs text-right">
                                                        Let it be null if you want a plain text response.
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <DialogFooter>
                                            <DialogClose>
                                                <Button type="button" variant="outline">Cancel</Button>
                                            </DialogClose>
                                            <Button type="submit">Save</Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>

                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button variant="destructive" size="icon" class="cursor-pointer">
                                        <Trash2 />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px]">
                                    <DialogHeader>
                                        <DialogTitle>Delete Prompt</DialogTitle>
                                        <DialogDescription>
                                            Are you sure you want to delete this prompt?
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <DialogClose>
                                            <Button type="button" variant="outline">Cancel</Button>
                                        </DialogClose>
                                        <DialogClose>
                                            <Button type="button" variant="destructive"
                                                @click="deletePrompt(prompt.uuid)">Delete</Button>
                                        </DialogClose>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </div>

    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[600px]">
            <form @submit.prevent="submitPrompt">
                <DialogHeader>
                    <DialogTitle>Add Prompt</DialogTitle>
                    <DialogDescription>
                        Add a new prompt to your account.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-4 py-4">
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="name" class="text-right">
                            Name
                        </Label>
                        <div class="col-span-3">
                            <Input id="name" v-model="formPrompt.name" />
                            <span class="text-red-500 text-xs text-right" v-if="formPrompt.errors.name">
                                {{ formPrompt.errors.name }}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="description" class="text-right">
                            Description
                        </Label>
                        <div class="col-span-3">
                            <Textarea id="description" v-model="formPrompt.description" />
                            <span class="text-red-500 text-xs text-right" v-if="formPrompt.errors.description">
                                {{ formPrompt.errors.description }}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="description" class="text-right">
                            JSON Schema
                        </Label>
                        <div class="col-span-3">
                            <Textarea id="json_schema" v-model="formPrompt.json_schema" type="json"
                                :placeholder="placeholderJsonSchema" />
                            <span class="text-red-500 text-xs text-right">
                                Let it be null if you want a plain text response.
                            </span>
                            <span class="text-red-500 text-xs text-right" v-if="formPrompt.errors.json_schema">
                                {{ formPrompt.errors.json_schema }}
                            </span>
                        </div>
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit">
                        Save changes
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
