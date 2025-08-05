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
import { Send, X, Loader2, Eraser, File } from 'lucide-vue-next'
import axios from 'axios'
import { router } from '@inertiajs/vue3'
import { marked } from 'marked'

const props = defineProps<{
  prompt?: string
}>()

const emit = defineEmits(['close'])

const input = ref('')
const inputLength = computed(() => input.value.trim().length)
const loading = ref(true)
const waiting = ref(false);
const chatDiv = ref<HTMLDivElement | null>(null);

const messages = ref<{
  role: 'user' | 'agent'
  content: string
}[]>([]);

const prompt = ref<{
  name: string
  description: string
  json_schema: any
  files: Array<{
    id: number
    name: string
    path: string
    mime_type: string
    size: number
  }>
} | null>(null)

const getPrompt = () => {
  axios.get(route('prompts.get', props.prompt))
    .then((response) => {
      prompt.value = response.data

      // Carregar arquivos existentes do prompt
      if (response.data.files && response.data.files.length > 0) {
        loadExistingFiles(response.data.files)
      }
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

  router.post(route('chats.store'), {
    prompt_id: props.prompt,
    role: 'user',
    text: input.value,
  }, {
    onSuccess: () => {
      getChats()
      scrollToBottom(chatDiv.value)

      attachFiles()

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

const generateContent = () => {
  waiting.value = true

  const formData = new FormData();
  formData.append('content', input.value);

  selectedFiles.value.forEach((file, index) => {
    formData.append(`files[${index}]`, file);
  });

  axios.post(route('api.ai.generate-content', props.prompt), formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  }).then((response) => {
    waiting.value = false

    getChats()
    scrollToBottom(chatDiv.value)

  }).catch((error) => {
    console.log(error)
  })
}

const deleteChat = () => {
  axios.delete(route('chats.delete', props.prompt))
    .then(() => {
      getChats()
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

const scrollToBottom = (div: HTMLDivElement | null) => {
  if (!div) {
    console.log('div is null')
    return
  }

  nextTick(() => {
    div.scrollTop = div.scrollHeight
  })
}

const loadExistingFiles = async (files: any[]) => {
  for (const fileData of files) {
    try {
      // Fazer download do arquivo
      const response = await axios.get(`/storage/${fileData.path}`, {
        responseType: 'blob'
      })

      // Criar File object a partir do blob
      const file = new (window as any).File([response.data], fileData.name)

        // Adicionar propriedades customizadas para identificar arquivos existentes
        ; (file as any).isExisting = true
        ; (file as any).fileId = fileData.id

      selectedFiles.value.push(file)
    } catch (error) {
      console.error('Erro ao carregar arquivo:', fileData.name, error)
    }
  }
}

const attachFiles = () => {
  if (!props.prompt) return

  const formData = new FormData();

  // Adicionar novos arquivos
  selectedFiles.value.forEach((file, index) => {
    if (!(file as any).isExisting) {
      formData.append(`files[${index}]`, file);
    }
  });

  // Adicionar IDs dos arquivos existentes que devem permanecer
  const existingFileIds = selectedFiles.value
    .filter(file => (file as any).isExisting)
    .map(file => (file as any).fileId);

  existingFileIds.forEach((id, index) => {
    formData.append(`existing_files[${index}]`, id.toString());
  });

  axios.post(route('prompts.attach-files', props.prompt), formData, {
    headers: {
      'Content-Type': 'multipart/form-data',
    },
  }).then((response) => {
    console.log(response)
    // Atualizar a lista de arquivos com a resposta do servidor
    if (response.data.files) {
      // Limpar arquivos existentes e recarregar
      selectedFiles.value = selectedFiles.value.filter(file => !(file as any).isExisting)
      loadExistingFiles(response.data.files)
    }
  }).catch((error) => {
    console.log(error)
  })
}

const fileInput = ref<HTMLInputElement>()
const selectedFiles = ref<File[]>([])

const handleFileUpload = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (file) {
    selectedFiles.value.push(file)

    target.value = ''
  }
}

const removeFile = (index: number) => {
  selectedFiles.value.splice(index, 1)
}

const handleFileButtonClick = () => {
  if (fileInput.value) {
    fileInput.value.click()
  }
}

const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

onMounted(async () => {
  getPrompt()
  getChats()

  await nextTick()
  scrollToBottom(chatDiv.value)
})
</script>

<template>
  <div class="fixed bottom-0 right-0 p-4 md:w-[600px] w-full h-full flex items-end">
    <Card class="w-full max-h-full">
      <CardHeader class=" flex flex-row justify-between">

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

        <div class="flex items-center space-x-2">
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
        </div>
      </CardHeader>
      <CardContent class="scrollbar-custom h-full">
        <div class="overflow-y-auto space-y-4 h-[calc(100vh-340px)] pr-4" ref="chatDiv">

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
        <form @submit.prevent="sendMessage" class="flex flex-col gap-2 w-full">
          <!-- Selected files -->
          <div v-if="selectedFiles.length > 0" class="mt-3 space-y-2">
            <div class="text-sm font-medium text-muted-foreground">Attachments:</div>
            <div class="space-y-2">
              <div v-for="(file, index) in selectedFiles" :key="index"
                class="flex items-center justify-between p-2 bg-muted rounded-md">
                <div class="flex items-center px-2">
                  <div class="flex flex-wrap gap-1 items-end">
                    <span class="text-sm font-medium">{{ file.name }}</span>
                    <span class="text-xs text-muted-foreground">({{ formatFileSize(file.size) }})</span>
                  </div>
                </div>
                <Button size="icon" variant="ghost" class="h-6 w-6 cursor-pointer" type="button"
                  @click="removeFile(index)">
                  <X class="w-3 h-3" />
                </Button>
              </div>
            </div>
          </div>

          <input id="file-upload" ref="fileInput" type="file" class="hidden" @change="handleFileUpload"
            accept=".txt,.pdf,.doc,.docx,.jpg,.jpeg,.png" />
          <div class="flex w-full items-center">
            <Button variant="outline" class="cursor-pointer rounded-full mr-2" size="icon" type="button"
              @click="handleFileButtonClick">
              <File class="w-4 h-4" />
            </Button>
            <Input v-model="input" placeholder="Type a message..." class="flex-1 rounded-full rounded-r-none"
              :disabled="loading" />
            <Button class="p-2.5 flex items-center justify-center rounded-full rounded-l-none"
              :disabled="inputLength === 0 || waiting || loading" type="submit">
              <Loader2 class="w-4 h-4 animate-spin" v-if="waiting" />
              <Send class="w-4 h-4" />
              <span class="sr-only">Send</span>
            </Button>
          </div>
        </form>


      </CardFooter>
    </Card>
  </div>
</template>
