import { reactive } from 'vue'

const state = reactive({
    toasts: []
})

export const useToastStore = () => {
    const add = (toast) => {
        const id = Math.random().toString(36).substr(2, 9)
        const newToast = {
            id,
            title: toast.title || '',
            message: toast.message || '',
            type: toast.type || 'info', // success, error, warning, info
            duration: toast.duration || 5000,
            progress: 100
        }

        state.toasts.push(newToast)

        // Progress bar animation
        const startTime = Date.now()
        const interval = setInterval(() => {
            const elapsed = Date.now() - startTime
            newToast.progress = Math.max(0, 100 - (elapsed / newToast.duration) * 100)

            if (elapsed >= newToast.duration) {
                clearInterval(interval)
                remove(id)
            }
        }, 30)

        return id
    }

    const remove = (id) => {
        const index = state.toasts.findIndex(t => t.id === id)
        if (index > -1) {
            state.toasts.splice(index, 1)
        }
    }

    return {
        get toasts() { return state.toasts },
        add,
        remove,
        success: (message, title = 'Success') => add({ message, title, type: 'success' }),
        error: (message, title = 'Error') => add({ message, title, type: 'error' }),
        warning: (message, title = 'Warning') => add({ message, title, type: 'warning' }),
        info: (message, title = 'Info') => add({ message, title, type: 'info' })
    }
}
