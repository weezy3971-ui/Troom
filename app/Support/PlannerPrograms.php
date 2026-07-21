<?php

namespace App\Support;

/**
 * Curated planting-planner programs, one per crop.
 *
 * Each program drives the planner worksheet: the readiness checklist, the dated
 * phase schedule (offsets from Day 0), the agronomy notes and the sources.
 *
 * `day_zero`  — what Day 0 means for the crop: crops raised in a nursery are
 *               dated from TRANSPLANT, direct-sown crops from SOWING.
 * `verified`  — true only once the figures have been checked against the named
 *               Kenyan agronomy sources listed in `sources`. Unverified programs
 *               render a "confirm with your agronomist" banner, because wrong
 *               rates or spacings cost a farmer real money.
 *
 * Rows: [label, task (HTML), startOffsetDays, endOffsetDays|null, rowClass]
 */
class PlannerPrograms
{
    public static function all(): array
    {
        return [
            'french-bean' => [
                'crop' => 'French Bean',
                'title' => 'French Bean Planting Planner',
                'day_zero' => 'sowing',
                'verified' => true,
                'variety_placeholder' => 'e.g. Serengeti (extra-fine)',
                'checklist' => [
                    ['text' => 'Land ploughed &amp; harrowed to a fine tilth; drip lines laid'],
                    ['text' => 'Certified seed in hand (~30 kg/acre)'],
                    ['text' => 'DAP basal fertiliser ready (~80 kg/acre)'],
                    ['text' => '<span class="must">Irrigation working</span> — the dry season has no rain to save the crop'],
                    ['text' => 'Well-rotted manure / compost worked in'],
                    ['text' => 'Soil test done (target pH 6.5–7.5)'],
                    ['text' => 'Exporter contract &amp; compliance (GLOBALG.A.P / KEPHIS) in motion'],
                ],
                'rows' => [
                    ['Plant', 'Sow at <b>30&times;15 cm</b>, DAP in the furrow (off the seed), irrigate in.', 0, null, 'is-plant'],
                    ['Germination &amp; emergence', 'Emergence in 5&ndash;8 days. Keep soil damp. <b>Scout bean fly</b> from day one.', 3, 8, ''],
                    ['Gap-fill', 'Replant blanks to keep a uniform plant stand.', 9, null, ''],
                    ['1st top-dress (CAN)', 'At 2&ndash;3 leaf stage: CAN ~60 kg/acre (split). <b>Weed now.</b>', 14, 18, ''],
                    ['Vegetative growth', 'Steady water ~50 mm/week. Scout aphids &amp; whitefly.', 8, 30, ''],
                    ['Flowering', '<b>2nd top-dress (CAN)</b> as flowers open. Even moisture + <b>thrips control</b> are critical.', 30, 40, ''],
                    ['Pod fill', 'Finish sprays &mdash; respect pre-harvest intervals. Watch rust / anthracnose.', 40, 48, ''],
                    ['First harvest', 'First pick around day 45&ndash;50. Pick young, straight, string-free pods.', 45, 50, 'is-harvest'],
                    ['Harvest window', 'Rolling ~3 weeks. Fine: pick 2&times;/week &middot; extra-fine: 3&times;/week. Early-morning picks.', 45, 70, 'is-harvest'],
                    ['Close-out &amp; rotation', 'Uproot, sanitise residues, rotate to a <b>non-legume</b> next.', 75, 90, ''],
                ],
                'notes' => [
                    ['type' => 'warning', 'title' => 'WEATHER', 'body' => 'In the cool, cloudy Kenyan dry season (roughly Jun–Aug) growth can run a few days slower, so treat the first-harvest date as the earliest, not the guaranteed, start.'],
                    ['type' => 'error', 'title' => 'TWO DATES DECIDE THE CROP', 'body' => 'During <b>flowering</b>, thrips scar pods (→ export rejection) and moisture must stay even. And stop residual sprays early enough that the <b>pre-harvest interval clears before the first pick</b>.'],
                ],
                'footer' => "Typical ranges for Kenyan export French beans; they vary by variety, altitude and buyer spec. Defer to your exporter's protocol, current KEPHIS/EU rules and a local soil test.",
                'sources' => [
                    ['label' => 'Cropnuts: French Beans Farming Guide', 'url' => 'https://cropnuts.com/'],
                    ['label' => 'Greenlife Kenya: French Beans', 'url' => 'https://www.greenlife.co.ke/french-beans/'],
                    ['label' => 'Simlaw Seeds: Bean Production Guide (spacing, seed rate, DAP/CAN)', 'url' => 'https://simlaw.co.ke/'],
                    ['label' => 'Infonet-Biovision: Beans (Phaseolus vulgaris)', 'url' => 'https://infonet-biovision.org/crops-fruits-vegetables/beans'],
                    ['label' => 'KEPHIS / Infonet-Biovision: export & residue (MRL) requirements', 'url' => 'https://infonet-biovision.org/'],
                ],
            ],

            'capsicum' => [
                'crop' => 'Capsicum',
                'title' => 'Capsicum Planting Planner',
                'day_zero' => 'transplant',
                'verified' => true,
                'variety_placeholder' => 'e.g. California Wonder',
                'checklist' => [
                    ['text' => 'Nursery seedlings ready &mdash; <b>6&ndash;7 weeks</b> from sowing, hardened off'],
                    ['text' => 'Land ploughed to a fine tilth; drip lines laid'],
                    ['text' => 'DAP basal fertiliser ready (~50 kg/acre)'],
                    ['text' => 'Well-rotted manure / compost worked in (~1 t/acre)'],
                    ['text' => '<span class="must">Irrigation working</span> &mdash; capsicum runs for months and will not carry a water gap'],
                    ['text' => 'Soil test done (target pH 5.5&ndash;6.5)'],
                    ['text' => 'Staking / support material ready for the fruit load'],
                    ['text' => 'Market or buyer identified before the harvest window opens'],
                ],
                'rows' => [
                    ['Transplant', 'Transplant hardened seedlings at <b>75&times;45 cm</b>. Basal <b>DAP ~50 kg/acre</b> (off the roots), water in immediately.', 0, null, 'is-plant'],
                    ['Establishment', 'Keep soil consistently moist while roots take. Scout <b>cutworms</b> at the base.', 0, 14, ''],
                    ['Gap-fill', 'Replace dead seedlings to hold a uniform stand.', 7, 10, ''],
                    ['1st top-dress (CAN)', 'CAN ~<b>50 kg/acre</b> at 2&ndash;3 weeks. <b>Weed now</b> &mdash; capsicum competes poorly.', 14, 21, ''],
                    ['2nd top-dress (CAN)', 'CAN ~<b>100 kg/acre</b> at 4&ndash;5 weeks to drive vegetative growth. Stake as plants gain height.', 28, 35, ''],
                    ['Vegetative growth', 'Steady water. Scout <b>thrips</b>, aphids, whitefly &amp; red spider mite.', 14, 45, ''],
                    ['Flowering', '<b>NPK ~50 kg/acre</b> at the reproductive stage. Fruit sets best at <b>16&ndash;21&nbsp;&deg;C</b>; even moisture is critical &mdash; stress drops flowers.', 45, 60, ''],
                    ['Fruit set &amp; fill', 'Watch <b>blossom-end rot</b> (calcium + steady water) and anthracnose. Respect pre-harvest intervals.', 60, 80, ''],
                    ['First harvest', 'First pick <b>2.5&ndash;3 months</b> after transplant, once fruit is firm and full-sized.', 75, 90, 'is-harvest'],
                    ['Harvest window', 'Expect <b>4&ndash;6 pickings</b> with good management. Cut, don&rsquo;t pull, to protect the plant.', 75, 150, 'is-harvest'],
                    ['Close-out &amp; rotation', 'Uproot, sanitise residues, rotate away from <b>solanaceae</b> (tomato, pepper, potato).', 150, 180, ''],
                ],
                'notes' => [
                    ['type' => 'warning', 'title' => 'DAY 0 IS TRANSPLANT', 'body' => 'Capsicum is nursery-raised for <b>6&ndash;7 weeks</b> before transplant. Count back from the transplant date above to fix your nursery sowing date.'],
                    ['type' => 'error', 'title' => 'WATER STEADINESS DECIDES THE CROP', 'body' => 'Capsicum runs for months. Irregular water at <b>flowering</b> drops flowers, and swings during <b>fruit fill</b> drive blossom-end rot. Consistency beats volume.'],
                ],
                'footer' => 'Figures follow Greenlife Kenya: 75×45 cm spacing, DAP ~50 kg/acre basal, CAN ~50 kg/acre at 2–3 weeks then ~100 kg/acre at 4–5 weeks, NPK ~50 kg/acre at flowering, first pick 2.5–3 months after transplant. Soil pH 5.5–6.5, altitude up to ~2,000 m, 800–1,200 mm rainfall/yr. Rates still vary with variety, soil test and open-field vs greenhouse — confirm with your agronomist.',
                'sources' => [
                    ['label' => 'Greenlife Kenya: The A–Z of Capsicum Farming (spacing, DAP/CAN/NPK rates, pH, altitude, temperature, harvest)', 'url' => 'https://www.greenlife.co.ke/the-a-z-of-capsicum-farming/'],
                    ['label' => 'Greenlife Kenya: Fertilizer Recommendations for Optimum Crop Production', 'url' => 'https://www.greenlife.co.ke/fertilizer-recommendations-for-optimum-crop-production/'],
                ],
            ],

            'collard-greens' => [
                'crop' => 'Collard Greens',
                'title' => 'Collard Greens (Sukuma Wiki) Planting Planner',
                'day_zero' => 'transplant',
                'verified' => true,
                'variety_placeholder' => 'e.g. Thousand Headed',
                'checklist' => [
                    ['text' => 'Nursery seedlings ready &mdash; <b>4&ndash;6 weeks</b>, at 3&ndash;4 true leaves'],
                    ['text' => 'Land ploughed to a fine tilth'],
                    ['text' => 'Well-rotted manure / compost worked in &mdash; this crop is a heavy feeder'],
                    ['text' => 'DAP / TSP basal fertiliser ready (~50 kg/acre)'],
                    ['text' => 'CAN ready for top-dressing at 2 and 4 weeks (~50 kg/acre each)'],
                    ['text' => '<span class="must">Reliable water</span> &mdash; leaf quality collapses under water stress'],
                    ['text' => 'Soil test done (target pH 5.5&ndash;7.5)'],
                    ['text' => 'Steady market lined up &mdash; harvest is continuous, not one-off'],
                ],
                'rows' => [
                    ['Transplant', 'Transplant at <b>60&times;45 cm</b> (medium varieties; 60&times;60 for large, 30&times;30 for small). Basal <b>DAP/TSP ~50 kg/acre</b>, water in.', 0, null, 'is-plant'],
                    ['Establishment', 'Keep soil moist while roots take. Transplant early morning, evening or on a cloudy day. Scout <b>cutworms</b>.', 0, 10, ''],
                    ['Gap-fill', 'Replace losses to hold a uniform stand.', 7, 10, ''],
                    ['1st top-dress (CAN)', 'CAN ~<b>50 kg/acre</b> at <b>2 weeks</b> after transplant. <b>Weed now.</b>', 14, null, ''],
                    ['Vegetative growth', 'Steady water. Scout <b>diamondback moth</b>, aphids, sawfly &amp; whitefly.', 10, 28, ''],
                    ['2nd top-dress (CAN)', 'CAN ~<b>50 kg/acre</b> at <b>4 weeks</b> after transplant.', 28, null, ''],
                    ['First harvest', 'First picking from about <b>4 weeks</b> after transplant, once lower leaves are full-sized.', 28, 42, 'is-harvest'],
                    ['Harvest window', 'Pick <b>outer/lower leaves</b> by hand and let the growing point run. Never strip the plant bare.', 28, 150, 'is-harvest'],
                    ['Top-dress after picking', 'Keep feeding to sustain leaf flush. Respect pre-harvest intervals &mdash; leaves are eaten fresh.', 45, 150, ''],
                    ['Close-out &amp; rotation', 'Uproot when leaf size and vigour drop off. Rotate away from <b>brassicas</b>.', 150, 180, ''],
                ],
                'notes' => [
                    ['type' => 'warning', 'title' => 'DAY 0 IS TRANSPLANT', 'body' => 'Sukuma wiki is nursery-raised for <b>4&ndash;6 weeks</b> (to 3&ndash;4 true leaves) before transplant. Count back from the transplant date above to fix your nursery sowing date.'],
                    ['type' => 'error', 'title' => 'THIS IS A ROLLING HARVEST', 'body' => 'There is no single harvest window &mdash; you pick lower leaves for months. Because leaves are eaten fresh and picked continuously, <b>spray pre-harvest intervals are the hard constraint</b> on which chemicals you can use at all.'],
                ],
                'footer' => 'Figures follow Greenlife Kenya: nursery 4–6 weeks, 60×45 cm (medium), DAP/TSP ~50 kg/acre basal, CAN ~50 kg/acre at 2 weeks and again at 4 weeks, first harvest from ~4 weeks after transplant. Soil pH 5.5–7.5, altitude 800–2,200 m, optimum 16–21 °C. Confirm rates against a local soil test with your agronomist.',
                'sources' => [
                    ['label' => 'Greenlife Kenya: Kale Production (nursery, spacing, DAP/CAN rates, pH, altitude, temperature, harvest)', 'url' => 'https://www.greenlife.co.ke/kale-production/'],
                    ['label' => 'Infonet-Biovision: Kale / brassica pests & diseases', 'url' => 'https://infonet-biovision.org/crops-fruits-vegetables/kale'],
                ],
            ],

            'spinach' => [
                'crop' => 'Spinach',
                'title' => 'Spinach Planting Planner',
                'day_zero' => 'transplant',
                'verified' => true,
                'variety_placeholder' => 'e.g. Fordhook Giant',
                'checklist' => [
                    ['text' => 'Nursery seedlings ready &mdash; <b>4&ndash;5 weeks</b>, at 3&ndash;4 leaves'],
                    ['text' => 'Land ploughed to a fine tilth; well-drained loam'],
                    ['text' => 'Well-rotted manure / compost worked in'],
                    ['text' => 'DAP basal fertiliser ready; CAN for top-dressing'],
                    ['text' => '<span class="must">Reliable water</span> &mdash; spinach needs high, even moisture or it bolts'],
                    ['text' => 'Soil test done (target pH 6.0&ndash;7.5)'],
                    ['text' => 'Steady market lined up &mdash; harvest is continuous'],
                ],
                'rows' => [
                    ['Transplant', 'Transplant at <b>30&ndash;38 cm between rows &times; ~13 cm between plants</b>, at the same depth as in the nursery. Basal <b>DAP</b>, water in.', 0, null, 'is-plant'],
                    ['Establishment', 'Keep soil consistently moist while roots take.', 0, 10, ''],
                    ['Gap-fill', 'Replace losses to hold a uniform stand.', 7, 10, ''],
                    ['1st top-dress (CAN)', 'Top-dress with <b>CAN</b> at the vegetative stage &mdash; spinach needs high nitrogen. <b>Weed now.</b>', 14, 21, ''],
                    ['Vegetative growth', 'Steady, even water. Scout aphids &amp; <b>leaf miner</b>; watch <b>downy mildew</b> and white rust in cool damp spells.', 10, 35, ''],
                    ['First harvest', 'First cut about <b>5 weeks</b> after transplant (~40 days), once leaves are full-sized.', 35, 45, 'is-harvest'],
                    ['Harvest window', 'Cut <b>outer leaves</b> with a knife and let the centre keep producing; allow <b>3&ndash;4 weeks regrowth</b> between cuts. Or lift whole plants at 8&ndash;10 leaves and sell in bundles.', 35, 120, 'is-harvest'],
                    ['Top-dress after cutting', 'Feed after each cut to sustain leaf flush. Respect pre-harvest intervals &mdash; leaves are eaten fresh.', 45, 120, ''],
                    ['Close-out &amp; rotation', 'Uproot as vigour drops or plants bolt. Rotate to a different family.', 120, 150, ''],
                ],
                'notes' => [
                    ['type' => 'warning', 'title' => 'WHICH “SPINACH”?', 'body' => 'In Kenya “spinach” usually means <b>Swiss chard</b> (Fordhook Giant), which is heat-tolerant and cuts for months. <b>True spinach</b> (Viroflay, King of Denmark) is a cool-season crop &mdash; it wants ~15&ndash;20&nbsp;&deg;C, suits altitudes above ~2,000&nbsp;m and is retarded above 27&nbsp;&deg;C. Check which one your seed actually is before trusting these dates.'],
                    ['type' => 'error', 'title' => 'HEAT AND STRESS CAUSE BOLTING', 'body' => 'Water stress and heat push spinach to <b>bolt</b> (run to seed), which ends leaf quality. Even moisture and timely cutting keep the plant productive.'],
                ],
                'footer' => 'Figures follow Greenlife Kenya and Infonet-Biovision: nursery 4–5 weeks, rows 30–38 cm × plants ~13 cm, DAP basal with CAN top-dressing, first cut ~5 weeks (~40 days) after transplant, 3–4 weeks regrowth between cuts. Soil pH ~6.0–7.5. Confirm rates against a local soil test with your agronomist.',
                'sources' => [
                    ['label' => 'Greenlife Kenya: Spinach Farming (nursery, spacing, pH, regrowth, pests)', 'url' => 'https://www.greenlife.co.ke/spinach-farming/'],
                    ['label' => 'Infonet-Biovision: Spinach (pH, temperature, altitude, harvest method)', 'url' => 'https://infonet-biovision.org/crops-fruits-vegetables/spinach'],
                ],
            ],

            'african-nightshade' => [
                'crop' => 'African Nightshade',
                'title' => 'African Nightshade (Managu) Planting Planner',
                'day_zero' => 'transplant',
                'verified' => true,
                'variety_placeholder' => 'e.g. Managu',
                'checklist' => [
                    ['text' => 'Nursery seedlings ready &mdash; transplant at <b>6 true leaves, 10&ndash;15 cm tall</b>'],
                    ['text' => 'Seed mixed with sand or ash at <b>1:3</b> for even sowing &mdash; the seed is very fine'],
                    ['text' => 'Land ploughed deeply to a fine tilth'],
                    ['text' => 'Manure / compost ready (<b>2&ndash;5 kg/m&sup2;</b>) &mdash; managu does best on organic-rich plots'],
                    ['text' => '<span class="must">Reliable water</span> &mdash; managu does not tolerate drought'],
                    ['text' => 'Steady market lined up &mdash; harvest is continuous'],
                ],
                'rows' => [
                    ['Transplant', 'Transplant at <b>40 cm between rows &times; 20 cm in the row</b> once seedlings have 6 true leaves. Work in <b>manure 2&ndash;5 kg/m&sup2;</b>, water in.', 0, null, 'is-plant'],
                    ['Establishment', 'Keep soil moist while roots take &mdash; managu does not tolerate drought. Handle seedlings gently.', 0, 10, ''],
                    ['Gap-fill', 'Replace losses to hold a uniform stand.', 7, 10, ''],
                    ['1st top-dress', 'Feed for nitrogen once established. <b>Weed now</b> &mdash; small plants are easily smothered.', 14, 21, ''],
                    ['Vegetative growth', 'Frequent irrigation for good leaf yield. Scout <b>aphids</b> &amp; <b>spider mites</b>; watch early blight and bacterial wilt.', 10, 28, ''],
                    ['First harvest', 'First picking about <b>4 weeks</b> from transplant &mdash; pinch out tender shoots and leaves.', 28, 35, 'is-harvest'],
                    ['Harvest window', 'Picking at <b>weekly intervals</b>. Pinching tips makes the plant branch and yield more leaf.', 28, 120, 'is-harvest'],
                    ['Top-dress after picking', 'Keep feeding to sustain flush. Respect pre-harvest intervals &mdash; leaves are eaten fresh.', 45, 120, ''],
                    ['Close-out &amp; rotation', 'Uproot as vigour drops. Rotate away from <b>solanaceae</b> (tomato, pepper, potato) &mdash; shares bacterial wilt and nematodes.', 120, 150, ''],
                ],
                'notes' => [
                    ['type' => 'warning', 'title' => 'DAY 0 IS TRANSPLANT', 'body' => 'Managu is nursery-raised and transplanted at <b>6 true leaves / 10&ndash;15 cm</b> (roughly 3&ndash;4 weeks). Count back from the transplant date above to fix your nursery sowing date. For a single complete harvest instead of rolling picks, it can be grown dense at <b>10&times;10 cm</b>.'],
                    ['type' => 'error', 'title' => 'PINCHING DRIVES YIELD', 'body' => 'Harvesting by <b>pinching the growing tips</b> makes the plant branch and produce more leaf. Letting it run to flower and seed early cuts leaf yield short.'],
                ],
                'footer' => 'Figures follow Infonet-Biovision: transplant at 6 true leaves / 10–15 cm, spacing 40 cm × 20 cm (or 10×10 cm for a single complete harvest), manure 2–5 kg/m², first picking ~4 weeks from transplant, weekly picking thereafter. Practice varies widely by locality — confirm with your agronomist.',
                'sources' => [
                    ['label' => 'Infonet-Biovision: African Nightshade (spacing, manure rate, transplant stage, harvest interval, pests)', 'url' => 'https://infonet-biovision.org/indigenous-plants/african-nightshade-revised'],
                ],
            ],
        ];
    }

    /** Slug => crop display name, for the planner's crop picker. */
    public static function options(): array
    {
        return collect(self::all())->map(fn ($p) => $p['crop'])->all();
    }

    /**
     * Find the curated program for a crop by its display name (case-insensitive),
     * matching either the slug key or the program's `crop` label. Lets a crop
     * cycle build a sensible default stage schedule with zero program setup.
     */
    public static function forCrop(?string $name): ?array
    {
        if (! $name) {
            return null;
        }

        $needle = strtolower(trim($name));
        $slug = \Illuminate\Support\Str::slug($needle);

        foreach (self::all() as $key => $program) {
            if ($key === $slug || strtolower(trim($program['crop'] ?? '')) === $needle) {
                return $program;
            }
        }

        return null;
    }
}
