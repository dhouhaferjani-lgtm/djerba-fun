#!/bin/bash

# Script to generate PWA assets from SVG files
# Requires ImageMagick to be installed: brew install imagemagick

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
PUBLIC_DIR="$PROJECT_ROOT/apps/web/public"

echo "Generating PWA assets..."

# Check if ImageMagick is installed
if ! command -v convert &> /dev/null; then
    echo "Error: ImageMagick is not installed."
    echo "Install it with: brew install imagemagick"
    exit 1
fi

# Check if source files exist
if [ ! -f "$PUBLIC_DIR/icon.svg" ]; then
    echo "Error: icon.svg not found in $PUBLIC_DIR"
    exit 1
fi

if [ ! -f "$PUBLIC_DIR/og-image.svg" ]; then
    echo "Error: og-image.svg not found in $PUBLIC_DIR"
    exit 1
fi

# Generate icon sizes for PWA
echo "Generating icon-192.png..."
convert "$PUBLIC_DIR/icon.svg" -resize 192x192 "$PUBLIC_DIR/icon-192.png"

echo "Generating icon-384.png..."
convert "$PUBLIC_DIR/icon.svg" -resize 384x384 "$PUBLIC_DIR/icon-384.png"

echo "Generating icon-512.png..."
convert "$PUBLIC_DIR/icon.svg" -resize 512x512 "$PUBLIC_DIR/icon-512.png"

# Generate Open Graph image
echo "Generating og-image.png (1200x630)..."
convert "$PUBLIC_DIR/og-image.svg" -resize 1200x630 "$PUBLIC_DIR/og-image.png"

# Generate Apple Touch Icon
echo "Generating apple-touch-icon.png (180x180)..."
convert "$PUBLIC_DIR/icon.svg" -resize 180x180 "$PUBLIC_DIR/apple-touch-icon.png"

# Generate favicon
echo "Generating favicon-32x32.png and favicon-16x16.png..."
convert "$PUBLIC_DIR/icon.svg" -resize 32x32 "$PUBLIC_DIR/favicon-32x32.png"
convert "$PUBLIC_DIR/icon.svg" -resize 16x16 "$PUBLIC_DIR/favicon-16x16.png"

echo "✅ All PWA assets generated successfully!"
echo ""
echo "Generated files:"
echo "  - icon-192.png (PWA)"
echo "  - icon-384.png (PWA)"
echo "  - icon-512.png (PWA)"
echo "  - og-image.png (Open Graph 1200x630)"
echo "  - apple-touch-icon.png (iOS)"
echo "  - favicon-32x32.png"
echo "  - favicon-16x16.png"
