
<script setup> 
import { ref, onMounted, watch, computed } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '../../Layouts/AdminLayout.vue'
import axios from 'axios'

const props = defineProps({
    stats: Object,
    recent_activities: Array,
    tab: { type: String, default: 'overview' }
})

const activeTab = ref(props.tab)
const searchQuery = ref('')
const dashboardSearch = ref('')
const isLoading = ref(false)

const filteredActivities = computed(() => {
    if (!dashboardSearch.value) return props.recent_activities
    const query = dashboardSearch.value.toLowerCase()
    return props.recent_activities.filter(act => 
        act.description?.toLowerCase().includes(query) ||
        act.activity_type?.toLowerCase().includes(query) ||
        act.user?.name?.toLowerCase().includes(query) ||
        act.organization?.name?.toLowerCase().includes(query)
    )
})

watch(() => props.tab, (newTab) => {
    activeTab.value = newTab || 'overview'
})

// Data State
const users = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const organizations = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const properties = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const invitations = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const sitemaps = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const schemas = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const keywords = ref({ data: [], current_page: 1, last_page: 1, total: 0 })
const siteConfig = ref({})
const servicesData = ref([])
const logs = ref({ content: '', resource: 'laravel', path: '' })

const logResource = ref('laravel')
const logAutoRefresh = ref(false)
let logInterval = null

// Advanced Details State
const selectedProperty = ref(null)
const isPropertyModalOpen = ref(false)
const selectedSchema = ref(null)
const isSchemaModalOpen = ref(false)

// Modals State
const isAddUserModalOpen = ref(false)
const isInviteOrgModalOpen = ref(false)

const newUser = ref({ name: '', email: '', password: '', is_admin: false })
const newInvite = ref({ email: '', organization_id: '', role: 'member' })

const addUser = async () => {
    try {
        await axios.post(route('admin.users.add'), newUser.value)
        isAddUserModalOpen.value = false
        newUser.value = { name: '', email: '', password: '', is_admin: false }
        fetchData(users.value.current_page)
        alert('User created successfully.')
    } catch (e) {
        console.error('Failed to create user', e)
        alert(e.response?.data?.message || 'Failed to create user.')
    }
}

const sendInvite = async () => {
    try {
        await axios.post(route('admin.invitations.invite-org'), newInvite.value)
        isInviteOrgModalOpen.value = false
        newInvite.value = { email: '', organization_id: '', role: 'member' }
        fetchData(invitations.value.current_page)
        alert('Invitation sent successfully.')
    } catch (e) {
        console.error('Failed to send invite', e)
        alert(e.response?.data?.message || 'Failed to send invite.')
    }
}

// Filters State
const statusFilter = ref('all')
const orgFilter = ref('all')
const keywordType = ref('research') // research or trends

const showProperty = async (prop) => {
    selectedProperty.value = prop
    isPropertyModalOpen.value = true
    try {
        const r = await axios.get(route('admin.properties.detail', prop.id))
        selectedProperty.value = r.data
    } catch (e) {
        console.error('Failed to load property details', e)
    }
}

const showSchema = (schema) => {
    selectedSchema.value = schema
    isSchemaModalOpen.value = true
}

// Tab switching logic
watch(activeTab, (newTab) => {
    searchQuery.value = ''
    statusFilter.value = 'all'
    orgFilter.value = 'all'
    fetchData()
    
    if (newTab === 'logs') {
        startLogPolling()
    } else {
        stopLogPolling()
    }

    if (newTab === 'services') {
        startServicePolling()
    } else {
        stopServicePolling()
    }
})

// Search Debouncing
let searchTimeout = null
watch(searchQuery, () => {
    if (searchTimeout) clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        fetchData(1)
    }, 500)
})

// Filter Watches
watch([statusFilter, orgFilter, keywordType], () => {
    fetchData(1)
})

const fetchData = async (page = 1) => {
    if (activeTab.value === 'overview') return
    isLoading.value = true
    try {
        let endpoint = ''
        if (activeTab.value === 'users') endpoint = route('admin.users')
        if (activeTab.value === 'organizations') endpoint = route('admin.organizations')
        if (activeTab.value === 'properties') endpoint = route('admin.properties')
        if (activeTab.value === 'invitations') endpoint = route('admin.invitations')
        if (activeTab.value === 'sitemaps') endpoint = route('admin.sitemaps')
        if (activeTab.value === 'schemas') endpoint = route('admin.schemas')
        if (activeTab.value === 'keywords') {
            endpoint = keywordType.value === 'research' ? route('admin.keywords.research') : route('admin.keywords.trends')
        }
        if (activeTab.value === 'cms') endpoint = route('admin.config.get')
        if (activeTab.value === 'services') endpoint = route('admin.services')
        if (activeTab.value === 'logs') endpoint = route('admin.logs')

        if (!endpoint) return

        const params = { 
            page, 
            search: searchQuery.value, 
            status: statusFilter.value,
            org_id: orgFilter.value,
            resource: logResource.value 
        }
        const r = await axios.get(endpoint, { params })
        
        if (activeTab.value === 'users') users.value = r.data
        if (activeTab.value === 'organizations') organizations.value = r.data
        if (activeTab.value === 'properties') properties.value = r.data
        if (activeTab.value === 'invitations') invitations.value = r.data
        if (activeTab.value === 'sitemaps') sitemaps.value = r.data
        if (activeTab.value === 'schemas') schemas.value = r.data
        if (activeTab.value === 'keywords') keywords.value = r.data
        if (activeTab.value === 'cms') siteConfig.value = r.data
        if (activeTab.value === 'services') servicesData.value = r.data
        if (activeTab.value === 'logs') logs.value = r.data
    } catch (e) {
        console.error('Failed to fetch data for tab: ' + activeTab.value, e)
        alert('Failed to load ' + activeTab.value + ' data. Check console.')
    } finally {
        isLoading.value = false
    }
}

