const fs = require('fs');

function block(x1, x2, x3, x4, y1, y2, z, colorTop, colorFront, colorSide, sideType, dataZone, label) {
    let top = `    <path d="M ${x1},${y1} L ${x2},${y1} L ${x3},${y2} L ${x4},${y2} Z" fill="${colorTop}" class="zone-top" />`;
    let front = `    <path d="M ${x4},${y2} L ${x3},${y2} L ${x3},${y2+z} L ${x4},${y2+z} Z" fill="${colorFront}" />`;
    
    let side = '';
    if (sideType === 'left') {
        side = `    <path d="M ${x1},${y1} L ${x4},${y2} L ${x4},${y2+z} L ${x1},${y1+z} Z" fill="${colorSide}" />`;
    } else if (sideType === 'right') {
        side = `    <path d="M ${x2},${y1} L ${x3},${y2} L ${x3},${y2+z} L ${x2},${y1+z} Z" fill="${colorSide}" />`;
    }

    return `  <g class="zone-group" data-zone="${dataZone}" data-label="${label}">\n${front}\n${side}\n${top}\n  </g>\n`;
}

// L-shaped blocks
function blockL(x1, x2, x3, x4, x5, x6, y1, y2, y3, z, colorTop, colorFront, colorSide, sideType, dataZone, label) {
    // x1..x6 define the vertices of the L-shape from top-left, top-right, inner-corner...
    // We will just write raw paths for them.
    
    // Top face
    let top = `    <path d="M ${x1},${y1} L ${x2},${y1} L ${x3},${y2} L ${x4},${y2} L ${x5},${y3} L ${x6},${y3} Z" fill="${colorTop}" class="zone-top" />`;
    
    // Front face (bottom-most edges)
    // The front faces are: (x6,y3) to (x5,y3)  -- wait, which ones are at the bottom?
    // Assuming x5,x6 are at y3 (bottom).
    let front = `    <path d="M ${x6},${y3} L ${x5},${y3} L ${x5},${y3+z} L ${x6},${y3+z} Z" fill="${colorFront}" />`;
    
    // Inner front face (if L-shape cuts inwards)
    let innerFront = `    <path d="M ${x4},${y2} L ${x3},${y2} L ${x3},${y2+z} L ${x4},${y2+z} Z" fill="${colorFront}" />`;

    let side = '';
    if (sideType === 'left') {
        side = `    <path d="M ${x1},${y1} L ${x6},${y3} L ${x6},${y3+z} L ${x1},${y1+z} Z" fill="${colorSide}" />`;
        // inner side
        side += `\n    <path d="M ${x2},${y1} L ${x3},${y2} L ${x3},${y2+z} L ${x2},${y1+z} Z" fill="${colorSide}" />`;
    } else if (sideType === 'right') {
        side = `    <path d="M ${x2},${y1} L ${x5},${y3} L ${x5},${y3+z} L ${x2},${y1+z} Z" fill="${colorSide}" />`;
        // inner side
        side += `\n    <path d="M ${x1},${y1} L ${x6},${y2} L ${x6},${y2+z} L ${x1},${y1+z} Z" fill="${colorSide}" />`;
    }

    return `  <g class="zone-group" data-zone="${dataZone}" data-label="${label}">\n${front}\n${innerFront}\n${side}\n${top}\n  </g>\n`;
}


let svg = `<svg viewBox="0 0 1000 600" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; font-family: sans-serif;">\n`;

// Colors
let cStage = ['#d1d5db', '#9ca3af', '#6b7280'];
let cVIP = ['#ef4444', '#dc2626', '#b91c1c'];
let cKhusus = ['#22c55e', '#16a34a', '#15803d'];
let cBlue = ['#3b82f6', '#2563eb', '#1d4ed8'];
let cYellow = ['#eab308', '#ca8a04', '#a16207'];
let cPurple = ['#a855f7', '#9333ea', '#7e22ce'];

