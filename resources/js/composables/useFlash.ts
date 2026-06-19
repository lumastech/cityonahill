import { usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import type { FlashMessages } from '@/types/shared'

export function useFlash() {
    const page = usePage()

    const localFlash = ref<FlashMessages>({
        success: null,
        error: null,
        info: null,
    })

    let clearTimer: ReturnType<typeof setTimeout> | null = null

    watch(
        () => page.props.flash,
        (flash) => {
            if (!flash) return

            localFlash.value = { ...flash }

            const hasMessage = flash.success || flash.error || flash.info

            if (hasMessage) {
                if (clearTimer) clearTimeout(clearTimer)

                clearTimer = setTimeout(() => {
                    localFlash.value = { success: null, error: null, info: null }
                }, 4000)
            }
        },
        { immediate: true, deep: true },
    )

    function clear() {
        localFlash.value = { success: null, error: null, info: null }
        if (clearTimer) clearTimeout(clearTimer)
    }

    const hasFlash = computed(
        () => !!(localFlash.value.success || localFlash.value.error || localFlash.value.info),
    )

    return { flash: localFlash, hasFlash, clear }
}
