<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Models\HeroUpgrade;
use OpenDominion\Tests\AbstractTestCase;
use Symfony\Component\Yaml\Yaml;

class HeroUpgradeSyncTest extends AbstractTestCase
{
    /**
     * @return array<string, array<string, mixed>>
     */
    protected function getHeroUpgradeData(): array
    {
        return Yaml::parse(file_get_contents(base_path('app/data/heroes.yml')));
    }

    public function testEveryMagicUpgradeIsNamedAfterItsSpellbook(): void
    {
        foreach ($this->getHeroUpgradeData() as $upgradeKey => $upgradeData) {
            if ($upgradeData['type'] !== 'magic') {
                continue;
            }

            $this->assertSame(
                ucfirst($upgradeKey) . ' Spellbook',
                $upgradeData['name'],
                "Magic upgrade {$upgradeKey} is not named after its spellbook"
            );
        }
    }

    /**
     * Info ops snapshot upgrade names into their JSON payload, so a rename must
     * update the existing row rather than orphan it behind a new one.
     */
    public function testDataSyncRenamesUpgradesInPlaceWithoutChangingKeysOrIds(): void
    {
        $this->artisan('game:data:sync')->assertExitCode(0);

        $before = HeroUpgrade::all()->keyBy('key');
        $this->assertNotEmpty($before);

        $upgrade = $before->get('evocation');
        $this->assertNotNull($upgrade);
        $upgrade->name = 'Stale Name';
        $upgrade->save();

        $this->artisan('game:data:sync')->assertExitCode(0);

        $after = HeroUpgrade::all()->keyBy('key');

        $this->assertSame($before->count(), $after->count(), 'Sync created or removed hero upgrades');
        $this->assertSame($upgrade->id, $after->get('evocation')->id, 'Sync replaced the row instead of updating it');
        $this->assertSame('Evocation Spellbook', $after->get('evocation')->name);
    }

    public function testDataSyncPersistsEveryUpgradeNameFromYaml(): void
    {
        $this->artisan('game:data:sync')->assertExitCode(0);

        $upgrades = HeroUpgrade::all()->keyBy('key');

        foreach ($this->getHeroUpgradeData() as $upgradeKey => $upgradeData) {
            $this->assertNotNull($upgrades->get($upgradeKey), "Hero upgrade {$upgradeKey} was not synced");
            $this->assertSame($upgradeData['name'], $upgrades->get($upgradeKey)->name);
        }
    }
}
