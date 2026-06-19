import { ref } from 'vue'

interface ConfirmState {
    open: boolean
    title: string
    message: string
    confirmLabel: string
    dangerMode: boolean
    resolve: ((value: boolean) => void) | null
}

const state = ref<ConfirmState>({
    open: false,
    title: 'Confirm',
    message: '',
    confirmLabel: 'Confirm',
    dangerMode: false,
    resolve: null,
})

export function useConfirm() {
    function confirm(
        message: string,
        options: { title?: string; confirmLabel?: string; dangerMode?: boolean } = {},
    ): Promise<boolean> {
        return new Promise<boolean>((resolve) => {
            state.value = {
                open: true,
                title: options.title ?? 'Confirm',
                message,
                confirmLabel: options.confirmLabel ?? 'Confirm',
                dangerMode: options.dangerMode ?? false,
                resolve,
            }
        })
    }

    function accept() {
        state.value.resolve?.(true)
        state.value.open = false
    }

    function cancel() {
        state.value.resolve?.(false)
        state.value.open = false
    }

    return { confirm, accept, cancel, state }
}
