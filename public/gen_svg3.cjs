const fs = require('fs');

// We will draw everything in a standard 2D top-down grid (x goes right, y goes down).
// Then we wrap the whole thing in an isometric transform.
// For the 3D thickness, we can use a CSS drop-shadow, or draw the shape multiple times
// slightly shifted to create an extrusion.

let svg = `<svg viewBox="-100 -50 800 600" preserveAspectRatio="xMidYMid meet" style="width: 100%; height: 100%; font-family: sans-serif;">
  <defs>
    <!-- Simple extrusion using a few layers of the same shape -->
    <filter id="extrude-red">
      <feDropShadow dx="-1" dy="2" stdDeviation="0" flood-color="#b91c1c" />
      <feDropShadow dx="-2" dy="4" stdDeviation="0" flood-color="#b91c1c" />
      <feDropShadow dx="-3" dy="6" stdDeviation="0" flood-color="#b91c1c" />
      <feDropShadow dx="-4" dy="8" stdDeviation="0" flood-color="#b91c1c" />
      <feDropShadow dx="-5" dy="10" stdDeviation="0" flood-color="#b91c1c" />
    </filter>
  </defs>

  <!-- Isometric Projection Wrapper -->
  <!-- rotate(-45) and scaleY(0.577) creates an isometric view from a top-down view -->
  <g transform="scale(1, 0.577) rotate(-45) translate(0, 300)">
    
    <!-- Stage -->
    <g class="zone-group" data-zone="STAGE" data-label="STAGE">
       <!-- Side walls simulated by translation -->
       <rect x="250" y="-100" width="200" height="120" fill="#9ca3af" transform="translate(-10, 10)" />
       <rect x="250" y="-100" width="200" height="120" fill="#d1d5db" class="zone-top" />
    </g>

  </g>
</svg>
`;

fs.writeFileSync('public/test3.svg', svg);
