<script setup lang="ts"></script>

<template>
    <!-- Only optimized for viewing on desktop -->
    <div class="bor flex h-screen w-full bg-black">
        <aside class="relative block overflow-y-auto border-r border-gray-800 bg-gray-200">
            <div class="aside-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center px-4 py-3">
                    <div class="flex-1">
                        <img class="h-11 w-11 rounded-full" />
                    </div>
                    <div class="flex-1 text-right">
                        <span class="pi pi-face-smile mr-4 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-comment mr-3 inline h-6 w-6 cursor-pointer"> </span>
                        <span class="pi pi-ellipsis-v inline h-6 w-6 cursor-pointer"> </span>
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
            <div id="mainMessages" class="aside-messages h-full"></div>
        </aside>
        <main id="messageBody" class="bg-whatsapp relative w-full overflow-y-auto">
            <div class="main-header sticky left-0 right-0 top-0 z-40 text-gray-400">
                <div class="flex items-center px-4 py-3">
                    <div class="flex-1">
                        <div class="flex">
                            <div class="mr-4">
                                <img class="h-11 w-11 rounded-full" />
                            </div>
                            <div>
                                <p class="text-md font-bold text-white">Josh Peters</p>
                                <p class="text-sm text-gray-400">last seen today at 14:46</p>
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
                <div class="flex justify-end">
                    <div class="single-message user mb-4 rounded-bl-lg rounded-br-lg rounded-tl-lg px-4 py-2 text-gray-200">
                        Hey! Thought I'd reach out to say how are you? 😊
                        <span class="inline-block text-xs">13:00 <img class="-mt-1 ml-2 inline h-4 w-4" alt="" /></span>
                    </div>
                    <span
                        ><svg class="user-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 13" width="8" height="13">
                            <path opacity=".13" d="M5.188 1H0v11.193l6.467-8.625C7.526 2.156 6.958 1 5.188 1z"></path>
                            <path fill="currentColor" d="M5.188 0H0v11.193l6.467-8.625C7.526 1.156 6.958 0 5.188 0z"></path></svg
                    ></span>
                </div>
                <div class="flex">
                    <span
                        ><svg class="incoming-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 8 13" width="8" height="13">
                            <path opacity=".13" d="M5.188 1H0v11.193l6.467-8.625C7.526 2.156 6.958 1 5.188 1z"></path>
                            <path fill="currentColor" d="M5.188 0H0v11.193l6.467-8.625C7.526 1.156 6.958 0 5.188 0z"></path></svg
                    ></span>
                    <div class="single-message mb-4 rounded-bl-lg rounded-br-lg rounded-tr-lg px-4 py-2 text-gray-200">
                        Hey Pal! I'm doing good, how have you been? Cold at the moment aye!
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
</template>
