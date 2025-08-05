# CSS Positioning Bug - Important Notes

## Date: August 4, 2025

## THE PROBLEM
CSS positioning is COMPLETELY INVERTED on this server. The behavior is the exact opposite of normal CSS specifications.

## DISCOVERED BEHAVIOR

### Normal CSS Behavior (What SHOULD happen):
- `position: static` (default) - Element scrolls with page
- `position: fixed` - Element stays fixed at specified position in viewport
- `position: sticky` - Element scrolls until threshold, then sticks
- `position: absolute` - Element positioned relative to positioned parent, scrolls with page

### What ACTUALLY Happens on This Server:
- **No position specified** - Header stays at top (acts like fixed!) ✓
- **`position: fixed`** - Header scrolls away with content (COMPLETELY WRONG) ✗
- **`position: sticky`** - Header scrolls away with content (WRONG) ✗
- **`position: absolute`** - Header stays fixed at top (acts like fixed!) ✓

## THE SOLUTION
Use `position: absolute` instead of `position: fixed` to achieve a fixed header effect.

## Current Implementation:
```css
.header {
    position: absolute;  /* Acts like fixed in this environment */
    top: 0;
    left: 0;
    width: 100%;
    z-index: 9999;
}

body {
    padding-top: 180px;  /* Push content below header */
}
```

## Files Modified:
- `/website/views/index.ejs` - Changed to position: absolute
- `/website/views/hiscores.ejs` - Changed to position: absolute  
- `/website/css/style.css` - Changed .header to position: absolute
- `/website/views/partials/header.ejs` - Uses the modified CSS

## Root Cause: UNKNOWN
- Not caused by the CMS implementation
- Not caused by Express or EJS
- Not caused by browser (happens in both Firefox and Chrome)
- Affects ALL pages served from this server
- Even static HTML files exhibit this inverted behavior

## Testing Performed:
1. Created multiple test pages with minimal HTML
2. Tested with inline styles, external CSS, and style tags
3. Bypassed EJS templating - issue persisted
4. Served raw HTML from Express - issue persisted
5. Served static files directly - issue persisted
6. Removed all external CSS - issue persisted

## Hypothesis:
Something at the HTTP server level or in the Node.js environment is fundamentally altering how CSS positioning works. This is unprecedented and should not be possible, yet it's consistently reproducible.

## IMPORTANT:
**DO NOT** change back to `position: fixed` - it will break the header and make it scroll.
**DO NOT** remove `position: absolute` - it's the only thing keeping the header in place.

This is the opposite of conventional CSS, but it works in this specific environment.