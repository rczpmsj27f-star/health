# PWA Icons

This directory contains icons for the Medication Reminder PWA.

## Icon Sizes Required

- 72x72 (badge)
- 96x96
- 128x128
- 144x144
- 152x152
- 192x192 (main icon)
- 384x384
- 512x512

## Generating Icons

You can use the following methods to generate icons:

### Option 1: Online Tools
- Use https://www.pwabuilder.com/ to generate icons
- Use https://realfavicongenerator.net/ for favicon and icons

### Option 2: ImageMagick
```bash
# Install ImageMagick if not already installed
# Create a source image (e.g., icon-source.png) and run:

convert icon-source.png -resize 72x72 icon-72x72.png
convert icon-source.png -resize 96x96 icon-96x96.png
convert icon-source.png -resize 128x128 icon-128x128.png
convert icon-source.png -resize 144x144 icon-144x144.png
convert icon-source.png -resize 152x152 icon-152x152.png
convert icon-source.png -resize 192x192 icon-192x192.png
convert icon-source.png -resize 384x384 icon-384x384.png
convert icon-source.png -resize 512x512 icon-512x512.png
```

### Option 3: Node.js script
Use the included icon-generator.js script:
```bash
npm install sharp
node icon-generator.js
```

## Placeholder Icons

For development, placeholder icons have been created using SVG. Replace these with proper branded icons for production.
