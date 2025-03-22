<script setup lang="ts">
import { Chat } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { nextTick, onBeforeMount, onBeforeUnmount, onMounted, onUnmounted, Ref, ref } from 'vue';

import 'emoji-picker-element';

import axios from 'axios';

import { formatTimeFromTimestamp, formatTimestamp } from '@/lib/utils';

import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Popover from 'primevue/popover';
import Toast from 'primevue/toast';

import { useToast } from 'primevue/usetoast';

const toast = useToast();
const page = usePage();

const showContacts = ref(false);

const newContactEmail = ref(null);
const showAddNewContactDialog = ref(false);
const isAddingNewContact = ref(false);

const isStartingNewChat = ref(false);
const showChat = ref(false);
const currentChat: Ref<Chat> = ref({
    partner: null,
    messages: [],
    id: null,
});

const isSendingMessage = ref(false);
const currentMessage = ref('');

const messageInput = ref();
const messageBody = ref();

const newChats = ref(0);

const emojiPopover = ref();

onMounted(() => {
    newChats.value = page.props.chats.filter((chat: Chat) => chat.unread_messages > 0).length;

    let emojiPicker: HTMLElement | null = null;
    let emojiClickHandler: ((event: any) => void) | null = null;

    const observer = new MutationObserver(() => {
        const newEmojiPicker = document.querySelector('emoji-picker');

        if (newEmojiPicker && newEmojiPicker !== emojiPicker) {
            // Remove old event listener if it exists
            if (emojiPicker && emojiClickHandler) {
                emojiPicker.removeEventListener('emoji-click', emojiClickHandler);
            }

            // Update reference
            emojiPicker = newEmojiPicker;
            emojiClickHandler = (event: any) => {
                currentMessage.value += event.detail.emoji.unicode;
            };

            // Attach new event listener
            emojiPicker.addEventListener('emoji-click', emojiClickHandler);
        }

        // If emojiPicker is removed
        if (!newEmojiPicker && emojiPicker) {
            emojiPicker.removeEventListener('emoji-click', emojiClickHandler!);
            emojiPicker = null;
            emojiClickHandler = null;
        }
    });

    observer.observe(document.body, { childList: true, subtree: true });

    onUnmounted(() => {
        observer.disconnect();
        if (emojiPicker && emojiClickHandler) {
            emojiPicker.removeEventListener('emoji-click', emojiClickHandler);
        }
    });
});

onBeforeMount(() => {
    axios.defaults.headers.common['X-Socket-ID'] = window.Echo.socketId();

    // Handle incoming messages
    for (const chat of page.props.chats) {
        window.Echo.private(`chat.${chat.id}`)
            .listen('MessageSent', (e: any) => {
                setupMessageListener(e);
            })
            .listen('MessageRead', (e: any) => {
                setupMessageStatusChange(e);
            });
    }

    // Handle incoming chat creations from other users
    window.Echo.private(`chat.start.user.${page.props.auth.user.id}`).listen('ChatStarted', (e: any) => {
        router.reload({ only: ['chats'] });

        window.Echo.private(`chat.${e.chat.id}`)
            .listen('MessageSent', (e: any) => {
                setupMessageListener(e);
            })
            .listen('MessageRead', (e: any) => {
                setupMessageStatusChange(e);
            });
    });

    document.addEventListener('visibilitychange', function () {
        handleVisibilityChange();
    });

    window.addEventListener('beforeunload', function () {
        handleVisibilityChange(true);
    });
});

onBeforeUnmount(() => {
    window.Echo.leave(`chat.start.user.${page.props.auth.user.id}`);

    for (const chat of page.props.chats) {
        window.Echo.leave(`chat.${chat.id}`);
    }

    document.removeEventListener('visibilitychange', function () {
        handleVisibilityChange();
    });

    window.removeEventListener('beforeunload', function () {
        handleVisibilityChange(true);
    });
});

function setupMessageStatusChange(e: any) {
    if (currentChat.value.id === e.message.chat_id) {
        const message = currentChat.value.messages.find((message) => message.id === e.message.id);
        if (message) message.status = 'read';
    }
}

