<?php

namespace App\Support;

/**
 * The generic land-preparation checklist a new block starts with.
 *
 * Deliberately crop-agnostic: preparation happens before anyone has decided
 * what goes in, so this is the common ground every Kenyan horticulture block
 * covers. Tasks are copied onto the preparation round when it is created, so
 * editing this list never rewrites work already recorded in the field.
 */
class LandPrepProgram
{
    /**
     * @return array<int, array{name: string, description: string}>
     */
    public static function tasks(): array
    {
        return [
            [
                'name' => 'Clear the land',
                'description' => 'Remove previous crop residues, weeds, stumps and stones. Burn or compost residues away from the block to break pest and disease carry-over.',
            ],
            [
                'name' => 'Soil sampling & test',
                'description' => 'Take samples across the block (zig-zag, 0–30 cm) and send for testing. The result sets lime, manure and fertiliser rates — everything after this is guesswork without it.',
            ],
            [
                'name' => 'Primary tillage (ploughing)',
                'description' => 'Plough to 20–30 cm to open the soil and bury residues. Do it while the soil is moist but not wet, to avoid compaction and clodding.',
            ],
            [
                'name' => 'Apply lime or gypsum (if the test calls for it)',
                'description' => 'Correct soil pH ahead of planting. Lime needs time and moisture to work, so apply well before the planting date, not with the basal fertiliser.',
            ],
            [
                'name' => 'Incorporate manure / compost',
                'description' => 'Spread well-rotted manure or compost and work it in. Fresh manure scorches roots and carries weed seed — it must be properly decomposed.',
            ],
            [
                'name' => 'Secondary tillage (harrowing)',
                'description' => 'Harrow to a fine tilth so seed and seedlings sit in even contact with the soil. Break clods before, not after, the beds are shaped.',
            ],
            [
                'name' => 'Level & shape beds or furrows',
                'description' => 'Level the block and form beds, ridges or furrows to the spacing the intended crop needs. Grade so water runs off rather than sitting.',
            ],
            [
                'name' => 'Lay out irrigation',
                'description' => 'Install or re-lay drip lines or sprinklers and test-run them. Flush lines and check every emitter before planting, not after.',
            ],
            [
                'name' => 'Drainage & erosion control',
                'description' => 'Open cut-off drains and check waterways on sloping ground. Waterlogging after planting is expensive to fix and easy to prevent now.',
            ],
            [
                'name' => 'Final field hygiene check',
                'description' => 'Confirm the block is clean, beds are firm, water reaches the far end, and access paths are usable. Record the date the block was ready to plant.',
            ],
        ];
    }

    /**
     * Published guidance the checklist follows. Declared here so land-prep
     * sources appear in Administration → Sources alongside the crop programs.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function sources(): array
    {
        return [
            ['label' => 'Cropnuts: soil sampling & testing before planting', 'url' => 'https://cropnuts.com/'],
            ['label' => 'Greenlife Kenya: land preparation & seedbed practice', 'url' => 'https://www.greenlife.co.ke/'],
            ['label' => 'Infonet-Biovision: soil fertility management, manure & erosion control', 'url' => 'https://infonet-biovision.org/'],
        ];
    }
}
