const fs = require('fs');

function iso(x, y, z) {
    const sx = (x - y) * Math.cos(Math.PI / 6);
    const sy = (x + y) * Math.sin(Math.PI / 6) - z;
    return [sx, sy];
}

function block(x, y, w, h, z, hZ, topCol, leftCol, rightCol, dataZone, label) {
    const [t1x, t1y] = iso(x, y, z + hZ);
    const [t2x, t2y] = iso(x + w, y, z + hZ);
    const [t3x, t3y] = iso(x + w, y + h, z + hZ);
    const [t4x, t4y] = iso(x, y + h, z + hZ);

    const [b2x, b2y] = iso(x + w, y, z);
    const [b3x, b3y] = iso(x + w, y + h, z);
    const [b4x, b4y] = iso(x, y + h, z);

    let html = `  <g class="zone-group" data-zone="${dataZone}" data-label="${label}">\n`;
    // Right face
    html += `    <path d="M ${t2x.toFixed(1)} ${t2y.toFixed(1)} L ${t3x.toFixed(1)} ${t3y.toFixed(1)} L ${b3x.toFixed(1)} ${b3y.toFixed(1)} L ${b2x.toFixed(1)} ${b2y.toFixed(1)} Z" fill="${rightCol}" />\n`;
    // Left face
    html += `    <path d="M ${t4x.toFixed(1)} ${t4y.toFixed(1)} L ${t3x.toFixed(1)} ${t3y.toFixed(1)} L ${b3x.toFixed(1)} ${b3y.toFixed(1)} L ${b4x.toFixed(1)} ${b4y.toFixed(1)} Z" fill="${leftCol}" />\n`;
    // Top face
    html += `    <path d="M ${t1x.toFixed(1)} ${t1y.toFixed(1)} L ${t2x.toFixed(1)} ${t2y.toFixed(1)} L ${t3x.toFixed(1)} ${t3y.toFixed(1)} L ${t4x.toFixed(1)} ${t4y.toFixed(1)} Z" fill="${topCol}" class="zone-top" />\n`;
    html += `  </g>\n`;
    return html;
}

let svg = `<svg viewBox="-600 -100 1200 800" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; font-family: sans-serif;">\n`;

// Stage
svg += block(-100, -50, 200, 100, 0, 30, '#d1d5db', '#9ca3af', '#6b7280', 'STAGE', 'STAGE');

// VIP (Red) - two blocks
svg += block(50, 60, 140, 80, 0, 20, '#ef4444', '#dc2626', '#b91c1c', 'VIP', 'VIP');
svg += block(-190, 60, 140, 80, 0, 20, '#ef4444', '#dc2626', '#b91c1c', 'VIP', 'VIP');

// Khusus (Green)
svg += block(10, 150, 100, 100, 0, 15, '#22c55e', '#16a34a', '#15803d', 'Khusus S', 'Khusus');
svg += block(-110, 150, 100, 100, 0, 15, '#22c55e', '#16a34a', '#15803d', 'Khusus N', 'Khusus');

// Klas I (Blue) - Free Seating
svg += block(120, -50, 80, 300, 0, 15, '#3b82f6', '#2563eb', '#1d4ed8', 'Klas S1', 'Class I (South)');
svg += block(-200, -50, 80, 300, 0, 15, '#3b82f6', '#2563eb', '#1d4ed8', 'Klas N1', 'Class I (North)');

// Klas II (Yellow)
// Let's make it composed of multiple blocks to form L-shape, or just a straight block for simplicity, 
// wait, the L-shape is nice.
// Right side L-shape:
svg += block(210, -50, 70, 370, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas S2 C', 'Class II (South)');
svg += block(120, 260, 90, 60, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas S2 C', 'Class II (South)');

// Left side L-shape:
svg += block(-280, -50, 70, 370, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas N2 C', 'Class II (North)');
svg += block(-210, 260, 90, 60, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas N2 C', 'Class II (North)');

// Free Seating (Purple)
svg += block(290, -50, 60, 370, 0, 5, '#a855f7', '#9333ea', '#7e22ce', 'Free Seating S', 'Free Seating');
svg += block(-350, -50, 60, 370, 0, 5, '#a855f7', '#9333ea', '#7e22ce', 'Free Seating N', 'Free Seating');

svg += `</svg>\n`;
fs.writeFileSync('test.svg', svg);
