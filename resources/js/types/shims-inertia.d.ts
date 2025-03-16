import { Page } from '@inertiajs/inertia';

declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $page: Page;
    }
}
