import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Helpers de autorización en el frontend. Reflejan los permisos que comparte
 * HandleInertiaRequests. La autorización REAL vive en las policies del backend;
 * esto solo controla qué se muestra.
 */
export function useAuth() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user ?? null)
    const roles = computed(() => user.value?.roles ?? [])
    const permissions = computed(() => user.value?.permissions ?? [])

    const isAdmin = computed(() => roles.value.includes('admin'))

    function can(permission) {
        return isAdmin.value || permissions.value.includes(permission)
    }

    function hasRole(role) {
        return roles.value.includes(role)
    }

    return { user, roles, permissions, isAdmin, can, hasRole }
}