// STAGE
svg += block(350, 650, 700, 300, 100, 200, cStage[0], cStage[1], cStage[2], 'left', 'STAGE', 'STAGE');

// VIP RED
svg += block(340, 490, 510, 320, 220, 270, cVIP[0], cVIP[1], cVIP[2], 'left', 'VIP', 'VIP');
svg += block(510, 660, 680, 490, 220, 270, cVIP[0], cVIP[1], cVIP[2], 'right', 'VIP', 'VIP');

// KHUSUS GREEN
svg += block(280, 470, 490, 240, 290, 360, cKhusus[0], cKhusus[1], cKhusus[2], 'left', 'Khusus N', 'Khusus');
svg += block(530, 720, 760, 510, 290, 360, cKhusus[0], cKhusus[1], cKhusus[2], 'right', 'Khusus S', 'Khusus');

// KLAS 1 BLUE (L-shape)
// Left: Top-left(260,220), Top-right(320,220), Inner-right(300,270), Inner-right-2(260,290) - wait, let's just make them 2 overlapping trapezoids for simplicity!
function multiBlock(blocks, z, c, sideType, dataZone, label) {
    let g = `  <g class="zone-group" data-zone="${dataZone}" data-label="${label}">\n`;
    blocks.forEach(b => {
        let [x1, x2, x3, x4, y1, y2] = b;
        g += `    <path d="M ${x4},${y2} L ${x3},${y2} L ${x3},${y2+z} L ${x4},${y2+z} Z" fill="${c[1]}" />\n`;
        if (sideType === 'left') {
            g += `    <path d="M ${x1},${y1} L ${x4},${y2} L ${x4},${y2+z} L ${x1},${y1+z} Z" fill="${c[2]}" />\n`;
        } else if (sideType === 'right') {
            g += `    <path d="M ${x2},${y1} L ${x3},${y2} L ${x3},${y2+z} L ${x2},${y1+z} Z" fill="${c[2]}" />\n`;
        }
    });
    blocks.forEach(b => {
        let [x1, x2, x3, x4, y1, y2] = b;
        g += `    <path d="M ${x1},${y1} L ${x2},${y1} L ${x3},${y2} L ${x4},${y2} Z" fill="${c[0]}" class="zone-top" />\n`;
    });
    return g + `  </g>\n`;
}

// Blue Left: Top part flanking Red, Bottom part flanking Green
svg += multiBlock([
    [260, 320, 300, 220, 220, 270], // top part
    [220, 300, 260, 160, 270, 360], // bottom part
], 20, cBlue, 'left', 'Klas N1', 'Class I (North)');

svg += multiBlock([
    [680, 740, 780, 700, 220, 270], // top part
    [700, 780, 840, 740, 270, 360], // bottom part
], 20, cBlue, 'right', 'Klas S1', 'Class I (South)');

// Yellow Left
svg += multiBlock([
    [180, 240, 200, 120, 220, 270], // top part
    [120, 200, 160, 60,  270, 360], // mid part
    [ 60, 160, 220, 80,  360, 440], // bottom part forming L
], 20, cYellow, 'left', 'Klas N2 C', 'Class II (North)');

svg += multiBlock([
    [760, 820, 880, 800, 220, 270], // top part
    [800, 880, 940, 840, 270, 360], // mid part
    [840, 940, 920, 780, 360, 440], // bottom part forming L
], 20, cYellow, 'right', 'Klas S2 C', 'Class II (South)');

// Purple Left
svg += multiBlock([
    [100, 160, 100, 20, 220, 360], // vertical part
    [ 20, 100, 220, 0,  360, 490], // bottom part forming L
], 20, cPurple, 'left', 'Free Seating N', 'Free Seating');

svg += multiBlock([
    [840, 900, 980, 900, 220, 360], // vertical part
    [900, 980, 1000, 780, 360, 490], // bottom part forming L
], 20, cPurple, 'right', 'Free Seating S', 'Free Seating');

svg += `</svg>\n`;
fs.writeFileSync('public/test4.svg', svg);
