<?php

namespace App\Support;

/**
 * Computes seat positions for the Ramayana Outdoor venue floor plan.
 * The layout uses exact physical coordinates derived from the floor-plan image.
 */
class RamayanaOutdoorLayout
{
    public const VIEWBOX_WIDTH = 2200;
    public const VIEWBOX_HEIGHT = 1000;

    /**
     * Seats per row for each zone.
     */
    public const ZONES = [
        'VIP' => [
            'A' => 16, 'B' => 16, 'C' => 16, 'D' => 16
        ],
        'Khusus N' => [
            'A' => 13, 'B' => 13, 'C' => 13, 'D' => 13, 'E' => 13
        ],
        'Khusus S' => [
            'A' => 13, 'B' => 13, 'C' => 13, 'D' => 13, 'E' => 13
        ],
        'Klas N1' => [
            'A' => 13, 'B' => 12, 'C' => 11, 'D' => 10, 'E' => 8
        ],
        'Klas S1' => [
            'A' => 13, 'B' => 12, 'C' => 11, 'D' => 10, 'E' => 8
        ],
        'Klas N2 A' => [
            'A' => 12, 'B' => 11, 'C' => 9, 'D' => 7, 'E' => 6
        ],
        'Klas S2 A' => [
            'A' => 12, 'B' => 11, 'C' => 9, 'D' => 7, 'E' => 6
        ],
        'Klas N2 B' => [
            'A' => 14, 'B' => 13, 'C' => 10, 'D' => 8, 'E' => 7
        ],
        'Klas S2 B' => [
            'A' => 14, 'B' => 13, 'C' => 10, 'D' => 8, 'E' => 7
        ],
        'Klas N2 C' => [
            'A' => 21, 'B' => 21, 'C' => 21, 'D' => 21, 'E' => 21, 'F' => 21, 'G' => 21, 'H' => 21, 'I' => 21, 'J' => 21, 'K' => 21
        ],
        'Klas S2 C' => [
            'A' => 21, 'B' => 21, 'C' => 21, 'D' => 21, 'E' => 21, 'F' => 21, 'G' => 21, 'H' => 21, 'I' => 21, 'J' => 21, 'K' => 21
        ],
    ];

    /**
     * Approximate anchors for each block.
     * x, y points to the center-bottom or specific corner.
     */
    private const ANCHORS = [
        'VIP'      => ['x' => 1100, 'y' => 150],
        'Khusus N' => ['x' => 800,  'y' => 450],
        'Khusus S' => ['x' => 1400, 'y' => 450],
        'Klas N1'  => ['x' => 650,  'y' => 200],
        'Klas S1'  => ['x' => 1550, 'y' => 200],
        'Klas N2 A'=> ['x' => 530,  'y' => 580],
        'Klas S2 A'=> ['x' => 1670, 'y' => 580],
        'Klas N2 B'=> ['x' => 330,  'y' => 800],
        'Klas S2 B'=> ['x' => 1870, 'y' => 800],
        'Klas N2 C'=> ['x' => 300,  'y' => 350],
        'Klas S2 C'=> ['x' => 1900, 'y' => 350],
    ];

    /** Degrees clockwise the block is rotated */
    private const ROTATIONS = [
        'VIP' => 0,
        'Khusus N' => 0,
        'Khusus S' => 0,
        'Klas N1' => 45,
        'Klas S1' => -45,
        'Klas N2 A' => 45,
        'Klas S2 A' => -45,
        'Klas N2 B' => 45,
        'Klas S2 B' => -45,
        'Klas N2 C' => 90,
        'Klas S2 C' => -90,
    ];

    private const SEAT_SPACING = [
        'special' => 28,
        '1' => 24,
        '2' => 22,
    ];

    private const ROW_SPACING = [
        'special' => 36,
        '1' => 32,
        '2' => 30,
    ];

    public static function zones(): array
    {
        return array_keys(self::ZONES);
    }

    public static function rowSeatCounts(string $zone): array
    {
        return self::ZONES[$zone] ?? [];
    }

    /**
     * Calculate position for a given seat.
     * @return array{x: float, y: float, rotation: float}
     */
    public static function seatPosition(string $zone, string $row, int|float $seatIndex, int $rowCount): array
    {
        // Determine row offset so that the last row (e.g. E) is at the anchor (closest to stage),
        // and earlier rows (e.g. A) are pushed further down (farthest from stage).
        $rowKeys = array_keys(self::ZONES[$zone] ?? ['A' => 1]);
        $rowIndex = array_search($row, $rowKeys, true) ?: 0;
        $maxRowIndex = count($rowKeys) - 1;

        $classKey = match ($zone) {
            'VIP', 'Khusus N', 'Khusus S' => 'special',
            'Klas N1', 'Klas S1' => '1',
            default => '2',
        };

        // lx is horizontal distance from center of row
        $lx = (($rowCount - 1) / 2 - $seatIndex) * self::SEAT_SPACING[$classKey];
        
        // ly pushes row A furthest down, row E at 0
        $ly = ($maxRowIndex - $rowIndex) * self::ROW_SPACING[$classKey];

        $theta = deg2rad(self::ROTATIONS[$zone] ?? 0);
        
        $anchor = self::ANCHORS[$zone] ?? ['x' => 600, 'y' => 400];
        
        $x = $anchor['x'] + $lx * cos($theta) - $ly * sin($theta);
        $y = $anchor['y'] + $lx * sin($theta) + $ly * cos($theta);

        return [
            'x' => round($x, 1),
            'y' => round($y, 1),
            'rotation' => self::ROTATIONS[$zone] ?? 0
        ];
    }
}
