<script setup lang="ts">
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Placeholder from '@tiptap/extension-placeholder';
import { watch } from 'vue';

const props = defineProps<{
    modelValue: string;
    placeholder?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit,
        Link.configure({ openOnClick: false }),
        Image,
        Placeholder.configure({ placeholder: props.placeholder || 'Start typing...' }),
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none min-h-[150px] p-3 focus:outline-none',
        },
    },
    onUpdate: () => {
        emit('update:modelValue', editor.value?.getHTML() ?? '');
    },
});

watch(
    () => props.modelValue,
    (val) => {
        if (editor.value && editor.value.getHTML() !== val) {
            editor.value.commands.setContent(val || '', false);
        }
    },
);
</script>

<template>
    <div class="rounded-md border bg-background">
        <!-- Toolbar -->
        <div v-if="editor" class="flex flex-wrap gap-1 border-b p-1.5">
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted font-bold': editor.isActive('bold') }"
                @click="editor.chain().focus().toggleBold().run()"
            >
                B
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm italic hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('italic') }"
                @click="editor.chain().focus().toggleItalic().run()"
            >
                I
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm line-through hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('strike') }"
                @click="editor.chain().focus().toggleStrike().run()"
            >
                S
            </button>
            <div class="mx-1 w-px bg-border" />
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('heading', { level: 2 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
            >
                H2
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('heading', { level: 3 }) }"
                @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
            >
                H3
            </button>
            <div class="mx-1 w-px bg-border" />
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('bulletList') }"
                @click="editor.chain().focus().toggleBulletList().run()"
            >
                &bull; List
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('orderedList') }"
                @click="editor.chain().focus().toggleOrderedList().run()"
            >
                1. List
            </button>
            <div class="mx-1 w-px bg-border" />
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                :class="{ 'bg-muted': editor.isActive('blockquote') }"
                @click="editor.chain().focus().toggleBlockquote().run()"
            >
                &ldquo;
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                @click="editor.chain().focus().setHorizontalRule().run()"
            >
                &mdash;
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                @click="editor.chain().focus().undo().run()"
                :disabled="!editor.can().undo()"
            >
                &#8617;
            </button>
            <button
                type="button"
                class="rounded px-2 py-1 text-sm hover:bg-muted"
                @click="editor.chain().focus().redo().run()"
                :disabled="!editor.can().redo()"
            >
                &#8618;
            </button>
        </div>

        <EditorContent :editor="editor" />
    </div>
</template>
