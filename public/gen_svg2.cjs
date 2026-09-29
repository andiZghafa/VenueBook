const fs = require('fs');

function iso(x, y, z) {
    // 30 degree isometric projection
    const angle = Math.PI / 6;
    const sx = (x - y) * Math.cos(angle);
    const sy = (x + y) * Math.sin(angle) - z;
    return [sx, sy];
}

// Draw an extruded polygon. `pts` is array of [x,y].
// Assumes pts are in clockwise order for the top face.
function polyBlock(pts, z, hZ, topCol, leftCol, rightCol, dataZone, label) {
    let html = `  <g class="zone-group" data-zone="${dataZone}" data-label="${label}">\n`;
    
    // For each edge, draw the side face. 
    // We only want to draw side faces that are "visible" from the camera.
    // In our isometric projection (camera looking from +z, -x, -y), 
    // visible faces have normals pointing towards +x or +y.
    // Actually, simply drawing all side faces and then the top face on top usually works if we sort them, 
    // but drawing just the bottom edges that go "down and right" or "down and left" in 2D screen space is better.
    // Since we know the exact shapes, let's just generate the sides by taking each segment 
    // and if it faces the "front" (i.e. x increases or y increases), we render it.
    
    // In our 2D grid, x goes down-right, y goes down-left.
    // Faces with normal in +x direction: drawn with `rightCol`
    // Faces with normal in +y direction: drawn with `leftCol`
    
    for (let i = 0; i < pts.length; i++) {
        let p1 = pts[i];
        let p2 = pts[(i + 1) % pts.length];
        
        let dx = p2[0] - p1[0];
        let dy = p2[1] - p1[1];
        
        let color = null;
        if (dx > 0 && dy === 0) color = rightCol; // Normal is +y
        if (dy > 0 && dx === 0) color = leftCol;  // Normal is +x
        
        // Wait, standard grid: right is +x, down is +y.
        // If we go clockwise:
        // Top edge: dx > 0, dy = 0. Normal is -y (hidden).
        // Right edge: dx = 0, dy > 0. Normal is +x (visible, right side). -> rightCol
        // Bottom edge: dx < 0, dy = 0. Normal is +y (visible, left side). -> leftCol
        // Left edge: dx = 0, dy < 0. Normal is -x (hidden).
        
        if (dx === 0 && dy > 0) color = rightCol;
        if (dx < 0 && dy === 0) color = leftCol;
        
        // Inside corners for L-shapes can also be visible.
        // For example, going UP (dy < 0) means normal is -x (hidden).
        // Going RIGHT (dx > 0) means normal is -y (hidden).
        
        if (color) {
            const [t1x, t1y] = iso(p1[0], p1[1], z + hZ);
            const [t2x, t2y] = iso(p2[0], p2[1], z + hZ);
            const [b1x, b1y] = iso(p1[0], p1[1], z);
            const [b2x, b2y] = iso(p2[0], p2[1], z);
            
            html += `    <path d="M ${t1x.toFixed(1)} ${t1y.toFixed(1)} L ${t2x.toFixed(1)} ${t2y.toFixed(1)} L ${b2x.toFixed(1)} ${b2y.toFixed(1)} L ${b1x.toFixed(1)} ${b1y.toFixed(1)} Z" fill="${color}" />\n`;
        }
    }
    
    // Top face
    let topPath = "M " + pts.map(p => {
        let [sx, sy] = iso(p[0], p[1], z + hZ);
        return `${sx.toFixed(1)} ${sy.toFixed(1)}`;
    }).join(" L ") + " Z";
    
    html += `    <path d="${topPath}" fill="${topCol}" class="zone-top" />\n`;
    html += `  </g>\n`;
    return html;
}

// Rectangle helper
function rect(x, y, w, h) {
    return [
        [x, y],
        [x + w, y],
        [x + w, y + h],
        [x, y + h]
    ];
}

let svg = `<svg viewBox="-500 -100 1000 700" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; font-family: sans-serif;">\n`;

// Stage
svg += polyBlock(rect(140, 0, 200, 140), 0, 30, '#d1d5db', '#9ca3af', '#6b7280', 'STAGE', 'STAGE');

// Red Left
svg += polyBlock(rect(165, 160, 70, 60), 0, 20, '#ef4444', '#dc2626', '#b91c1c', 'VIP', 'VIP');
// Red Right
svg += polyBlock(rect(245, 160, 70, 60), 0, 20, '#ef4444', '#dc2626', '#b91c1c', 'VIP', 'VIP');

// Green Left
svg += polyBlock(rect(185, 230, 40, 70), 0, 15, '#22c55e', '#16a34a', '#15803d', 'Khusus N', 'Khusus');
// Green Right
svg += polyBlock(rect(255, 230, 40, 70), 0, 15, '#22c55e', '#16a34a', '#15803d', 'Khusus S', 'Khusus');

// Blue Left (L-shape/stepped)
// Top-Left, Top-Right, Mid-Right, Bot-Right, Bot-Left, Mid-Left
let blueLeft = [
    [115, 160], [155, 160], [155, 220], [175, 220], [175, 300], [115, 300]
];
svg += polyBlock(blueLeft, 0, 15, '#3b82f6', '#2563eb', '#1d4ed8', 'Klas N1', 'Class I (North)');

// Blue Right (mirror of Left)
// To mirror `x`: 480 - x. Since it's clockwise, mirroring reverses order.
let blueRight = blueLeft.map(p => [480 - p[0], p[1]]).reverse();
svg += polyBlock(blueRight, 0, 15, '#3b82f6', '#2563eb', '#1d4ed8', 'Klas S1', 'Class I (South)');

// Yellow Left (L-shape)
let yellowLeft = [
    [65, 160], [105, 160], [105, 310], [175, 310], [175, 350], [65, 350]
];
svg += polyBlock(yellowLeft, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas N2 C', 'Class II (North)');

let yellowRight = yellowLeft.map(p => [480 - p[0], p[1]]).reverse();
svg += polyBlock(yellowRight, 0, 10, '#eab308', '#ca8a04', '#a16207', 'Klas S2 C', 'Class II (South)');

// Purple Left (L-shape)
let purpleLeft = [
    [15, 160], [55, 160], [55, 360], [175, 360], [175, 400], [15, 400]
];
svg += polyBlock(purpleLeft, 0, 5, '#a855f7', '#9333ea', '#7e22ce', 'Free Seating N', 'Free Seating');

let purpleRight = purpleLeft.map(p => [480 - p[0], p[1]]).reverse();
svg += polyBlock(purpleRight, 0, 5, '#a855f7', '#9333ea', '#7e22ce', 'Free Seating S', 'Free Seating');

svg += `</svg>\n`;
fs.writeFileSync('public/test2.svg', svg);
