<script setup lang="ts">
import { computed, ref, onMounted, nextTick } from 'vue'
import { cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import {
  Card,
  CardContent,
  CardFooter,
  CardHeader,
} from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Skeleton } from '@/components/ui/skeleton'
import { AlertDialog, AlertDialogTrigger, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog'
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip'
import { Send, X, Loader2, Eraser } from 'lucide-vue-next'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { marked } from 'marked'

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

const loading = ref(true)
const prompt = ref<{
  name: string
  description: string
  json_schema: any
} | null>(null)

const waiting = ref(false);

const getPrompt = () => {
  axios.get(route('prompts.get', props.prompt))
    .then((response) => {
      prompt.value = response.data
    })
    .catch((error) => {
      console.log(error)
    })
}

const getChats = () => {
  if (!props.prompt) return

  axios.get(route('prompts.chats', props.prompt))
    .then((response) => {
      messages.value = response.data.chats.map((chat: any) => ({
        role: chat.role === 'user' ? 'user' : 'agent',
        content: chat.text,
      }))
      loading.value = false
      // Scroll after data is loaded and DOM is updated
      nextTick(() => {
        scrollToBottom(chatDiv.value)
      })
    })
    .catch((error) => {
      console.log(error)
      loading.value = false
    })
}

const sendMessage = () => {
  if (inputLength.value === 0 || !props.prompt) return

  waiting.value = true

  // Salvar mensagem do usuário
  router.post(route('chats.store'), {
    prompt_id: props.prompt,
    role: 'user',
    text: input.value,
  }, {
    onSuccess: () => {
      getChats()
      generateContent()
    },
    onError: (error) => {
      console.log(error)
    },
    onFinish: () => {
      input.value = ''
    }
  })

}

const chatDiv = ref<HTMLDivElement | null>(null);

function scrollToBottom(div: HTMLDivElement | null) {
  if (!div) {
    console.log('div is null')
    return
  }

  nextTick(() => {
    div.scrollTop = div.scrollHeight
  })
}

const generateContent = () => {
  axios.post(route('api.ai.generate-content', props.prompt), {
    content: input.value,
  })
    .then(() => {
      getChats()
      scrollToBottom(chatDiv.value)
      waiting.value = false
    })
    .catch((error) => {
      console.log(error)
    })
}

// Function to safely render markdown
const renderMarkdown = (text: string) => {
  try {
    return marked(text)
  } catch (error) {
    console.error('Error parsing markdown:', error)
    return text
  }
}

const deleteChat = () => {
  axios.delete(route('chats.delete', props.prompt))
    .then(() => {
      getChats()
    })
}

onMounted(async () => {
  getPrompt()
  getChats()

  // Wait for the next tick to ensure DOM is rendered
  await nextTick()
  scrollToBottom(chatDiv.value)
})
</script>

<template>
  <div class="absolute bottom-0 right-0 p-4 md:w-[600px] w-full h-full">
    <Card class="w-full max-h-full">
      <CardHeader class=" flex flex-row justify-between">
        <div class="flex items-center space-x-4">
          <div class="flex flex-col gap-2">
            <Skeleton v-if="loading" class="h-4 w-32" />
            <p v-else class="text-sm font-medium leading-none">
              {{ prompt?.name }}
            </p>
            <Skeleton v-if="loading" class="h-3 w-48" />
            <p v-else class="text-sm text-muted-foreground line-clamp-2">
              {{ prompt?.description }}
            </p>
          </div>
        </div>

        <AlertDialog v-if="messages.length > 0">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger as-child>
                <AlertDialogTrigger as-child>
                  <Button size="icon" variant="ghost" class="cursor-pointer rounded-full" type="button">
                    <Eraser class="w-4 h-4" />
                  </Button>
                </AlertDialogTrigger>
              </TooltipTrigger>
              <TooltipContent>
                <p>Delete chat</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>Are you sure you want to delete this chat?</AlertDialogTitle>
              <AlertDialogDescription>
                This action cannot be undone. This will permanently delete this chat.
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel class="cursor-pointer">Cancel</AlertDialogCancel>
              <AlertDialogAction class="cursor-pointer" @click="deleteChat">Continue</AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
        <Button size="icon" variant="ghost" class="cursor-pointer rounded-full" @click="emit('close')">
          <X class="w-4 h-4" />
        </Button>
      </CardHeader>
      <CardContent class="scrollbar-custom">
        <div class="overflow-y-auto space-y-4 h-[calc(100vh-240px)] pr-4" ref="chatDiv">

          <!-- Loading skeleton for messages -->
          <template v-if="loading">
            <div v-for="i in 3" :key="`skeleton-${i}`" class="flex w-max max-w-[75%] flex-col gap-2">
              <Skeleton class="h-4 w-32" />
              <Skeleton class="h-4 w-48" />
            </div>
            <div class="flex w-max max-w-[75%] flex-col gap-2 ml-auto">
              <Skeleton class="h-4 w-24" />
            </div>
            <div v-for="i in 2" :key="`skeleton-agent-${i}`" class="flex w-max max-w-[75%] flex-col gap-2">
              <Skeleton class="h-4 w-40" />
              <Skeleton class="h-4 w-36" />
            </div>
          </template>

          <!-- Actual messages -->
          <template v-else>
            <div v-for="(message, index) in messages" :key="index" :class="cn(
              'flex w-max max-w-[75%] flex-col gap-2 rounded-lg px-3 py-2 text-sm',
              message.role === 'user' ? 'ml-auto bg-primary text-primary-foreground' : 'bg-muted',
            )">
              <div v-html="renderMarkdown(message.content)" class="prose prose-sm max-w-none overflow-x-auto">
              </div>
            </div>
          </template>

          <!-- Waiting skeleton for new message -->
          <div v-if="waiting" class="flex w-max max-w-[75%] flex-col gap-2 rounded-lg px-3 py-2 text-sm bg-muted">
            <Skeleton class="h-4 w-32" />
          </div>
        </div>
      </CardContent>
      <CardFooter>
        <form class="flex w-full items-center space-x-2" @submit.prevent="sendMessage">
          <Input v-model="input" placeholder="Type a message..." class="flex-1" :disabled="loading" />
          <Button class="p-2.5 flex items-center justify-center" :disabled="inputLength === 0 || waiting || loading"
            type="submit">
            <Loader2 class="w-4 h-4 animate-spin" v-if="waiting" />
            <Send class="w-4 h-4" />
            <span class="sr-only">Send</span>
          </Button>
        </form>
      </CardFooter>
    </Card>
  </div>
</template>