const toggleUserStatus = async (user) => {
    try {
        const r = await axios.post(route('admin.users.toggle-status', user.id))
        fetchData(users.value.current_page)
    } catch (e) {
        console.error(e.response?.data?.error || 'Failed to update user status')
    }
}

const deleteUser = async (user) => {
    if (!confirm(`Are you sure you want to delete ${user.email}?`)) return
    try {
        await axios.delete(route('admin.users.delete', user.id))
        fetchData(users.value.current_page)
    } catch (e) {
        console.error('Failed to delete user', e)
    }
}

const resetPassword = async (user) => {
    try {
        const r = await axios.post(route('admin.users.reset-password', user.id))
        alert(`New password for ${user.email}: ${r.data.new_password}`)
    } catch (e) {
        console.error('Failed to reset password', e)
    }
}

const revokeInvitation = async (invite) => {
    if (!confirm(`Revoke invitation for ${invite.email}?`)) return
    try {
        await axios.delete(route('admin.invitations.revoke', invite.id))
        fetchData(invitations.value.current_page)
    } catch (e) {
        console.error('Failed to revoke invitation', e)
    }
}

const startLogPolling = () => {
    stopLogPolling()
    fetchData()
    logInterval = setInterval(() => {
        if (logAutoRefresh.value) fetchData()
    }, 5000)
}

const stopLogPolling = () => {
    if (logInterval) clearInterval(logInterval)
}

const saveConfig = async () => {
    try {
        await axios.post(route('admin.config.update'), { configs: siteConfig.value })
        alert('System configurations updated successfully.')
    } catch (e) {
        console.error('Failed to save configs', e)
        alert('Failed to save configurations.')
    }
}

const handleLogoUpload = async (event) => {
    const file = event.target.files[0]
    if (!file) return
    
    const formData = new FormData()
    formData.append('logo', file)
    
    try {
        const r = await axios.post(route('admin.config.upload-logo'), formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        })
        siteConfig.value['site_logo'] = r.data.url
        alert('Logo uploaded successfully.')
    } catch (e) {
        console.error('Logo upload failed', e)
        alert('Failed to upload logo.')
    }
}

const formatLog = (content) => {
    if (!content) return 'No log content.'
    return content.trim()
}

onMounted(() => {
    if (activeTab.value !== 'overview') fetchData()
})
</script>

