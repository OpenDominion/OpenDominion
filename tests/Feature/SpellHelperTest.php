<?php

namespace OpenDominion\Tests\Feature;

use Cache;
use OpenDominion\Helpers\SpellHelper;
use OpenDominion\Models\Spell;
use OpenDominion\Tests\AbstractTestCase;

class SpellHelperTest extends AbstractTestCase
{
    protected SpellHelper $spellHelper;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget('game:spells');
        $this->spellHelper = app(SpellHelper::class);
    }

    protected function tearDown(): void
    {
        Cache::forget('game:spells');

        parent::tearDown();
    }

    public function testGetSpellByKeyReturnsActiveSpell(): void
    {
        $spell = Spell::active()->firstOrFail();

        $this->assertSame($spell->name, $this->spellHelper->getSpellByKey($spell->key)?->name);
    }

    public function testGetSpellByKeyReturnsInactiveSpell(): void
    {
        $spell = Spell::active()->firstOrFail();
        $spell->update(['active' => false]);

        $this->assertSame($spell->name, $this->spellHelper->getSpellByKey($spell->key)?->name);
    }

    public function testGetSpellByKeyReturnsNullForUnknownKey(): void
    {
        $this->assertNull($this->spellHelper->getSpellByKey('not_a_real_spell'));
    }

    public function testGetSpellsExcludesInactiveSpells(): void
    {
        $spell = Spell::active()->firstOrFail();
        $spell->update(['active' => false]);

        $this->assertFalse($this->spellHelper->getSpells()->has($spell->key));
        $this->assertSame(Spell::active()->count(), $this->spellHelper->getSpells()->count());
    }
}
