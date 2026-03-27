<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="qr-code" :href="route('qr-code')" :current="request()->routeIs('qr-code')"
                    wire:navigate>
                    {{ __('QR Code') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="shopping-cart" :href="route('kitchen.index')"
                    :current="request()->routeIs('kitchen.index')" wire:navigate>
                    {{ __('Kitchen') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="banknotes" :href="route('cashier.index')"
                    :current="request()->routeIs('cashier.index')" wire:navigate>
                    {{ __('Cashier') }}
                </flux:sidebar.item>

                @can('change menu')
                <flux:navlist>
                    {{-- Admin Section --}}
                    <div x-data="{ open: @js(request()->routeIs('admin.*')) }">
                        <flux:sidebar.item icon="cog" x-on:click.prevent="open = !open"
                            class="cursor-pointer flex justify-between items-center group" {{-- Warna teks saat aktif
                            menggunakan Primary Orange --}}
                            :class="request()->routeIs('admin.*') ? 'text-brand font-bold bg-brand-light/50' : 'text-slate-600'">
                            <div class="flex justify-between items-center">
                                <span>{{ __('Admin') }}</span>
                                <div :class="open ? 'rotate-180 text-brand' : ''"
                                    class="transition-transform duration-200">
                                    <flux:icon name="chevron-down" variant="micro" />
                                </div>
                            </div>
                        </flux:sidebar.item>

                        @can('change central')
                        <div x-show="open" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                            <flux:sidebar.item :href="route('admin.menu-management')"
                                :current="request()->routeIs('admin.menu-management')" wire:navigate size="sm" {{--
                                Hover state halus dengan warna accent --}}
                                class="hover:text-brand hover:bg-brand-light/30">
                                {{ __('Menu - Categories') }}
                            </flux:sidebar.item>
                        </div>
                        @endcan
                        @can('change branch')
                        <div x-show="open" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                            <flux:sidebar.item :href="route('admin.branch-menu-management')"
                                :current="request()->routeIs('admin.branch-menu-management')" wire:navigate size="sm"
                                {{-- Hover state halus dengan warna accent --}}
                                class="hover:text-brand hover:bg-brand-light/30">
                                {{ __('Branch Menu') }}
                            </flux:sidebar.item>
                        </div>
                        @endcan
                        @can('change central')
                        <div x-show="open" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                            <flux:sidebar.item :href="route('admin.user-management')"
                                :current="request()->routeIs('admin.user-management')" wire:navigate size="sm" {{--
                                Hover state halus dengan warna accent --}}
                                class="hover:text-brand hover:bg-brand-light/30">
                                {{ __('Users') }}
                            </flux:sidebar.item>
                        </div>
                        <div x-show="open" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                            <flux:sidebar.item :href="route('admin.role-permission')"
                                :current="request()->routeIs('admin.role-permission')" wire:navigate size="sm" {{--
                                Hover state halus dengan warna accent --}}
                                class="hover:text-brand hover:bg-brand-light/30">
                                {{ __('Role - Permission') }}
                            </flux:sidebar.item>
                        </div>
                        <div x-show="open" x-collapse x-cloak class="pl-8 mt-1 space-y-1">
                            <flux:sidebar.item :href="route('admin.global-settings')"
                                :current="request()->routeIs('admin.global-settings')" wire:navigate size="sm" {{--
                                Hover state halus dengan warna accent --}}
                                class="hover:text-brand hover:bg-brand-light/30">
                                {{ __('Global Settings') }}
                            </flux:sidebar.item>
                        </div>
                        @endcan
                    </div>
                </flux:navlist>
                @endcan
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item icon="computer-desktop" href="https://hellojonathan.my.id/" :current="false">
                {{ __('HelloJonathan') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}
    <x-toaster />
    @fluxScripts
    <script>
        window.addEventListener('play-sound', event => {
        // Daftar semua suara di satu tempat (Centralized)
        const sounds = {
            'kitchen': '/sounds/notification.mp3',
            'guest': '/sounds/notification.mp3',
            'cashier': '/sounds/notification.mp3',
            'default': '/sounds/notification.mp3'
        };

        const soundPath = sounds[event.detail.type] || sounds['default'];
        const audio = new Audio(soundPath);
        
        audio.play().catch(error => {
            console.log("Autoplay dicegah. Kasir harus klik layar dulu.");
        });
    });
    </script>
</body>

</html>