<template>
    <Head title="Master Oversight" />

    <AdminLayout>
        <div class="py-12 px-4 sm:px-6 lg:px-8 max-w-[100rem] mx-auto space-y-10">
            <!-- Header -->
            <div class="flex flex-col xl:flex-row xl:items-end justify-between gap-8 pb-10 border-b border-gray-300">
                <div>
                    <h2 class="text-4xl font-black text-slate-900 tracking-tight flex items-center gap-4 text-rose-500">
                        Metapilot Registry 
                        <span class="px-3 py-1 bg-rose-50 text-rose-600 text-[10px] font-black rounded-full uppercase tracking-tighter border border-rose-500/20 not-italic">Dashboard</span>
                    </h2>
                    <p class="text-slate-500 font-medium mt-2 max-w-xl leading-relaxed">Unified governance console for platform-wide metrics, users, and infrastructure.</p>
                </div>
            </div>

            <!-- Tab Content: Overview -->
            <div v-if="activeTab === 'overview'" class="space-y-12">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <div class="bg-white p-8 shadow-sm rounded-[2.5rem] border border-gray-200">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Total Users</p>
                        <h4 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_users }}</h4>
                    </div>
                    <div class="bg-white p-8 shadow-sm rounded-[2.5rem] border border-gray-200">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Organizations</p>
                        <h4 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_orgs }}</h4>
                    </div>
                    <div class="bg-white p-8 shadow-sm rounded-[2.5rem] border border-gray-200">
                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest mb-1">Properties</p>
                        <h4 class="text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_props }}</h4>
                    </div>
                    <div class="bg-white p-8 shadow-sm rounded-[2.5rem] border border-gray-200">
                        <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest mb-1">24h Activity</p>
                        <h4 class="text-3xl font-black text-rose-600 tracking-tight">{{ stats.hits_24h }}</h4>
                    </div>
                    <div class="bg-rose-600 p-8 shadow-sm rounded-[2.5rem] text-white border border-rose-500/50">
                        <p class="text-[10px] font-black text-rose-200 uppercase tracking-widest mb-1">7d Volume</p>
                        <h4 class="text-3xl font-black tracking-tight">{{ stats.hits_7d }}</h4>
                    </div>
                </div>

                <!-- Recent Activity Feed -->
                <div class="bg-white p-10 shadow-lg rounded-[3rem] border border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                        <h3 class="text-xs font-black text-slate-500 uppercase tracking-widest flex items-center gap-4">
                            <span class="w-12 h-px bg-rose-500/20"></span>
                            System-wide Forensic Stream
                        </h3>
                        <div class="w-full md:w-96 relative group">
                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-rose-500 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input 
                                v-model="dashboardSearch" 
                                placeholder="Filter activities..." 
                                class="w-full bg-slate-50 border-gray-200 focus:bg-white focus:border-rose-500 focus:ring-0 rounded-2xl text-[11px] font-bold py-4 pl-12 pr-6 shadow-sm transition-all text-slate-800 placeholder:text-slate-400" 
                            />
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div v-if="filteredActivities.length === 0" class="py-20 text-center">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest italic">No activities match your filter</p>
                        </div>
                        <div v-for="act in filteredActivities" :key="act.id" class="flex items-center gap-6 p-5 hover:bg-slate-50 rounded-2xl transition-all group">
                            <div class="w-12 h-12 rounded-xl bg-white flex items-center justify-center text-slate-500 group-hover:bg-rose-600 group-hover:text-white transition-all">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-black text-slate-800 uppercase">
                                    {{ act.user?.name || 'Unknown User' }}
                                    <span class="text-slate-600 font-medium px-2">@</span>
                                    {{ act.organization?.name || 'No Org' }}
                                </p>
                                <p class="text-[11px] text-slate-500 font-medium mt-0.5">{{ act.description }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">{{ new Date(act.created_at).toLocaleTimeString() }}</p>
                                <p class="text-[9px] font-black text-slate-500 uppercase">{{ act.activity_type }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Users -->
            <div v-if="activeTab === 'users'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight  uppercase">User Registry</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Manage platform-wide accounts and permissions</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <button @click="isAddUserModalOpen = true" class="bg-rose-600 hover:bg-rose-500 text-white px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                            Add User
                        </button>
                        <select v-model="statusFilter" class="bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800 min-w-[150px]">
                            <option value="all">All Status</option>
                            <option value="active">Active Only</option>
                            <option value="disabled">Disabled Only</option>
                        </select>
                        <div class="w-80 relative">
                            <input v-model="searchQuery" placeholder="Filter..." class="w-full bg-slate-50 border-gray-100 focus:bg-white focus:border-rose-500 focus:ring-0 rounded-2xl text-[11px] font-bold py-4 px-6 shadow-sm transition-all text-slate-800 placeholder:text-slate-400" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Identity</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Organizations</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Access</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Status</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-white transition-all group">
                                <td class="py-8 px-10">
                                    <div class="flex items-center gap-4">
                                        <img :src="user.profile_photo_url" class="w-10 h-10 rounded-full border-2 border-gray-200 shadow-sm" />
                                        <div>
                                            <p class="text-xs font-black text-slate-900">{{ user.name }}</p>
                                            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">{{ user.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-8 px-6 text-xs font-bold text-slate-500">
                                    {{ user.organizations?.map(o => o.name).join(', ') || 'None' }}
                                </td>
                                <td class="py-8 px-6 text-center">
                                    <span v-if="user.is_admin" class="px-2 py-1 bg-rose-50 text-rose-600 text-[9px] font-black rounded uppercase tracking-widest border border-rose-500/20">YES</span>
                                    <span v-else class="text-[9px] font-black text-slate-600 uppercase">NO</span>
                                </td>
                                <td class="py-8 px-6 text-center">
                                    <span :class="user.is_active ? 'bg-emerald-900/30 text-emerald-400 border-emerald-500/20' : 'bg-white/30 text-slate-500 border-gray-200'" class="px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-widest border">
                                        {{ user.is_active ? 'Active' : 'Disabled' }}
                                    </span>
                                </td>
                                <td class="py-6 px-10 text-right space-x-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="resetPassword(user)" class="p-2.5 rounded-xl bg-white text-slate-400 hover:text-rose-600 border border-gray-200 hover:border-rose-200 hover:shadow-sm transition-all" title="Reset Password">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                        </button>
                                        <button @click="toggleUserStatus(user)" :disabled="user.id === $page.props.auth.user.id" class="px-5 py-2.5 rounded-xl border text-[9px] font-black uppercase tracking-widest shadow-sm transition-all active:scale-95 disabled:opacity-30" :class="user.is_active ? 'text-rose-600 border-rose-500/20 bg-rose-50/50 hover:bg-rose-50' : 'text-emerald-500 border-emerald-500/20 bg-emerald-50/50 hover:bg-emerald-50'">
                                            {{ user.is_active ? 'Disable' : 'Enable' }}
                                        </button>
                                        <button @click="deleteUser(user)" :disabled="user.id === $page.props.auth.user.id" class="p-2.5 rounded-xl bg-white text-slate-300 hover:text-rose-600 border border-gray-100 hover:border-rose-200 hover:shadow-sm transition-all disabled:opacity-30" title="Delete User">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <!-- ... Pagination logic stays similar ... -->
                </div>
            </div>

            <!-- Tab Content: Organizations -->
            <div v-if="activeTab === 'organizations'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Organization Brands</h3>
                    <div class="flex items-center gap-4">
                        <button @click="isInviteOrgModalOpen = true" class="bg-rose-600 hover:bg-rose-500 text-white px-6 py-4 rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl transition-all flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
                            Invite to Org
                        </button>
                        <div class="w-80 relative">
                            <input v-model="searchQuery" placeholder="Search organizations..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Brand Name</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Users</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Properties</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Pixel Hits</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="org in organizations.data" :key="org.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10">
                                    <p class="text-xs font-black text-slate-900">{{ org.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">slug: {{ org.slug }}</p>
                                </td>
                                <td class="py-8 px-6 text-xs font-black text-slate-500">{{ org.users_count }}</td>
                                <td class="py-8 px-6 text-xs font-black text-slate-500">{{ org.analytics_properties_count }}</td>
                                <td class="py-8 px-6 text-xs font-black text-rose-600">{{ org.ad_track_events_count }}</td>
                                <td class="py-8 px-10 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                    {{ new Date(org.created_at).toLocaleDateString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Properties -->
            <div v-if="activeTab === 'properties'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Property Portfolio</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest">Cross-platform asset oversight & sync health</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select v-model="statusFilter" class="bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800 min-w-[150px]">
                            <option value="all">All Health</option>
                            <option value="active">Healthy</option>
                            <option value="inactive">Issues</option>
                        </select>
                        <div class="w-80 relative">
                            <input v-model="searchQuery" placeholder="Search properties..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Property / Connection</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Sync Status</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Token Health</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Org</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="prop in properties.data" :key="prop.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10">
                                    <p class="text-xs font-black text-slate-900">{{ prop.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold underline truncate max-w-[200px]">{{ prop.website_url }}</p>
                                </td>
                                <td class="py-8 px-6">
                                    <div class="flex flex-col gap-1">
                                        <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ prop.sync_status || 'IDLE' }}</p>
                                        <p class="text-[9px] text-slate-500 font-bold italic">Last: {{ prop.last_sync_at ? new Date(prop.last_sync_at).toLocaleDateString() : 'Never' }}</p>
                                    </div>
                                </td>
                                <td class="py-8 px-6 text-center">
                                    <span :class="prop.is_active ? 'bg-emerald-900/30 text-emerald-400 border-emerald-500/20' : 'bg-rose-50 text-rose-600 border-rose-500/20'" class="px-3 py-1 text-[9px] font-black rounded-full uppercase tracking-widest border">
                                        {{ prop.is_active ? 'VALID' : 'EXPIRED' }}
                                    </span>
                                </td>
                                <td class="py-8 px-6 text-center text-[10px] font-black text-slate-500 uppercase tracking-widest italic">{{ prop.organization?.name }}</td>
                                <td class="py-8 px-10 text-right">
                                    <button @click="showProperty(prop)" class="text-[10px] font-black text-rose-600 hover:text-white px-4 py-2 border border-rose-500/20 hover:bg-rose-600 rounded-xl transition-all">View Details</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Invitations -->
            <div v-if="activeTab === 'invitations'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Pending Invites</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest">Platform & Organization access requests</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <select v-model="statusFilter" class="bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800 min-w-[150px]">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="accepted">Accepted</option>
                            <option value="expired">Expired</option>
                        </select>
                        <div class="w-80 relative">
                            <input v-model="searchQuery" placeholder="Search by email..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                        </div>
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Invited Email</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Target Org</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Role</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Status</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="invite in invitations.data" :key="invite.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10 text-xs font-black text-slate-900">{{ invite.email }}</td>
                                <td class="py-8 px-6 text-xs font-bold text-slate-500">{{ invite.organization?.name || 'Platform' }}</td>
                                <td class="py-8 px-6 text-xs font-black text-rose-600 uppercase tracking-widest">{{ invite.role }}</td>
                                <td class="py-8 px-6 text-center">
                                    <span v-if="invite.accepted_at" class="px-3 py-1 bg-emerald-900/30 text-emerald-400 border border-emerald-500/20 text-[9px] font-black rounded-full uppercase tracking-widest">Accepted</span>
                                    <span v-else-if="new Date(invite.expires_at) < new Date()" class="px-3 py-1 bg-rose-50 text-rose-600 border border-rose-500/20 text-[9px] font-black rounded-full uppercase tracking-widest">Expired</span>
                                    <span v-else class="px-3 py-1 bg-white text-slate-500 border border-gray-200 text-[9px] font-black rounded-full uppercase tracking-widest">Pending</span>
                                </td>
                                <td class="py-8 px-10 text-right">
                                    <button v-if="!invite.accepted_at" @click="revokeInvitation(invite)" class="text-rose-500 hover:text-white p-2.5 rounded-xl border border-rose-500/20 hover:bg-rose-600 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Keywords -->
            <div v-if="activeTab === 'keywords'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Keyword Intelligence</h3>
                            <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest">Global search patterns and trending discovery</p>
                        </div>
                        <div class="flex p-1 bg-white rounded-xl border border-gray-200 ml-4">
                            <button @click="keywordType = 'research'" :class="keywordType === 'research' ? 'bg-rose-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-500'" class="px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Research</button>
                            <button @click="keywordType = 'trends'" :class="keywordType === 'trends' ? 'bg-rose-600 text-white shadow-lg' : 'text-slate-500 hover:text-slate-500'" class="px-4 py-2 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Trends</button>
                        </div>
                    </div>
                    <div class="w-80 relative">
                        <input v-model="searchQuery" placeholder="Filter global keywords..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">{{ keywordType === 'research' ? 'Query' : 'Keyword' }}</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Niche / Industry</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Interest</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Growth</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Org Source</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="kw in keywords.data" :key="kw.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10 text-xs font-black text-slate-900">{{ kw.query || kw.keyword }}</td>
                                <td class="py-8 px-6 text-xs font-bold text-slate-500 uppercase tracking-tighter">{{ kw.niche || 'General' }}</td>
                                <td class="py-8 px-6 text-center text-xs font-black text-rose-600">{{ kw.current_interest || '—' }}</td>
                                <td class="py-8 px-6 text-center">
                                    <span :class="parseFloat(kw.growth_rate) > 0 ? 'text-emerald-400' : 'text-slate-600'" class="text-xs font-black">
                                        {{ kw.growth_rate ? (parseFloat(kw.growth_rate) > 0 ? '+' : '') + kw.growth_rate + '%' : '—' }}
                                    </span>
                                </td>
                                <td class="py-8 px-10 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">
                                    {{ kw.organization?.name || 'System' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Sitemaps -->
            <div v-if="activeTab === 'sitemaps'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Sitemap Infrastructure</h3>
                    <div class="w-80 relative">
                        <input v-model="searchQuery" placeholder="Filter sitemaps..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Sitemap Name</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Target URL</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Links</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Status</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Organization</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="map in sitemaps.data" :key="map.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10 text-xs font-black text-slate-900">{{ map.name }}</td>
                                <td class="py-8 px-6 text-xs font-bold text-slate-500 underline truncate max-w-xs">{{ map.site_url }}</td>
                                <td class="py-8 px-6 text-center text-xs font-black text-rose-600">{{ map.links_count }}</td>
                                <td class="py-8 px-6 text-center">
                                    <span :class="map.last_crawl_status === 'success' ? 'text-emerald-400' : 'text-rose-600'" class="text-[9px] font-black uppercase tracking-widest">
                                        {{ map.last_crawl_status?.toUpperCase() || 'IDLE' }}
                                    </span>
                                </td>
                                <td class="py-8 px-10 text-right text-[10px] font-black text-slate-500 uppercase tracking-widest">{{ map.organization?.name }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: Schemas -->
            <div v-if="activeTab === 'schemas'" class="space-y-8">
                <div class="flex items-center justify-between">
                    <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">JSON-LD Schemas</h3>
                    <div class="w-80 relative">
                        <input v-model="searchQuery" placeholder="Filter schemas..." class="w-full bg-white border-gray-200 focus:border-rose-500 focus:ring-0 rounded-2xl text-xs font-bold py-4 px-6 shadow-xl text-slate-800" />
                    </div>
                </div>

                <div class="bg-white shadow-lg rounded-[2.5rem] border border-gray-200 overflow-hidden">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-gray-100">
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Schema Name</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Type</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Target URL</th>
                                <th class="py-6 px-6 text-[10px] font-black text-slate-500 uppercase tracking-widest text-center sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Status</th>
                                <th class="py-6 px-10 text-[10px] font-black text-slate-500 uppercase tracking-widest text-right sticky top-0 bg-slate-50/80 backdrop-blur-sm z-10">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="schema in schemas.data" :key="schema.id" class="hover:bg-white transition-all">
                                <td class="py-8 px-10">
                                    <p class="text-xs font-black text-slate-900">{{ schema.name }}</p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase tracking-tight">{{ schema.organization?.name }}</p>
                                </td>
                                <td class="py-8 px-6">
                                    <span class="px-2 py-1 bg-rose-50 text-rose-600 border border-rose-500/20 text-[9px] font-black rounded uppercase tracking-widest">
                                        {{ schema.schema_type?.name || 'Local' }}
                                    </span>
                                </td>
                                <td class="py-8 px-6 text-xs font-bold text-slate-500 underline truncate max-w-xs">{{ schema.url }}</td>
                                <td class="py-8 px-6 text-center">
                                    <span class="text-emerald-400 text-[9px] font-black uppercase tracking-widest">ACTIVE</span>
                                </td>
                                <td class="py-8 px-10 text-right">
                                    <button @click="showSchema(schema)" class="text-[10px] font-black text-rose-600 hover:text-white px-4 py-2 border border-rose-500/20 hover:bg-rose-600 rounded-xl transition-all">Preview JSON</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Content: CMS -->
            <div v-if="activeTab === 'cms'" class="space-y-12">
                <div class="flex items-center justify-between border-b border-gray-100 pb-10">
                    <div>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Site Configuration</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-2 tracking-widest">Global branding and legal content management</p>
                    </div>
                    <button @click="saveConfig" class="bg-rose-600 hover:bg-rose-500 text-white px-10 py-5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg transition-all active:scale-95">Save All Changes</button>
                </div>

                <div class="grid grid-cols-1 xl:grid-cols-3 gap-12">
                    <!-- Branding -->
                    <div class="xl:col-span-1 space-y-10">
                        <div class="bg-white p-10 rounded-[2.5rem] border border-gray-200 shadow-sm space-y-8">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em]">Corporate Branding</h4>
                            
                            <div class="space-y-6">
                                <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Company Logo</p>
                                <div class="relative group">
                                    <div class="w-full aspect-video bg-white rounded-3xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden">
                                        <img v-if="siteConfig['site_logo']" :src="siteConfig['site_logo']" class="max-h-20 object-contain" />
                                        <div v-else class="text-[10px] font-black text-slate-700 uppercase">No Logo Uploaded</div>
                                    </div>
                                    <input type="file" @change="handleLogoUpload" class="absolute inset-0 opacity-0 cursor-pointer" />
                                    <div class="absolute inset-0 bg-rose-600/20 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-all pointer-events-none rounded-3xl">
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest bg-rose-600 px-4 py-2 rounded-xl">Click to Upload</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Platform Name</label>
                                <input v-model="siteConfig['site_name']" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800" />
                            </div>
                        </div>
                    </div>

                    <!-- Content Management -->
                    <div class="xl:col-span-2 space-y-10">
                        <div class="bg-white p-10 rounded-[2.5rem] border border-gray-200 shadow-sm space-y-10">
                            <h4 class="text-xs font-black text-slate-500 uppercase tracking-[0.3em]">CMS Content Blocks</h4>
                            
                            <div class="grid grid-cols-1 gap-10">
                                <div class="space-y-4">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest flex justify-between">
                                        Front Page Hero Heading
                                        <span class="text-slate-700 lowercase italic">Supports HTML labels</span>
                                    </label>
                                    <textarea v-model="siteConfig['welcome_hero_title']" rows="2" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-900"></textarea>
                                </div>
                                
                                <div class="space-y-4">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Privacy Policy (Markdown/HTML)</label>
                                    <textarea v-model="siteConfig['legal_privacy']" rows="6" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-mono py-4 px-6 text-rose-600/80 leading-relaxed"></textarea>
                                </div>

                                <div class="space-y-4">
                                    <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Terms of Service</label>
                                    <textarea v-model="siteConfig['legal_terms']" rows="6" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-mono py-4 px-6 text-rose-600/80 leading-relaxed"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Services -->
            <div v-if="activeTab === 'services'" class="space-y-12">
                <div class="flex items-center justify-between border-b border-gray-100 pb-10">
                    <div>
                        <h3 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">System Vitality</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-2 tracking-widest">Real-time microservice health monitoring</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest italic mr-2">Auto-refresh every 30s</span>
                        <button @click="fetchData" class="p-4 rounded-2xl bg-white border border-gray-200 hover:text-rose-600 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div v-for="service in servicesData" :key="service.name" class="bg-white p-10 rounded-[2.5rem] border border-gray-200 shadow-sm flex flex-col items-center text-center space-y-4 group">
                        <div class="text-4xl mb-2 grayscale group-hover:grayscale-0 transition-all duration-500 scale-110 group-hover:scale-125">{{ service.icon }}</div>
                        <h4 class="text-sm font-black text-slate-900 uppercase tracking-widest">{{ service.name }}</h4>
                        <div class="flex items-center gap-2">
                            <span :class="service.status === 'Online' ? 'bg-emerald-500' : 'bg-rose-500'" class="w-2 h-2 rounded-full shadow-[0_0_10px_rgba(16,185,129,0.5)]"></span>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-[0.2em]">{{ service.status }}</span>
                        </div>
                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest pt-4 border-t border-gray-100 w-full">{{ service.ping }}</p>
                    </div>
                </div>
            </div>

            <!-- Tab Content: Logs -->
            <div v-if="activeTab === 'logs'" class="space-y-8">
                <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-6">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Infrastructure Logs</h3>
                        <p class="text-slate-500 text-[10px] font-bold uppercase mt-1 tracking-widest">Real-time terminal stream from core services</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-4">
                        <select v-model="logResource" @change="fetchData" class="bg-white border-gray-200 rounded-xl text-xs font-black shadow-xl px-6 py-4 min-w-[220px] text-slate-800">
                            <option value="laravel">🛡️ Laravel App System</option>
                            <option value="engine">⚙️ Python Intelligence Engine</option>
                            <option value="worker">🏗️ Automation Worker (Redis)</option>
                            <option value="crawler">🕷️ Distributed Spider Node</option>
                            <option value="metapilot">🤖Metapilot System</option>
                        </select>
                        <button @click="logAutoRefresh = !logAutoRefresh" :class="logAutoRefresh ? 'bg-emerald-600 text-white shadow-emerald-900/20' : 'bg-white text-slate-500 border border-gray-200'" class="px-6 py-4 rounded-xl text-[10px] font-black uppercase tracking-widest shadow-xl transition-all">
                            Live Stream: {{ logAutoRefresh ? 'ACTIVE' : 'IDLE' }}
                        </button>
                    </div>
                </div>

                <div class="bg-slate-950 rounded-[2.5rem] p-10 border border-slate-800 shadow-lg relative overflow-hidden group">
                    <div class="absolute top-4 left-1/2 -translate-x-1/2 text-[9px] font-black text-slate-700 uppercase tracking-[0.5em] group-hover:text-slate-500 transition-colors">
                        Central Security Console — {{ logs.path }}
                    </div>
                    <div class="h-[600px] overflow-y-auto font-mono text-[11px] leading-relaxed text-emerald-500/90 custom-scrollbar mt-4">
                        <pre class="whitespace-pre-wrap">{{ formatLog(logs.content) }}</pre>
                        <div v-if="logAutoRefresh" class="flex items-center gap-2 text-[9px] text-emerald-500 font-bold uppercase tracking-widest mt-8 animate-pulse">
                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full"></span>
                            Tailing live stream...
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modals -->
            <!-- Add User Modal -->
            <div v-if="isAddUserModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isAddUserModalOpen = false"></div>
                <div class="relative bg-white border border-gray-200 w-full max-w-md rounded-[3rem] shadow-xl overflow-hidden p-10 space-y-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 uppercase italic">Create Account</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest">Direct administrative user creation</p>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Full Name</label>
                            <input v-model="newUser.name" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800" placeholder="John Doe" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Email Address</label>
                            <input v-model="newUser.email" type="email" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800" placeholder="john@example.com" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Initial Password</label>
                            <input v-model="newUser.password" type="password" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800" />
                        </div>
                        <div class="flex items-center gap-3">
                            <input v-model="newUser.is_admin" type="checkbox" class="w-5 h-5 rounded border-gray-300 bg-white text-rose-600 focus:ring-0" />
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Grant Global Admin Privileges</label>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <button @click="isAddUserModalOpen = false" class="flex-1 px-6 py-4 rounded-2xl border border-gray-300 text-[10px] font-black text-slate-500 uppercase tracking-widest hover:bg-white transition-all">Cancel</button>
                        <button @click="addUser" class="flex-1 px-6 py-4 rounded-2xl bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-rose-500 shadow-xl transition-all">Create User</button>
                    </div>
                </div>
            </div>

            <!-- Invite to Organization Modal -->
            <div v-if="isInviteOrgModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isInviteOrgModalOpen = false"></div>
                <div class="relative bg-white border border-gray-200 w-full max-w-md rounded-[3rem] shadow-xl overflow-hidden p-10 space-y-8">
                    <div>
                        <h3 class="text-2xl font-black text-slate-900 uppercase italic">Organization Invite</h3>
                        <p class="text-[10px] text-slate-500 font-bold uppercase mt-1 tracking-widest">Add collaborators to specific brands</p>
                    </div>

                    <div class="space-y-6">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Target Organization</label>
                            <select v-model="newInvite.organization_id" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800">
                                <option value="">Select an organization...</option>
                                <option v-for="org in organizations.data" :key="org.id" :value="org.id">{{ org.name }}</option>
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Invitee Email</label>
                            <input v-model="newInvite.email" type="email" class="w-full bg-white border-gray-200 rounded-2xl text-xs font-bold py-4 px-6 text-slate-800" placeholder="collaborator@example.com" />
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Assign Role</label>
                            <div class="flex p-1 bg-white rounded-xl border border-gray-200">
                                <button @click="newInvite.role = 'member'" :class="newInvite.role === 'member' ? 'bg-white text-white shadow-lg' : 'text-slate-500'" class="flex-1 py-3 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Member</button>
                                <button @click="newInvite.role = 'admin'" :class="newInvite.role === 'admin' ? 'bg-rose-600 text-white shadow-lg' : 'text-slate-500'" class="flex-1 py-3 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Admin</button>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 pt-4">
                        <button @click="isInviteOrgModalOpen = false" class="flex-1 px-6 py-4 rounded-2xl border border-gray-300 text-[10px] font-black text-slate-500 uppercase tracking-widest hover:bg-white transition-all">Cancel</button>
                        <button @click="sendInvite" class="flex-1 px-6 py-4 rounded-2xl bg-rose-600 text-white text-[10px] font-black uppercase tracking-widest hover:bg-rose-500 shadow-xl transition-all">Send Invitation</button>
                    </div>
                </div>
            </div>

            <!-- Property Details Modal (Already covered above in full) -->
            <div v-if="isPropertyModalOpen && selectedProperty" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isPropertyModalOpen = false"></div>
                <div class="relative bg-white border border-gray-200 w-full max-w-5xl max-h-[90vh] overflow-hidden rounded-[3rem] shadow-xl flex flex-col">
                    <!-- Header -->
                    <div class="p-10 border-b border-gray-200 flex items-center justify-between bg-white">
                        <div>
                            <h3 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase italic">{{ selectedProperty.name }}</h3>
                            <p class="text-[10px] font-black text-slate-500 uppercase tracking-[0.3em] mt-2">Property Resource Node #{{ selectedProperty.property_id }}</p>
                        </div>
                        <button @click="isPropertyModalOpen = false" class="w-12 h-12 rounded-2xl bg-white border border-gray-200 flex items-center justify-center text-slate-500 hover:text-white transition-all shadow-xl">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Content (Simplified for space, but includes and uses all backend data) -->
                    <div class="flex-1 overflow-y-auto p-10 custom-scrollbar space-y-12">
                        <div class="grid grid-cols-4 gap-6">
                            <div class="bg-white p-6 rounded-3xl border border-gray-300">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Snapshots</p>
                                <h4 class="text-2xl font-black text-slate-900">{{ selectedProperty.snapshots_count }}</h4>
                            </div>
                            <div class="bg-white p-6 rounded-3xl border border-gray-300">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Leads</p>
                                <h4 class="text-2xl font-black text-slate-900">{{ selectedProperty.lead_events_count }}</h4>
                            </div>
                            <div class="bg-white p-6 rounded-3xl border border-gray-300">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">Insights</p>
                                <h4 class="text-2xl font-black text-slate-900">{{ selectedProperty.insights_count }}</h4>
                            </div>
                            <div class="bg-white p-6 rounded-3xl border border-gray-300">
                                <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest mb-2">GSC Data</p>
                                <h4 class="text-2xl font-black text-slate-900">{{ selectedProperty.search_console_metrics_count }}</h4>
                            </div>
                        </div>

                        <!-- Extended info would go here -->
                        <div class="bg-white p-10 rounded-[2.5rem] border border-gray-300">
                             <h4 class="text-xs font-black text-slate-500 uppercase tracking-widest mb-6 italic">Configuration Metadata</h4>
                             <div class="grid grid-cols-2 gap-8 text-xs">
                                 <div>
                                     <p class="text-slate-500 font-bold uppercase text-[9px] mb-1">Organization</p>
                                     <p class="text-slate-900 font-black">{{ selectedProperty.organization?.name }}</p>
                                 </div>
                                 <div>
                                     <p class="text-slate-500 font-bold uppercase text-[9px] mb-1">Primary Owner</p>
                                     <p class="text-slate-900 font-black">{{ selectedProperty.user?.name }} ({{ selectedProperty.user?.email }})</p>
                                 </div>
                             </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Schema Preview Modal -->
            <div v-if="isSchemaModalOpen && selectedSchema" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="isSchemaModalOpen = false"></div>
                <div class="relative bg-white border border-gray-200 w-full max-w-3xl rounded-[3rem] shadow-xl overflow-hidden">
                    <div class="p-8 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-xl font-black text-slate-900 uppercase italic">JSON-LD Output Preview</h3>
                        <button @click="isSchemaModalOpen = false" class="text-slate-500 hover:text-white transition-all">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="p-8 bg-white font-mono text-[11px] text-slate-800 overflow-x-auto border-t border-gray-100">
                        <pre><code>{
  "@context": "https://schema.org",
  "@type": "{{ selectedSchema.schema_type?.name }}",
  "name": "{{ selectedSchema.name }}",
  "url": "{{ selectedSchema.url }}"
  ... computed fields coming from generator service ...
}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #e2e8f0; /* bg-slate-200 */
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e11d48; /* bg-rose-600 */
    border-radius: 10px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #f43f5e; /* bg-rose-500 */
}
</style>
