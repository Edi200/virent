import PhotoSwipeLightbox from 'photoswipe/lightbox';
import {
    nextTick,
    onMounted,
    onUnmounted,
    type MaybeRef,
    type Ref,
    toValue,
} from 'vue';
import 'photoswipe/style.css';

export type LightboxItem = {
    src: string;
    width: number;
    height: number;
    alt: string;
};

export function useLightbox(
    galleryRef: Ref<HTMLElement | null>,
    items: MaybeRef<LightboxItem[]>,
) {
    let lightbox: PhotoSwipeLightbox | null = null;

    function toDataSource() {
        return toValue(items).map((item) => ({
            src: item.src,
            w: item.width,
            h: item.height,
            alt: item.alt,
        }));
    }

    async function ensureInitialized(): Promise<void> {
        if (lightbox !== null) {
            return;
        }

        // Ensure template refs (gallery container) are attached before init.
        if (galleryRef.value === null) {
            await nextTick();
        }

        await nextTick();

        lightbox = new PhotoSwipeLightbox({
            pswpModule: () => import('photoswipe'),
        });
        lightbox.init();
    }

    async function open(index = 0): Promise<void> {
        await ensureInitialized();
        lightbox?.loadAndOpen(index, toDataSource());
    }

    onMounted(() => {
        void ensureInitialized();
    });

    onUnmounted(() => {
        lightbox?.destroy();
        lightbox = null;
    });

    return { open };
}
