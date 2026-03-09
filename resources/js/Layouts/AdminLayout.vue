<template>
  <div class="min-h-screen bg-white font-sans selection:bg-rose-500 selection:text-white text-slate-900 pb-20">
    <Head :title="title" />
    
    <!-- Sidebar Navigation -->
    <aside class="fixed top-0 left-0 h-full w-72 bg-white border-r border-gray-300 z-50 hidden lg:block shadow-sm overflow-y-auto py-6">
      <div class="flex flex-col h-full p-8">
        <!-- Brand -->
        <div class="flex items-center space-x-4 mb-12 group">
          <div class="w-12 h-12 bg-white border border-gray-100 rounded-2xl flex items-center justify-center shadow-sm group-hover:border-rose-500/30 transition-all overflow-hidden">
            <template v-if="$page.props.branding?.logo">
              <img :src="$page.props.branding.logo" class="w-full h-full object-contain" :alt="$page.props.branding.name" />
            </template>
            <template v-else>
              <svg class="w-6 h-6 text-rose-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </template>
          </div>
          <div class="flex-1 min-w-0">
            <h1 class="text-xl font-black text-slate-900 tracking-tight uppercase truncate">{{ $page.props.branding?.name || 'Admin' }}</h1>
            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mt-0.5">Admin Control</p>
          </div>
        </div>

        <!-- Links -->
        <nav class="flex-1 space-y-2">
          <Link
            v-for="item in navItems"
            :key="item.name"
            :href="item.href"
            class="flex items-center gap-4 px-6 py-4 rounded-2xl text-[11px] font-black uppercase tracking-widest transition-all group"
            :class="isActive(item) ? 'bg-rose-600 text-white shadow-sm shadow-rose-200' : 'text-slate-500 hover:text-slate-900 hover:bg-gray-100'"
          >
            <!-- SVGs -->
            <svg v-if="item.icon === 'overview'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>
            <svg v-else-if="item.icon === 'users'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <svg v-else-if="item.icon === 'orgs'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
            <svg v-else-if="item.icon === 'props'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9-3-9m-9 9a9 9 0 019-9" /></svg>
            <svg v-else-if="item.icon === 'invites'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            <svg v-else-if="item.icon === 'keywords'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" /></svg>
            <svg v-else-if="item.icon === 'sitemaps'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" /></svg>
            <svg v-else-if="item.icon === 'schemas'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
            <svg v-else-if="item.icon === 'cms'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" /></svg>
            <svg v-else-if="item.icon === 'services'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" /></svg>
            <svg v-else-if="item.icon === 'logs'" class="w-5 h-5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
            
            {{ item.name }}
          </Link>
        </nav>

        <!-- User Profile & Logout -->
        <div class="mt-auto pt-8 border-t border-gray-100">
          <div class="flex items-center gap-4 mb-6 px-2">
            <div class="w-10 h-10 rounded-xl bg-gray-50 border border-gray-100 flex items-center justify-center text-rose-500 font-bold overflow-hidden shadow-sm">
                <img v-if="$page.props.auth.user.profile_photo_url" :src="$page.props.auth.user.profile_photo_url" class="w-full h-full object-cover" />
                <span v-else>{{ $page.props.auth.user.name.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-black text-slate-900 truncate uppercase">{{ $page.props.auth.user.name }}</p>
              <p class="text-[9px] text-slate-400 font-bold truncate tracking-tight lowercase">{{ $page.props.auth.user.email }}</p>
            </div>
          </div>
          <Link 
            :href="route('admin.logout')" 
            method="post" 
            as="button"
            class="w-full flex items-center justify-center gap-3 bg-white hover:bg-rose-50 hover:text-rose-600 text-slate-400 py-2 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all border border-gray-200 hover:border-rose-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            Terminate Session
          </Link>
        </div>
      </div>
    </aside>

    <!-- Main Content Area -->
    <main class="lg:ml-72 p-8 lg:p-12">
      <!-- Top Bar (Mobile) -->
      <header class="lg:hidden flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <div class="w-10 h-10 bg-white border border-gray-200 rounded-xl flex items-center justify-center shadow-sm overflow-hidden">
                <template v-if="$page.props.branding?.logo">
                    <img :src="$page.props.branding.logo" class="w-full h-full object-contain" :alt="$page.props.branding.name" />
                </template>
                <template v-else>
                    <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </template>
            </div>
            <h1 class="text-lg font-black text-slate-900 uppercase tracking-tight italic">{{ $page.props.branding?.name || 'Admin' }}</h1>
        </div>
        <button class="text-slate-500 p-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16m-7 6h7"/></svg>
        </button>
      </header>

      <slot />
    </main>

    <Toaster />
  </div>
</template>

<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'
import Toaster from '../Components/Toaster.vue'

const props = defineProps({
  title: String,
})

const page = usePage()

const navItems = [
  { name: 'Overview', href: route('admin.dashboard'), icon: 'overview', tab: '' },
  { name: 'Users', href: route('admin.dashboard') + '?tab=users', icon: 'users', tab: 'users' },
  { name: 'Organizations', href: route('admin.dashboard') + '?tab=organizations', icon: 'orgs', tab: 'organizations' },
  { name: 'Properties', href: route('admin.dashboard') + '?tab=properties', icon: 'props', tab: 'properties' },
  { name: 'Invitations', href: route('admin.dashboard') + '?tab=invitations', icon: 'invites', tab: 'invitations' },
  { name: 'Keywords', href: route('admin.dashboard') + '?tab=keywords', icon: 'keywords', tab: 'keywords' },
  { name: 'Sitemaps', href: route('admin.dashboard') + '?tab=sitemaps', icon: 'sitemaps', tab: 'sitemaps' },
  { name: 'Schemas', href: route('admin.dashboard') + '?tab=schemas', icon: 'schemas', tab: 'schemas' },
  { name: 'CMS Settings', href: route('admin.dashboard') + '?tab=cms', icon: 'cms', tab: 'cms' },
  { name: 'Service Health', href: route('admin.dashboard') + '?tab=services', icon: 'services', tab: 'services' },
  { name: 'Security Logs', href: route('admin.dashboard') + '?tab=logs', icon: 'logs', tab: 'logs' },
]

const isActive = (item) => {
  const currentTab = new URLSearchParams(window.location.search).get('tab') || ''
  return currentTab === item.tab
}
</script>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

.selection\:bg-rose-500 ::selection {
  background-color: rgb(244 63 94);
}

body {
    background-color: #ffffff;
    font-family: 'Outfit', sans-serif;
}
</style>
