import { route as ziggyRoute } from 'ziggy-js'

declare global {
    const route: typeof ziggyRoute
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof ziggyRoute
    }
}

export {}