function setupMessageListener(e: any) {
    if (e.message.user_id === page.props.auth.user.id) return;

    const chat = page.props.chats.find((chat: Chat) => chat.id === e.message.chat_id);
    chat.last_message = e.message.message;
    chat.last_message_created_at = e.message.created_at;
    if (currentChat.value.id !== e.message.chat_id) {
        chat.unread_messages++;
        newChats.value = page.props.chats.filter((chat: Chat) => chat.unread_messages > 0).length;
    }

    page.props.chats.splice(page.props.chats.indexOf(chat), 1);
    page.props.chats.unshift(chat);

    if (currentChat.value.id === e.message.chat_id) {
        currentChat.value.messages.push(e.message);

        axios.patch('/message-status', {
            message_ids: [e.message.id],
        });

        nextTick(() => {
            scrollToBottom();
        });
    }
}

let timeoutId: number;
function handleVisibilityChange(exit = false) {
    clearTimeout(timeoutId);

    timeoutId = setTimeout(() => {
        axios.patch('/user-status', { active: !document.hidden && !exit });
    }, 2000);
}

const toggleEmojiPicker = (event) => {
    emojiPopover.value.toggle(event);
};

const handleContactListToggle = () => {
    showContacts.value = !showContacts.value;
};

const handleAddNewContact = () => {
    if (!newContactEmail.value || isAddingNewContact.value) return;

    isAddingNewContact.value = true;

    axios
        .post('/contact/store', {
            contact_email: newContactEmail.value,
        })
        .then(() => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'New Contact added',
                life: 5000,
            });

            newContactEmail.value = null;

            router.reload({ only: ['contacts'] });
        })
        .catch((error) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isAddingNewContact.value = false;
        });
};

const startNewChat = (email: string) => {
    if (isStartingNewChat.value) return;

    isStartingNewChat.value = true;

    axios
        .post('/chat/start', {
            email: email,
        })
        .then((response) => {
            showChat.value = true;

            if (response.data.created) {
                router.reload({ only: ['chats'] });

                window.Echo.private(`chat.${response.data.chat_id}`)
                    .listen('MessageSent', (e: any) => {
                        setupMessageListener(e);
                    })
                    .listen('MessageRead', (e: any) => {
                        setupMessageStatusChange(e);
                    });
            }

            handleChatSelection(response.data.chat_id);

            showContacts.value = false;
        })
        .catch((error) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isStartingNewChat.value = false;
        });
};

const handleChatSelection = (id: number) => {
    if (currentChat.value.id === id) return;

    axios
        .get(`/chat/${id}/messages`)
        .then((response) => {
            window.Echo.leave(`status.user.${currentChat.value.partner?.id}`);

            currentChat.value.partner = null;
            currentChat.value.messages = [];
            currentChat.value.id = null;

            currentChat.value.id = response.data.chat_id;
            currentChat.value.partner = response.data.partner;
            currentChat.value.messages.push(...response.data.messages);

            const chat = page.props.chats.find((chat: Chat) => chat.id === currentChat.value.id);
            chat.unread_messages = 0;
            newChats.value = page.props.chats.filter((chat: Chat) => chat.unread_messages > 0).length;

            window.Echo.private(`status.user.${currentChat.value.partner?.id}`).listen('UserStatusChange', (e: any) => {
                if (currentChat.value.partner) currentChat.value.partner.is_active = e.active;
            });

            const unreadMessages = currentChat.value.messages.filter(
                (message) => message.status !== 'read' && message.user_id !== page.props.auth.user.id,
            );

            if (unreadMessages.length > 0)
                axios.patch('/message-status', {
                    message_ids: unreadMessages.map((message) => message.id),
                });

            showChat.value = true;

            nextTick(() => {
                messageInput.value.focus();

                scrollToBottom();
            });
        })
        .catch((error) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        });
};

const sendMessage = () => {
    if (isSendingMessage.value || currentMessage.value.length === 0) return;

    isSendingMessage.value = true;

    axios
        .post('/chat/message', {
            chat_id: currentChat.value.id,
            message: currentMessage.value,
        })
        .then((response) => {
            currentChat.value.messages.push(response.data.message);

            const chat = page.props.chats.find((chat: Chat) => chat.id === currentChat.value.id);
            chat.last_message = response.data.message.message;
            chat.last_message_created_at = response.data.message.created_at;

            // Move the chat to the first position in the array
            page.props.chats.splice(page.props.chats.indexOf(chat), 1);
            page.props.chats.unshift(chat);
        })
        .catch((error) => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response.data.message ?? error.response.data,
                life: 5000,
            });
        })
        .finally(() => {
            isSendingMessage.value = false;

            currentMessage.value = '';

            nextTick(() => {
                messageInput.value.focus();

                scrollToBottom();
            });
        });
};

