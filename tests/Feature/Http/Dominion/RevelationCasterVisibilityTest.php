<?php

namespace OpenDominion\Tests\Feature\Http\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Mappers\Dominion\InfoMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Spell;
use OpenDominion\Models\Wonder;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

class RevelationCasterVisibilityTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public static function opCenterPages(): array
    {
        return [
            'current spells in own advisors' => ['own', null],
            'shared advisors with perception on the viewer only' => ['shared', 'viewer_only'],
            'shared advisors with perception on the target' => ['shared', 'surreal_perception'],
            'saved Revelation on another realm' => ['revelation', null],
            'own advisors with Surreal Perception' => ['own', 'surreal_perception'],
            'own advisors with Astral Panopticon' => ['own', 'astral_panopticon'],
        ];
    }

    #[DataProvider('opCenterPages')]
    public function testCasterVisibilityInTableAndClipboardExports(string $report, ?string $perceptionSource): void
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound('-4 days midnight');
        $viewer = $this->createAndSelectDominionWithLegacyStats($user, $round);
        $otherRealm = app(RealmFactory::class)->create($round, 'good');
        $enemyCaster = $this->createDominion($this->createUser(), $round, realm: $otherRealm);
        $target = $viewer;
        if ($report === 'shared') {
            $target = $this->createDominion($this->createUser(), $round, realm: $viewer->realm, attributes: [
                'settings' => ['realmadvisors' => [$viewer->id => true]],
            ]);
        } elseif ($report === 'revelation') {
            $target = $this->createDominion($this->createUser(), $round, realm: app(RealmFactory::class)->create($round, 'good'));
        }
        $friendlyCaster = $this->createDominion($this->createUser(), $round, realm: $target->realm);

        $this->attachSpell($target, 'plague', $enemyCaster);
        $this->attachSpell($target, 'arcane_ward', $friendlyCaster);

        if ($perceptionSource === 'surreal_perception') {
            $this->attachSpell($target, 'surreal_perception', $target);
        } elseif ($perceptionSource === 'viewer_only') {
            $this->attachSpell($viewer, 'surreal_perception', $viewer);
        } elseif ($perceptionSource === 'astral_panopticon') {
            $wonder = Wonder::where('key', 'astral_panopticon')->firstOrFail();
            RoundWonder::create([
                'round_id' => $round->id,
                'realm_id' => $viewer->realm_id,
                'wonder_id' => $wonder->id,
                'power' => $wonder->power,
            ]);
        }

        if ($report === 'own') {
            $url = route('dominion.advisors.op-center');
        } elseif ($report === 'shared') {
            $url = route('dominion.realm.advisors.op-center', $target);
        } else {
            $snapshot = app(InfoMapper::class)->mapSpells($target);
            foreach ($snapshot as $i => $record) {
                if ($record['spell'] === 'plague') {
                    unset($snapshot[$i]['cast_by_dominion_name'], $snapshot[$i]['cast_by_dominion_realm_number']);
                    $snapshot[$i]['cast_by_dominion'] = ['private' => 'historical relation'];
                }
            }

            InfoOp::create([
                'source_realm_id' => $viewer->realm_id,
                'target_realm_id' => $target->realm_id,
                'source_dominion_id' => $viewer->id,
                'target_dominion_id' => $target->id,
                'type' => 'revelation',
                'latest' => true,
                'data' => $snapshot,
            ]);
            $url = route('dominion.op-center.show', $target);
        }

        $response = $this->get($url)->assertOk();
        $page = new Crawler($response->getContent());
        $plagueCasterCell = '//tr[td[1][normalize-space()="Plague"]]/td[last()]';
        $expectedCaster = $report === 'revelation' || in_array($perceptionSource, ['surreal_perception', 'astral_panopticon'], true) ? $enemyCaster : null;
        $casterLabel = $expectedCaster === null ? 'Unknown' : "{$expectedCaster->name} (#{$otherRealm->number})";
        $wardCaster = $page->filterXPath('//tr[td[1][normalize-space()="Arcane Ward"]]/td[last()]');
        $this->assertSame($casterLabel, $page->filterXPath($plagueCasterCell)->text());
        $this->assertSame("{$friendlyCaster->name} (#{$friendlyCaster->realm->number})", $wardCaster->text());

        $individual = json_decode($page->filter('#revelation')->text(), true, flags: JSON_THROW_ON_ERROR);
        $combined = json_decode($page->filter('#ops_json')->text(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($individual, $combined['revelation']);

        $spells = array_column($individual['spells'], null, 'spell');
        $this->assertArrayNotHasKey('cast_by_dominion', $spells['plague']);
        $this->assertSame(8, $spells['plague']['duration']);
        $this->assertSame($expectedCaster?->id, $spells['plague']['cast_by_dominion_id']);
        $this->assertSame($expectedCaster?->name, $spells['plague']['cast_by_dominion_name']);
        $this->assertSame($expectedCaster?->realm->number, $spells['plague']['cast_by_dominion_realm_number']);
        $this->assertSame($friendlyCaster->id, $spells['arcane_ward']['cast_by_dominion_id']);
        $this->assertSame($friendlyCaster->name, $spells['arcane_ward']['cast_by_dominion_name']);
        $this->assertSame($friendlyCaster->realm->number, $spells['arcane_ward']['cast_by_dominion_realm_number']);

        if ($report !== 'revelation') {
            $magicUrl = $report === 'own' ? route('dominion.magic') : route('dominion.realm.advisors.magic', $target);
            $magic = $this->get($magicUrl)->assertOk();
            $magicPage = new Crawler($magic->getContent());
            $this->assertSame($casterLabel, $magicPage->filterXPath($plagueCasterCell)->text());
        } else {
            $archive = $this->get(route('dominion.op-center.archive', [$target, 'revelation']))->assertOk();
            $this->assertSame($casterLabel, (new Crawler($archive->getContent()))->filterXPath($plagueCasterCell)->text());
        }
    }

    private function attachSpell(Dominion $target, string $key, Dominion $caster): void
    {
        $target->spells()->attach(Spell::where('key', $key)->firstOrFail()->id, [
            'duration' => 8,
            'cast_by_dominion_id' => $caster->id,
        ]);
    }
}
