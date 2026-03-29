import { Node } from '@tiptap/core';

/**
 * Card block for home page design. Renders as <div class="content-card"> with editable block content inside.
 */
export const Card = Node.create({
  name: 'contentCard',

  group: 'block',

  content: 'block+',

  parseHTML() {
    return [{ tag: 'div.content-card' }];
  },

  renderHTML({ HTMLAttributes }) {
    return ['div', { class: 'content-card', ...HTMLAttributes }, 0];
  },

  addCommands() {
    return {
      setCard:
        () =>
        ({ commands }) => {
          return commands.insertContent({
            type: 'contentCard',
            content: [
              {
                type: 'paragraph',
                content: [{ type: 'text', text: 'Card title or content…' }],
              },
            ],
          });
        },
    };
  },
});
