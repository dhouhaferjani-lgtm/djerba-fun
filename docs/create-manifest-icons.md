# Manifest Icon Creation Guide

## Required Icons

The PWA manifest requires three PNG icon files in the `apps/web/public/` directory:

- `icon-192.png` (192x192px) - For mobile devices
- `icon-384.png` (384x384px) - For tablets
- `icon-512.png` (512x512px) - For high-resolution displays

## Source File

The project already has an SVG icon at `apps/web/public/icon.svg`:

- Dark green circle (#0D642E - brand primary color)
- White "GA" text centered

## Creation Methods

### Option 1: Online Conversion (Recommended)

1. Visit https://svg2png.com or https://cloudconvert.com/svg-to-png
2. Upload `apps/web/public/icon.svg`
3. Convert to PNG at each required size (192px, 384px, 512px)
4. Download and save to `apps/web/public/`

### Option 2: Using ImageMagick (Command Line)

```bash
cd apps/web/public

# Install ImageMagick if not already installed
# macOS: brew install imagemagick
# Ubuntu: sudo apt-get install imagemagick

# Convert to PNG at different sizes
convert icon.svg -resize 192x192 icon-192.png
convert icon.svg -resize 384x384 icon-384.png
convert icon.svg -resize 512x512 icon-512.png
```

### Option 3: Using Inkscape (GUI)

1. Install Inkscape: https://inkscape.org/
2. Open `icon.svg` in Inkscape
3. File > Export PNG Image
4. Set width/height to 192, 384, or 512
5. Export and save to `apps/web/public/`
6. Repeat for each size

## Verification

After creating the icons, verify they exist:

```bash
ls -lh apps/web/public/icon-*.png
```

All three files should be present and roughly:

- icon-192.png: ~5-10 KB
- icon-384.png: ~15-25 KB
- icon-512.png: ~25-40 KB

## Testing

1. Build the app: `pnpm build`
2. Open browser DevTools > Application tab
3. Check "Manifest" section - all icons should be listed without errors
4. Test PWA installation on mobile device

## Notes

- Icons use `purpose: "maskable"` for 192px and 512px (adaptive icons on Android)
- Icons use `purpose: "any"` for 384px (standard display)
- The design uses the brand's primary color (#0D642E) for consistency
