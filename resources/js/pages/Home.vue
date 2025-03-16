<script setup lang="ts">
import { Chat } from '@/types';
import { router } from '@inertiajs/vue3';
import { Ref, ref } from 'vue';

import axios from 'axios';

import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import Toast from 'primevue/toast';

import { useToast } from 'primevue/usetoast';

const toast = useToast();

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

            if (response.data.created) router.reload({ only: ['chats'] });

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
            currentChat.value.partner = null;
            currentChat.value.messages = [];
            currentChat.value.id = null;

            currentChat.value.id = response.data.chat_id;
            currentChat.value.partner = response.data.partner;

            const messages = response.data.messages;
            for (const message of messages) {
                currentChat.value.messages.push(message);
            }

            showChat.value = true;
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

function formatTimeFromTimestamp(timestamp: Date) {
    const date = new Date(timestamp);

    // Extract hours and minutes
    const hours = date.getHours();
    const minutes = date.getMinutes();

    // Format the time to always show two digits (e.g., "09" for minutes or hours less than 10)
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
}

function formatTimestamp(timestamp: Date): string {
    const date = new Date(timestamp);
    const now = new Date();

    // Convert both to timestamps (number)
    const nowTimestamp: number = now.getTime();
    const dateTimestamp: number = date.getTime();

    // Check if the timestamp is from today
    if (date.toDateString() === now.toDateString()) {
        // If today, return just the time (HH:mm)
        const hours = date.getHours();
        const minutes = date.getMinutes();
        return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}`;
    }

    // Check if the timestamp is within the last 4 days
    const daysDifference = Math.floor((nowTimestamp - dateTimestamp) / (1000 * 3600 * 24));
    if (daysDifference <= 4) {
        // If within 4 days, return the day of the week
        return date.toLocaleDateString('en-US', { weekday: 'long' });
    }

    // If older than 4 days, return the full date (YYYY-MM-DD)
    const fullDate = date.toISOString().split('T')[0]; // Extract just the date part
    return fullDate;
}
</script>

<template>
    <Toast />

    <!-- Source Code for frontend: https://codepen.io/macridgway23/pen/rNMgRgY -->

    <!-- Only optimized for viewing on desktop -->
    <div class="flex h-screen w-full bg-black">
        <aside class="relative block overflow-y-auto border-r border-gray-800 bg-gray-200">
            <div class="aside-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center bg-[#131C21] px-4 py-6">
                    <div class="text-2xl font-bold text-white">{{ showContacts ? 'New Chat' : 'Chats' }}</div>
                    <div v-if="showContacts" class="flex-1 text-right">
                        <span class="pi pi-arrow-left cursor-pointer" v-tooltip="'Back'" @click="handleContactListToggle"></span>
                    </div>
                    <div v-else class="flex-1 text-right">
                        <span
                            class="pi pi-comment mr-6 inline cursor-pointer"
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
            <div v-if="showContacts" class="aside-messages h-full">
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
            <div v-else class="aside-messages h-full">
                <div
                    v-for="chat in $page.props.chats"
                    :key="chat.id"
                    class="message cursor-pointer border-b border-gray-700 px-4 py-3 text-gray-300 hover:bg-gray-600/50"
                    @click="handleChatSelection(chat.id)"
                >
                    <div class="relative flex items-center">
                        <div class="w-1/6">
                            <Avatar :label="chat.partner.name[0]" class="mr-2" size="large" shape="circle" />
                        </div>
                        <div class="w-5/6">
                            <div class="text-xl text-white" id="personName">{{ chat.partner.name }}</div>
                            <div class="truncate text-sm" id="messagePreview">{{ chat.last_message }}</div>
                        </div>
                        <span class="absolute right-0 top-0 mt-1 text-xs">{{ formatTimestamp(chat.last_message_created_at) }}</span>
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
        <main v-else id="messageBody" class="bg-whatsapp relative w-full overflow-y-auto">
            <div class="main-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center px-4 py-3">
                    <div class="flex-1">
                        <div class="flex">
                            <div class="mr-4">
                                <Avatar :label="currentChat.partner?.name[0]" class="mr-2" size="large" shape="circle" />
                            </div>
                            <div>
                                <p class="text-md font-bold text-white">{{ currentChat.partner?.name }}</p>
                                <p class="text-sm text-gray-400">last seen {{ formatTimestamp(currentChat.partner?.last_seen || new Date()) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 text-right">
                        <span class="pi pi-search mr-5 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-ellipsis-v inline h-6 w-6 cursor-pointer"> </span>
                    </div>
                </div>
            </div>
            <div class="main-messages block h-full px-4 py-3">
                <div
                    v-for="message in currentChat.messages"
                    :key="message.id"
                    class="flex"
                    :class="{ 'justify-end': message.user_id === $page.props.auth.user.id }"
                >
                    <div
                        class="single-message mb-4 rounded-bl-lg rounded-br-lg rounded-tl-lg px-4 py-2 text-gray-200"
                        :class="{ user: message.user_id === $page.props.auth.user.id }"
                    >
                        {{ message.message }}
                        <span class="inline-block text-xs">{{ formatTimeFromTimestamp(message.created_at) }}</span>
                    </div>
                </div>
            </div>
            <div class="main-footer sticky bottom-0 left-0 right-0 text-gray-400">
                <div class="flex items-center px-4 py-1">
                    <div class="flex-none">
                        <span class="pi pi-face-smile -mt-1 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-paperclip -mt-1 ml-2 inline h-6 w-6 cursor-pointer"> </span>
                    </div>
                    <div class="flex-grow">
                        <div class="w-full px-4 py-2">
                            <form @submit.prevent>
                                <div class="relative text-gray-600 focus-within:text-gray-200">
                                    <input
                                        class="message-input w-full rounded-full bg-gray-700 py-3 pl-5 text-sm text-white focus:bg-gray-600/50 focus:outline-none"
                                        placeholder="Type a message"
                                        autocomplete="off"
                                    />
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="flex-none text-right">
                        <span class="pi pi-microphone inline cursor-pointer" style="font-size: x-large"> </span>
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
</style>
