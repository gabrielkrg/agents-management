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

import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'

import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Pencil, Trash2 } from 'lucide-vue-next';

import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3'
import axios from 'axios'

const prompts = ref([]);
const isOpen = ref(false)

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
    json_schema: null
})

const submitPrompt = () => {
    formPrompt.post(route('prompts.store'), {
        onSuccess: () => {
            isOpen.value = false
            formPrompt.reset()
        }
    })
}


const editPrompt = (prompt: any) => {
    editPromptForm.name = prompt.name
    editPromptForm.description = prompt.description
    editPromptForm.json_schema = prompt.json_schema
}

const editPromptForm = useForm({
    name: '',
    description: '',
    json_schema: null
})

const updatePrompt = (uuid: string) => {
    editPromptForm.put(route('prompts.update', { prompt: uuid }), {
        onSuccess: () => {
            editPromptForm.reset()
        }
    })
}

const deletePrompt = (uuid: string) => {
    router.delete(route('prompts.destroy', { prompt: uuid }), {
        onSuccess: () => {
        }
    })
}

onMounted(() => {
    axios.get(route('prompts.index')).then((response) => {
        prompts.value = response.data
    })
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex justify-between">
            <div class="flex flex-col">
                <h3 class="text-xl font-bold">Prompts</h3>
                <p class="text-sm text-gray-500">
                    Manage your user prompts here
                </p>
            </div>

            <Button @click="isOpen = true">
                Add Prompt
            </Button>
        </div>

        <div class="flex flex-col gap-2" v-if="prompts.length > 0">
            <Table class="overflow-hidden">
                <TableHeader>
                    <TableRow>
                        <TableHead>Name</TableHead>
                        <TableHead>Description</TableHead>
                        <TableHead>Link</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="prompt in prompts" :key="prompt.uuid">
                        <TableCell class="font-medium">{{ prompt.name }}</TableCell>
                        <TableCell class="max-w-[200px] overflow-hidden text-ellipsis whitespace-nowrap">
                            {{ prompt.description }}
                        </TableCell>
                        <TableCell>
                            {{ route('api.generate-with-ai', { prompt: prompt.uuid }) }}
                        </TableCell>
                        <TableCell class="flex justify-end items-center gap-2">
                            <Dialog>
                                <DialogTrigger as-child>
                                    <Button size="icon" variant="secondary" @click="editPrompt(prompt)">
                                        <Pencil />
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-[425px]">
                                    <form @submit.prevent="updatePrompt(prompt.uuid)">
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
                                    <Button variant="destructive" size="icon">
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
                        <Input id="name" v-model="formPrompt.name" class="col-span-3" />
                    </div>
                    <div class="grid grid-cols-4 items-center gap-4">
                        <Label for="description" class="text-right">
                            Description
                        </Label>
                        <Textarea id="description" v-model="formPrompt.description" class="col-span-3" />
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