function scrollToBottom() {
    messageBody.value.scrollTo(0, messageBody.value.scrollHeight);
}
</script>

<template>
    <Toast />

    <Head :title="newChats > 0 ? `(${newChats.toString()})` : ''" />

    <!-- Source Code for frontend: https://codepen.io/macridgway23/pen/rNMgRgY -->

    <!-- Only optimized for viewing on desktop -->
    <div class="flex h-screen w-full bg-black">
        <aside
            class="relative flex w-[22rem] shrink-0 flex-col overflow-y-auto border-r border-gray-800 bg-gray-200 md:w-[30rem] lg:w-[35rem] xl:w-[40rem]"
        >
            <div class="aside-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center bg-[#131C21] px-4 py-6">
                    <div class="text-2xl font-bold text-white">{{ showContacts ? 'New Chat' : 'Chats' }}</div>
                    <div v-if="showContacts" class="flex-1 text-right">
                        <span class="pi pi-arrow-left cursor-pointer" v-tooltip="'Back'" @click="handleContactListToggle"></span>
                    </div>
                    <div v-else class="flex-1 text-right">
                        <span
                            class="pi pi-pen-to-square mr-6 inline cursor-pointer"
                            v-tooltip="'New Chat'"
                            style="font-size: large"
                            @click="handleContactListToggle"
                        ></span>
                        <span class="pi pi-ellipsis-v inline cursor-pointer" style="font-size: large"></span>
                    </div>
                </div>
                <div class="search-bar w-full px-4 py-2">
                    <form @submit.prevent>
                        <div class="relative text-gray-600 focus-within:text-gray-200">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-2">
                                <button type="submit" class="focus:shadow-outline p-1 focus:outline-none">
                                    <svg
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                        class="h-4 w-4 text-gray-300"
                                    >
                                        <path d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </button>
                            </span>
                            <input
                                class="w-full rounded-full bg-gray-600 py-2 pl-10 text-sm text-white focus:bg-gray-600/50 focus:outline-none"
                                placeholder="Search or start new chat"
                                autocomplete="off"
                            />
                        </div>
                    </form>
                </div>
            </div>
            <div v-if="showContacts" class="aside-messages grow select-none">
                <div
                    class="message cursor-pointer border-gray-700 px-4 py-3 text-gray-300 hover:bg-gray-600/50"
                    @click="showAddNewContactDialog = true"
                >
                    <div class="relative flex items-center">
                        <div class="w-1/6">
                            <Avatar class="mr-2" icon="pi pi-user" style="background-color: #00a884" size="large" shape="circle" />
                        </div>
                        <div class="w-5/6">
                            <div class="text-xl text-white" id="personName">New Contact</div>
                        </div>
                    </div>
                </div>

                <div class="px-4 py-4 text-green-400">Me</div>
                <div class="message cursor-pointer border-gray-700 px-4 py-3 text-gray-300 hover:bg-gray-600/50">
                    <div class="relative flex items-center">
                        <div class="w-1/6">
                            <Avatar :label="$page.props.auth.user.name[0]" class="mr-2" size="large" shape="circle" />
                        </div>
                        <div class="w-5/6">
                            <div class="text-xl text-white" id="personName">{{ $page.props.auth.user.name }}</div>
                            <div class="truncate text-sm" id="messagePreview">{{ $page.props.auth.user.about }}</div>
                        </div>
                        <span class="absolute right-0 top-0 mt-1 text-xs">{{ $page.props.auth.user.email }}</span>
                    </div>
                </div>

                <div class="px-4 py-6 text-green-400">My Contacts</div>
                <div class="flex-col divide-y-2">
                    <div
                        v-for="contact in $page.props.contacts"
                        :key="contact.id"
                        class="message cursor-pointer border-gray-700 px-4 py-3 text-gray-300 hover:bg-gray-600/50"
                        @click="startNewChat(contact.email)"
                    >
                        <div class="relative flex items-center">
                            <div class="w-1/6">
                                <Avatar :label="contact.name[0]" class="mr-2" size="large" shape="circle" />
                            </div>
                            <div class="w-5/6">
                                <div class="text-xl text-white" id="personName">{{ contact.name }}</div>
                                <div class="truncate text-sm" id="messagePreview">{{ contact.about }}</div>
                            </div>
                            <span class="absolute right-0 top-0 mt-1 text-xs">{{ contact.email }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else class="aside-messages grow">
                <div
                    v-for="chat in $page.props.chats"
                    :key="chat.id"
                    class="message cursor-pointer border-b border-gray-700 px-4 py-3 text-gray-300 hover:bg-gray-600/50"
                    @click="handleChatSelection(chat.id)"
                >
                    <div class="relative flex select-none items-center">
                        <div class="w-1/6">
                            <Avatar :label="chat.partner.name[0]" class="mr-2" size="large" shape="circle" />
                        </div>
                        <div class="w-5/6">
                            <div class="text-xl text-white" id="personName">{{ chat.partner.name }}</div>
                            <div class="truncate text-sm" id="messagePreview">{{ chat.last_message }}</div>
                        </div>
                        <span class="absolute right-0 top-0 mt-1 text-xs">{{
                            chat.last_message_created_at ? formatTimestamp(chat.last_message_created_at) : ''
                        }}</span>
                        <span v-if="chat.unread_messages > 0" class="absolute bottom-0 right-0 mt-1 text-xs text-green-400">
                            {{ chat.unread_messages }} new
                        </span>
                    </div>
                </div>
            </div>
        </aside>
        <div v-if="!showChat" class="flex w-full bg-[#222E35]">
            <div class="m-auto flex flex-col items-center gap-4">
                <div class="pi pi-comments" style="font-size: 8rem"></div>
                <div class="text-sm text-gray-200">
                    WhatsApp Clone - <a class="underline" href="https://github.com/BlackyDrum/whatsapp-clone">Github</a>
                </div>
            </div>
        </div>
        <main v-else ref="messageBody" id="messageBody" class="bg-whatsapp relative flex w-full flex-col overflow-y-auto">
            <div class="main-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center px-4 py-3">
                    <div class="flex-1 truncate">
                        <div class="flex">
                            <div class="mr-4">
                                <Avatar :label="currentChat.partner?.name[0]" class="mr-2" size="large" shape="circle" />
                            </div>
                            <div>
                                <p class="text-md font-bold text-white">{{ currentChat.partner?.name }}</p>
                                <p v-if="!currentChat.partner?.is_active" class="text-sm text-gray-400">
                                    last seen {{ formatTimestamp(currentChat.partner?.last_seen || new Date()) }}
                                </p>
                                <p v-else class="text-sm text-green-400">Online</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 text-right">
                        <span class="pi pi-search mr-5 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-ellipsis-v inline h-6 w-6 cursor-pointer"> </span>
                    </div>
                </div>
            </div>
            <div class="main-messages block grow px-4 py-3">
                <div
                    v-for="message in currentChat.messages"
                    :key="message.id"
                    class="flex"
                    :class="{ 'justify-end': message.user_id === $page.props.auth.user.id }"
                >
                    <div
                        class="single-message mb-4 max-w-[90%] break-words rounded-bl-lg rounded-br-lg rounded-tl-lg px-4 py-2 text-gray-200"
                        :class="{ user: message.user_id === $page.props.auth.user.id }"
                    >
                        {{ message.message }}
                        <span class="relative top-1 ml-2 inline-flex gap-[3px]">
                            <span class="inline-block select-none text-xs text-gray-200/70">{{ formatTimeFromTimestamp(message.created_at) }}</span>
                            <span
                                v-if="message.user_id === $page.props.auth.user.id"
                                class="relative top-[3px] inline-block select-none text-xs text-blue-400"
                                :class="{ 'text-gray-200/70': message.status !== 'read' }"
                            >
                                <svg viewBox="0 0 16 11" height="11" width="16" preserveAspectRatio="xMidYMid meet" class="" fill="none">
                                    <title>{{ message.status }}</title>
                                    <path
                                        d="M11.0714 0.652832C10.991 0.585124 10.8894 0.55127 10.7667 0.55127C10.6186 0.55127 10.4916 0.610514 10.3858 0.729004L4.19688 8.36523L1.79112 6.09277C1.7488 6.04622 1.69802 6.01025 1.63877 5.98486C1.57953 5.95947 1.51817 5.94678 1.45469 5.94678C1.32351 5.94678 1.20925 5.99544 1.11192 6.09277L0.800883 6.40381C0.707784 6.49268 0.661235 6.60482 0.661235 6.74023C0.661235 6.87565 0.707784 6.98991 0.800883 7.08301L3.79698 10.0791C3.94509 10.2145 4.11224 10.2822 4.29844 10.2822C4.40424 10.2822 4.5058 10.259 4.60313 10.2124C4.70046 10.1659 4.78086 10.1003 4.84434 10.0156L11.4903 1.59863C11.5623 1.5013 11.5982 1.40186 11.5982 1.30029C11.5982 1.14372 11.5348 1.01888 11.4078 0.925781L11.0714 0.652832ZM8.6212 8.32715C8.43077 8.20866 8.2488 8.09017 8.0753 7.97168C7.99489 7.89128 7.8891 7.85107 7.75791 7.85107C7.6098 7.85107 7.4892 7.90397 7.3961 8.00977L7.10411 8.33984C7.01947 8.43717 6.97715 8.54508 6.97715 8.66357C6.97715 8.79476 7.0237 8.90902 7.1168 9.00635L8.1959 10.0791C8.33132 10.2145 8.49636 10.2822 8.69102 10.2822C8.79681 10.2822 8.89838 10.259 8.99571 10.2124C9.09304 10.1659 9.17556 10.1003 9.24327 10.0156L15.8639 1.62402C15.9358 1.53939 15.9718 1.43994 15.9718 1.32568C15.9718 1.1818 15.9125 1.05697 15.794 0.951172L15.4386 0.678223C15.3582 0.610514 15.2587 0.57666 15.1402 0.57666C14.9964 0.57666 14.8715 0.635905 14.7657 0.754395L8.6212 8.32715Z"
                                        fill="currentColor"
                                    ></path>
                                </svg>
                            </span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="main-footer sticky bottom-0 left-0 right-0 text-gray-400">
                <div class="flex items-center px-4 py-1">
                    <Popover ref="emojiPopover">
                        <emoji-picker></emoji-picker>
                    </Popover>
                    <div class="flex-none">
                        <span @click="toggleEmojiPicker" class="pi pi-face-smile -mt-1 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-paperclip -mt-1 ml-2 inline h-6 w-6 cursor-pointer"> </span>
                    </div>
                    <div class="flex-grow">
                        <div class="w-full px-4 py-2">
                            <form @submit.prevent="sendMessage">
                                <div class="relative text-gray-600 focus-within:text-gray-200">
                                    <input
                                        ref="messageInput"
                                        class="message-input w-full rounded-full bg-gray-700 py-3 pl-5 text-sm text-white focus:bg-gray-600/50 focus:outline-none"
                                        placeholder="Type a message"
                                        autocomplete="off"
                                        v-model="currentMessage"
                                        :disabled="isSendingMessage"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="flex-none text-right">
                        <span :class="{ 'pi pi-spin pi-spinner': isSendingMessage }" class="inline cursor-pointer" style="font-size: x-large"> </span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add new contact Dialog -->
    <Dialog v-model:visible="showAddNewContactDialog" :draggable="false" modal header="Add Contact" :style="{ width: '25rem' }">
        <form @submit.prevent="handleAddNewContact">
            <div class="relative text-gray-600 focus-within:text-gray-200">
                <input
                    v-model="newContactEmail"
                    class="message-input w-full rounded-full bg-gray-700 py-3 pl-5 text-sm text-white focus:bg-gray-600/50 focus:outline-none"
                    placeholder="Email of your contact"
                />
                <Button type="submit" :icon="isAddingNewContact ? 'pi pi-spin pi-spinner' : ''" label="Add Contact" class="mt-3 w-full" />
            </div>
        </form>
    </Dialog>
</template>

<style>
.p-message-error {
    word-break: break-word;
}
.p-toast {
    max-width: calc(100vw - 40px);
    word-break: break-word;
}

.p-popover-content {
    padding: 0 !important;
}
</style>
