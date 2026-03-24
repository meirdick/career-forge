import TurndownService from 'turndown';

const turndown = new TurndownService({
    headingStyle: 'atx',
    bulletListMarker: '-',
    codeBlockStyle: 'fenced',
});

export function htmlToMarkdown(html: string): string {
    if (!html.trim()) {
        return '';
    }
    return turndown.turndown(html);
}

export function markdownToHtml(markdown: string): string {
    if (!markdown.trim()) {
        return '';
    }

    let html = markdown;

    // Headings
    html = html.replace(/^#{6}\s+(.+)$/gm, '<h6>$1</h6>');
    html = html.replace(/^#{5}\s+(.+)$/gm, '<h5>$1</h5>');
    html = html.replace(/^#{4}\s+(.+)$/gm, '<h4>$1</h4>');
    html = html.replace(/^###\s+(.+)$/gm, '<h3>$1</h3>');
    html = html.replace(/^##\s+(.+)$/gm, '<h2>$1</h2>');
    html = html.replace(/^#\s+(.+)$/gm, '<h1>$1</h1>');

    // Bold and italic
    html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

    // Bullet lists — collect consecutive lines
    html = html.replace(/^[-*]\s+(.+)$/gm, '<li>$1</li>');
    html = html.replace(/((?:<li>.*<\/li>\n?)+)/g, '<ul>$1</ul>');

    // Numbered lists
    html = html.replace(/^\d+\.\s+(.+)$/gm, '<li>$1</li>');
    html = html.replace(/((?:<li>.*<\/li>\n?)+)/g, (match) => {
        // Only wrap if not already wrapped
        if (match.startsWith('<ul>') || match.startsWith('<ol>')) {
            return match;
        }
        return `<ul>${match}</ul>`;
    });

    // Horizontal rules
    html = html.replace(/^[-*_]{3,}$/gm, '<hr>');

    // Paragraphs — wrap remaining plain text lines
    html = html
        .split('\n')
        .map((line) => {
            const trimmed = line.trim();
            if (!trimmed || trimmed.startsWith('<')) {
                return line;
            }
            return `<p>${trimmed}</p>`;
        })
        .join('\n');

    return html;
}
