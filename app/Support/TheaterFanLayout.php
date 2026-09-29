<?php

namespace App\Support;

/**
 * Computes seat positions for the Trimurti-style theater floor plan: a center "Special"
 * block facing the stage, flanked by an inner block (N1/S1) and a more sharply angled
 * outer block (N2/S2) — three rotated trapezoids fanning outward from the stage, each
 * wider at the back (row A) than the front (row E). Positions are deterministic (derived
 * only from zone/row/seat index), so a seat never moves on screen regardless of which
 * seats are booked.
 *
 * N2/S2 sit in their own band right beside the stage (rotated close to 90°, so row E —
 * nearest the stage — reads as a column touching the stage's side); N1/Special/S1 sit in
 * a separate band below, front-left/center/front-right. The two bands are vertically
 * separated with a clear gap so neither block's bounding area overlaps the other's.
 *
 * Coordinate space is a 1090x615 SVG viewBox, sized to hug the content tightly (minimal
 * side margin) since the page renders it at full container width. S1/S2 are produced by
 * mirroring N1/N2 across the vertical center line (x = 545) rather than duplicating their
 * own constants. The gap between "special" and N1/S1 is wide enough (45 units) for both
 * blocks' own seats plus a row-letter marker between them without touching either.
 */
class TheaterFanLayout
{
    public const VIEWBOX_WIDTH = 1090;

    public const VIEWBOX_HEIGHT = 615;

    /** Row letters ordered from nearest-to-stage to farthest. */
    public const ROWS = ['E', 'D', 'C', 'B', 'A'];

    /**
     * Seats per row for each zone. S1 mirrors N1 and S2 mirrors N2 exactly, so only the
     * left-hand zones and the shared middle zone are defined here.
     */
    public const ZONES = [
        'N2' => ['A' => 23, 'B' => 20, 'C' => 19, 'D' => 18, 'E' => 12],
        'N1' => ['A' => 20, 'B' => 15, 'C' => 13, 'D' => 11, 'E' => 8],
        'special' => ['A' => 16, 'B' => 14, 'C' => 12, 'D' => 10, 'E' => 8],
        'S1' => ['A' => 20, 'B' => 15, 'C' => 13, 'D' => 11, 'E' => 8],
        'S2' => ['A' => 23, 'B' => 20, 'C' => 19, 'D' => 18, 'E' => 12],
    ];

    private const SEAT_SPACING = ['special' => 23, 'N1' => 18.4, 'N2' => 14.95];

    private const ROW_SPACING = ['special' => 31.05, 'N1' => 26.45, 'N2' => 20.35];

    /**
     * Row E (nearest the stage) sits at the anchor; the block fans out toward row A as it
     * rotates away from the stage. N1/Special share a lower band (front-left/center),
     * pushed down far enough to clear N2's larger footprint; N2 is anchored high, right
     * beside the stage, in its own band well clear of N1's.
     */
    private const ANCHOR = [
        'special' => ['x' => 545, 'y' => 380.51],
        'N1' => ['x' => 229.02, 'y' => 380.51],
        'N2' => ['x' => 244.53, 'y' => 160.47],
    ];

    /** Degrees clockwise the block is rotated away from the special block's orientation. */
    private const ROTATION_DEGREES = ['special' => 0, 'N1' => 30, 'N2' => 45];

    public static function zones(): array
    {
        return array_keys(self::ZONES);
    }

    public static function rowSeatCounts(string $zone): array
    {
        return self::ZONES[$zone];
    }

    public static function zoneTotal(string $zone): int
    {
        return array_sum(self::ZONES[$zone]);
    }

    /**
     * $seatIndex accepts a fractional value (e.g. the row's midpoint) for callers that
     * need a reference point which isn't a real seat, such as a row-letter marker.
     *
     * @return array{x: float, y: float}
     */
    public static function seatPosition(string $zone, string $row, int|float $seatIndex, int $rowCount): array
    {
        $mirror = in_array($zone, ['S1', 'S2'], true);
        $baseZone = match ($zone) {
            'S1' => 'N1',
            'S2' => 'N2',
            default => $zone,
        };

        $rowIndex = array_search($row, self::ROWS, true);
        // Seat 1 sits at the right end of the row, numbering increasing to the left.
        $lx = (($rowCount - 1) / 2 - $seatIndex) * self::SEAT_SPACING[$baseZone];
        $ly = $rowIndex * self::ROW_SPACING[$baseZone];

        $thetaDeg = self::ROTATION_DEGREES[$baseZone];
        $theta = deg2rad($thetaDeg);
        $x = self::ANCHOR[$baseZone]['x'] + $lx * cos($theta) - $ly * sin($theta);
        $y = self::ANCHOR[$baseZone]['y'] + $lx * sin($theta) + $ly * cos($theta);

        $rotation = $mirror ? -$thetaDeg : $thetaDeg;

        if ($mirror) {
            $x = self::VIEWBOX_WIDTH - $x;
        }

        return [
            'x' => round($x, 1), 
            'y' => round($y, 1),
            'rotation' => 0,
        ];
    }
}
