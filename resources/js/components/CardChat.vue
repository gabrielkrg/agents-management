<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Send, X, Loader2 } from 'lucide-vue-next'
import axios from 'axios'

const input = ref('')
const inputLength = computed(() => input.value.trim().length)

const messages = ref([
  { role: 'agent', content: 'Hi, how can I help you today?' },
  { role: 'user', content: 'Hey, I\'m having trouble with my account.' },
  { role: 'agent', content: 'What seems to be the problem?' },
  { role: 'user', content: 'I can\'t log in.' },
])

const props = defineProps<{
  prompt?: string
}>()

const emit = defineEmits(['close'])

const getPrompt = () => {
  axios.get(route('prompts.get', props.prompt))
    .then((response) => {
      prompt.value = response.data
    })
    .catch((error) => {
      console.log(error)
    })
}

const prompt = ref<{
  name: string
  description: string
  json_schema: any
} | null>(null)

const waiting = ref(false);
const sendMessage = () => {
  waiting.value = true

  setTimeout(() => {
    if (inputLength.value === 0) return
    messages.value.push({
      role: 'user',
      content: input.value,
    })
    input.value = ''
    waiting.value = false;
  }, 1000)

}

onMounted(() => {
  getPrompt()
}) 
</script>

<template>
  <Card class="absolute bottom-10 right-10">
    <CardHeader class=" flex flex-row justify-between">
      <div class="flex items-center space-x-4">
        <div class="flex flex-col gap-2">
          <p class="text-sm font-medium leading-none">
            {{ prompt?.name }}
          </p>
          <p class="text-sm text-muted-foreground line-clamp-2">
            {{ prompt?.description }}
          </p>
        </div>
      </div>
      <Button size="icon" variant="secondary" class="cursor-pointer rounded-full" @click="emit('close')">
        <X class="w-4 h-4" />
      </Button>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div v-for="(message, index) in messages" :key="index" :class="cn(
          'flex w-max max-w-[75%] flex-col gap-2 rounded-lg px-3 py-2 text-sm',
          message.role === 'user' ? 'ml-auto bg-primary text-primary-foreground' : 'bg-muted',
        )">
          {{ message.content }}
        </div>
      </div>
    </CardContent>
    <CardFooter>
      <form class="flex w-full items-center space-x-2" @submit.prevent="sendMessage">
        <Input v-model="input" placeholder="Type a message..." class="flex-1" />
        <Button class="p-2.5 flex items-center justify-center" :disabled="inputLength === 0 || waiting" type="submit">
          <Loader2 class="w-4 h-4 animate-spin" v-if="waiting" />
          <Send class="w-4 h-4" />
          <span class="sr-only">Send</span>
        </Button>
      </form>
    </CardFooter>
  </Card>
</template>
