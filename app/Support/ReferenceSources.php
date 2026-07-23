<?php

namespace App\Support;

use App\Models\InformationSource;

/**
 * Keeps Administration → Sources in step with the guidance declared in code.
 *
 * The register lists one row per publisher. Sources are declared alongside the
 * material that cites them (currently the land-preparation checklist), so a
 * source can't be relied on without being listed and open to review. A source
 * an admin removed stays removed: sync never resurrects it.
 */
class ReferenceSources
{
    /**
     * Publisher metadata for the domains cited in code, so the register can
     * show a proper name and category rather than a bare hostname.
     *
     * @var array<string, array{name: string, category: string}>
     */
    public const KNOWN = [
        'cropnuts.com' => ['name' => 'Cropnuts', 'category' => 'soil_lab'],
        'greenlife.co.ke' => ['name' => 'Greenlife Crop Protection Africa', 'category' => 'agronomy'],
        'infonet-biovision.org' => ['name' => 'Infonet-Biovision', 'category' => 'agronomy'],
        'simlaw.co.ke' => ['name' => 'Simlaw Seeds', 'category' => 'seed_variety'],
        'kephis.go.ke' => ['name' => 'KEPHIS', 'category' => 'compliance'],
        'globalgap.org' => ['name' => 'GLOBALG.A.P.', 'category' => 'compliance'],
    ];

    /**
     * Every citation declared in code, grouped by the domain that published it.
     *
     * @return array<string, array<int, array{label: string, url: string}>>
     */
    public static function citations(): array
    {
        $byDomain = [];

        foreach (LandPrepProgram::sources() as $source) {
            $domain = InformationSource::domainFrom($source['url']);

            if ($domain === '') {
                continue;
            }

            $byDomain[$domain][] = $source;
        }

        return $byDomain;
    }

    /**
     * Add any newly cited publisher to the register and refresh the pages cited
     * from it. Existing rows keep their admin-set name, category and status —
     * only the citation list is rewritten, since that comes from the code.
     */
    public static function sync(): void
    {
        foreach (self::citations() as $domain => $references) {
            $known = self::KNOWN[$domain] ?? null;

            $source = InformationSource::firstOrNew(['domain' => $domain]);

            if (! $source->exists) {
                $source->fill([
                    'name' => $known['name'] ?? $domain,
                    'category' => $known['category'] ?? 'other',
                    'url' => 'https://' . $domain . '/',
                    'status' => 'active',
                    'purpose' => 'Land preparation guidance followed by the block preparation checklist.',
                ]);
            }

            $source->references = $references;
            $source->save();
        }
    }
}
