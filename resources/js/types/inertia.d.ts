import type { SharedProps } from './shared'

declare module '@inertiajs/core' {
    interface PageProps extends SharedProps {}
}
