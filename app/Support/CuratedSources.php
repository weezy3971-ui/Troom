<?php

namespace App\Support;

use App\Models\InformationSource;

/**
 * Vetting layer between the curated planner programs (PlannerPrograms) and the
 * admin-managed source register (InformationSource).
 *
 * A planting plan may only stay printable while the publishers it cites are
 * still approved. When an admin removes a source, vet() strips that citation
 * from every plan, marks a plan unverified once any of its backing sources is
 * gone, and withdraws a plan entirely once nothing backs it.
 *
 * NOTE: this file was reconstructed on 2026-07-23 when the spec redesign was
 * rolled back — the original was an untracked working file that predated any
 * commit. It reproduces the documented behaviour the planner depends on.
 */
class CuratedSources
{
    /**
     * Vet a set of planner programs against the source register.
     *
     * @param  array<string, array<string, mixed>>  $programs
     * @return array<string, array<string, mixed>>  Surviving programs, keyed as given.
     */
    public static function vet(array $programs): array
    {
        $vetted = [];

        foreach ($programs as $slug => $program) {
            $sources = $program['sources'] ?? [];

            $kept = array_values(array_filter(
                $sources,
                fn ($s) => isset($s['url']) && InformationSource::allows($s['url'])
            ));

            // Nothing left to back the plan → withdraw it entirely.
            if ($sources && ! $kept) {
                continue;
            }

            // Some citations were dropped → the plan can no longer be shown as
            // verified, even though it survives.
            if (count($kept) < count($sources)) {
                $program['verified'] = false;
            }

            $program['sources'] = $kept;
            $vetted[$slug] = $program;
        }

        return $vetted;
    }

    /**
     * The vetted program for a crop, or null if none survives vetting.
     */
    public static function forCrop(?string $name): ?array
    {
        return PlannerPrograms::matchIn(self::vet(PlannerPrograms::all()), $name);
    }

    /**
     * Every citation across all curated programs, flattened and de-duplicated
     * by URL — the set of pages the planner draws on.
     *
     * @return array<int, array{label: string, url: string}>
     */
    public static function citations(): array
    {
        $byUrl = [];

        foreach (PlannerPrograms::all() as $program) {
            foreach ($program['sources'] ?? [] as $source) {
                if (isset($source['url'])) {
                    $byUrl[$source['url']] = $source;
                }
            }
        }

        return array_values($byUrl);
    }
}
