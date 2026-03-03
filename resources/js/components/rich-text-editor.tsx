import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Placeholder from '@tiptap/extension-placeholder';
import { Bold, Italic, List, ListOrdered, Heading2 } from 'lucide-react';
import { useRef, useEffect } from 'react';

interface RichTextEditorProps {
    name: string;
    defaultValue?: string;
    placeholder?: string;
    required?: boolean;
}

export default function RichTextEditor({ name, defaultValue = '', placeholder, required }: RichTextEditorProps) {
    const hiddenRef = useRef<HTMLInputElement>(null);

    const editor = useEditor({
        extensions: [
            StarterKit,
            Placeholder.configure({ placeholder: placeholder ?? 'Write something...' }),
        ],
        content: defaultValue,
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none min-h-[80px] px-3 py-2 focus:outline-none [&_ul]:list-disc [&_ul]:pl-4 [&_ol]:list-decimal [&_ol]:pl-4 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:mt-2 [&_p]:my-1',
            },
        },
        onUpdate({ editor }) {
            if (hiddenRef.current) {
                hiddenRef.current.value = editor.isEmpty ? '' : editor.getHTML();
            }
        },
    });

    useEffect(() => {
        if (hiddenRef.current) {
            hiddenRef.current.value = defaultValue;
        }
    }, [defaultValue]);

    if (!editor) {
        return null;
    }

    return (
        <div className="border-input bg-background rounded-md border text-sm">
            <div className="border-input flex gap-1 border-b px-2 py-1">
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleBold().run()}
                    className={`rounded p-1 hover:bg-accent ${editor.isActive('bold') ? 'bg-accent' : ''}`}
                    title="Bold"
                >
                    <Bold className="h-4 w-4" />
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleItalic().run()}
                    className={`rounded p-1 hover:bg-accent ${editor.isActive('italic') ? 'bg-accent' : ''}`}
                    title="Italic"
                >
                    <Italic className="h-4 w-4" />
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleHeading({ level: 2 }).run()}
                    className={`rounded p-1 hover:bg-accent ${editor.isActive('heading', { level: 2 }) ? 'bg-accent' : ''}`}
                    title="Heading"
                >
                    <Heading2 className="h-4 w-4" />
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleBulletList().run()}
                    className={`rounded p-1 hover:bg-accent ${editor.isActive('bulletList') ? 'bg-accent' : ''}`}
                    title="Bullet List"
                >
                    <List className="h-4 w-4" />
                </button>
                <button
                    type="button"
                    onClick={() => editor.chain().focus().toggleOrderedList().run()}
                    className={`rounded p-1 hover:bg-accent ${editor.isActive('orderedList') ? 'bg-accent' : ''}`}
                    title="Numbered List"
                >
                    <ListOrdered className="h-4 w-4" />
                </button>
            </div>
            <EditorContent editor={editor} />
            <input type="hidden" name={name} ref={hiddenRef} required={required} />
        </div>
    );
}
