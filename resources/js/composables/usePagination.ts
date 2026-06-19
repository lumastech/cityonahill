import { router } from '@inertiajs/vue3'
import { ref } from 'vue'

interface PaginationOptions {
    url: string
    preserveState?: boolean
    preserveScroll?: boolean
}

export function usePagination(options: PaginationOptions) {
    const search = ref('')
    const filters = ref<Record<string, string | number | boolean>>({})

    function paginate(page: number = 1) {
        const params: Record<string, unknown> = {
            page,
            ...filters.value,
        }

        if (search.value.trim()) {
            params.search = search.value.trim()
        }

        router.get(options.url, params, {
            preserveState: options.preserveState ?? true,
            preserveScroll: options.preserveScroll ?? true,
            replace: true,
        })
    }

    function setSearch(value: string) {
        search.value = value
        paginate(1)
    }

    function setFilter(key: string, value: string | number | boolean) {
        filters.value[key] = value
        paginate(1)
    }

    function clearFilters() {
        search.value = ''
        filters.value = {}
        paginate(1)
    }

    return { search, filters, paginate, setSearch, setFilter, clearFilters }
}
