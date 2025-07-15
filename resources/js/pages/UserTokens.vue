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
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import { Trash2 } from 'lucide-vue-next';

const tokens = ref([]);
const token = ref(null);

const isOpen = ref(false)

const formToken = useForm({
    name: '',
})

const submitToken = () => {
    axios.post(route('tokens.store'), {
        name: formToken.name,
    }).then((response) => {
        token.value = response.data
        formToken.reset()
    })
}

const deleteToken = (id: string) => {
    router.delete(route('tokens.destroy', { id }))
}

onMounted(() => {
    axios.get(route('tokens.index')).then((response) => {
        tokens.value = response.data
    })
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <div class="flex justify-between">
            <div class="flex flex-col">
                <h3 class="text-xl font-bold">Tokens</h3>
                <p class="text-sm text-gray-500">
                    Manage your user tokens here
                </p>
            </div>

            <Button @click="isOpen = true">
                Add Token
            </Button>
        </div>

        <div class="flex flex-col gap-2" v-if="tokens.length > 0">
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
                        <Input id="name" v-model="formToken.name" class="col-span-3" />
                    </div>
                </div>
                <DialogFooter>
                    <Button type="submit">
                        Create
                    </Button>
                    <DialogClose>
                        <Button type="button" variant="outline">Close</Button>
                    </DialogClose>
                    <Input type="text" :value="token" />
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
