// Simple icon generator for PWA
// Creates placeholder PNG icons with a pill emoji
const fs = require('fs');
const path = require('path');

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

// For now, create simple SVG icons that can be used as placeholders
const createSVGIcon = (size) => {
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <rect width="${size}" height="${size}" fill="#4CAF50" rx="${size * 0.2}"/>
  <text x="50%" y="50%" font-size="${size * 0.6}" text-anchor="middle" dominant-baseline="central" fill="white">💊</text>
</svg>`;
};

// Badge icon (simpler, smaller)
const createBadgeSVG = (size) => {
  return `<?xml version="1.0" encoding="UTF-8"?>
<svg width="${size}" height="${size}" viewBox="0 0 ${size} ${size}" xmlns="http://www.w3.org/2000/svg">
  <circle cx="${size/2}" cy="${size/2}" r="${size/2}" fill="#4CAF50"/>
  <text x="50%" y="50%" font-size="${size * 0.7}" text-anchor="middle" dominant-baseline="central">💊</text>
</svg>`;
};

console.log('Generating placeholder icons...');

sizes.forEach(size => {
  const svg = createSVGIcon(size);
  const filename = path.join(__dirname, `icon-${size}x${size}.svg`);
  fs.writeFileSync(filename, svg);
  console.log(`Created ${filename}`);
});

// Create badge
const badgeSVG = createBadgeSVG(72);
const badgeFilename = path.join(__dirname, 'badge-72x72.svg');
fs.writeFileSync(badgeFilename, badgeSVG);
console.log(`Created ${badgeFilename}`);

console.log('\nPlaceholder SVG icons created!');
console.log('For production, convert these to PNG or replace with custom designed icons.');
console.log('\nTo convert to PNG, you can use:');
console.log('- Online: https://cloudconvert.com/svg-to-png');
console.log('- CLI: ImageMagick or rsvg-convert');
