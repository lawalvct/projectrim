import { createApp, h, ref } from 'vue';

import RichTextEditor from '@/components/RichTextEditor.vue';

document.querySelectorAll<HTMLTextAreaElement>('textarea[data-rich-text]').forEach((textarea) => {
    const mountPoint = document.createElement('div');
    textarea.insertAdjacentElement('afterend', mountPoint);

    createApp({
        setup() {
            const content = ref(textarea.value);

            return () =>
                h(RichTextEditor, {
                    modelValue: content.value,
                    placeholder: textarea.dataset.placeholder || 'Start typing...',
                    'onUpdate:modelValue': (value: string) => {
                        content.value = value;
                        textarea.value = value;
                    },
                });
        },
    }).mount(mountPoint);

    textarea.hidden = true;
});
