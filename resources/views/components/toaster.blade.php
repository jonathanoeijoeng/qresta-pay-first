<div x-data="{ 
        messages: [], 
        remove(id) { 
            this.messages = this.messages.filter(m => m.id !== id) 
        } 
     }" @toast.window="
        let id = Date.now();
        messages.push({ id, type: $event.detail.type || 'info', text: $event.detail.text });
        setTimeout(() => remove(id), 3000);
     " class="fixed bottom-5 right-5 z-[100] flex flex-col gap-3">

    <template x-for="msg in messages" :key="msg.id">
        <div x-show="true" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 scale-95" :class="{
            'border-l-green-500 bg-green-100': msg.type === 'success',
            'border-l-red-500 bg-red-100': msg.type === 'error',
            'border-l-blue-500 bg-blue-100': msg.type === 'info'
        }"
            class="px-5 py-5 rounded shadow-2xl text-slate-700 flex items-center justify-between min-w-[300px] border border-slate-200 border-l-8 backdrop-blur-md">

            <div class="flex items-center gap-3">
                <template x-if="msg.type === 'success'"><span
                        class="text-green-500 text-xl font-bold">✓</span></template>
                <template x-if="msg.type === 'error'"><span class="text-red-500 text-xl font-bold">✕</span></template>
                <span x-text="msg.text" class="text-sm"></span>
            </div>

            <button @click="remove(msg.id)" class="ml-4 text-slate-400 hover:text-slate-600">&times;</button>
        </div>
    </template>
</div>