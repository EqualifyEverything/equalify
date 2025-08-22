import * as cheerio from 'cheerio';

export const normalizeHtmlWithVdom = (html) => {
    if (!html) return '';

    const $ = cheerio.load(`<div id="wrapper">${html}</div>`);
    const root = $('#wrapper');

    // Process all elements
    root.find('*').each(function () {
        const el = $(this);

        // Normalize IDs with numbers
        if (el.attr('id') && /\d{4,}/.test(el.attr('id'))) {
            el.attr('id', 'NORMALIZED');
        }

        // Always normalize tabindex
        if (el.attr('tabindex')) {
            el.attr('tabindex', 'NORMALIZED');
        }

        // Remove query params from URLs
        ['src', 'href'].forEach(attr => {
            if (el.attr(attr) && el.attr(attr).includes('?')) {
                el.attr(attr, el.attr(attr).split('?')[0]);
            }
        });

        // Handle h5p quiz elements
        if (el.hasClass('h5p-sc-alternative')) {
            if (el.hasClass('h5p-sc-is-correct') || el.hasClass('h5p-sc-is-wrong')) {
                el.removeClass('h5p-sc-is-correct h5p-sc-is-wrong')
                    .addClass('h5p-sc-is-NORMALIZED');
            }
        }

        // Normalize data-version attributes
        if (el.attr('data-version') && el.attr('data-version').includes('/s/player/')) {
            el.attr('data-version', el.attr('data-version')
                .replace(/\/s\/player\/[a-zA-Z0-9]{8,}\//, '/s/player/NORMALIZED/'));
        }

        // Add more element-specific normalizations based on your data patterns
    });

    // Remove all whitespace between tags for more reliable comparison
    let result = root.html();

    // Remove excess whitespace for more consistent matching
    result = result.replace(/>\s+</g, '><');

    // Remove all text node whitespace variations
    result = result.replace(/\s{2,}/g, ' ');

    return result;
}
