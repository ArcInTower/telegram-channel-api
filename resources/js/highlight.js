import hljs from 'highlight.js/lib/core';
import php from 'highlight.js/lib/languages/php';
import json from 'highlight.js/lib/languages/json';
import 'highlight.js/styles/atom-one-dark.css';

// Register languages
hljs.registerLanguage('php', php);
hljs.registerLanguage('json', json);

// Initialize highlighting on page load
document.addEventListener('DOMContentLoaded', () => {
    hljs.highlightAll();
});

// Export for use in other scripts
export default hljs;