<?php

namespace OpenDominion\Services\Scripting;

class LogParserService
{
    const simtodomspells = array(
        "Gaia's Watch"        => 'gaias_watch',
        "Mining Strength"    => 'mining_strength',
        "Ares Call"            => 'ares_call',
        "Midas Touch"        => 'midas_touch',
        "Harmony"            => 'harmony',
        "Racial Spell"        => 'af' // TODO: Fix racial spells
    );
    
    const simtodombanktypes = array(
        'platinum'     => 'resource_platinum',
        'gems'         => 'resource_gems',
        'ore'         => 'resource_ore',
        'lumber'     => 'resource_lumber',
    );
    
    const simtodomlandtypes = array(
        'Plains'        => 'plain',
        'Forest'        => 'forest',
        'Mountains'        => 'mountain',
        'Hills'            => 'hill',
        'Swamps'        => 'swamp',
        'Caverns'        => 'cavern',
        'Water'            => 'water',
    );

    const simtodombuildings = array(
        'Homes'             => 'home',
        'Alchemies'         => 'alchemy',
        'Farms'             => 'farm',
        'Smithies'             => 'smithy',
        'Masonries'         => 'masonry',
        'Ore Mines'         => 'ore_mine',
        'Gryphon Nests'     => 'gryphon_nest',
        'Towers'             => 'tower',
        'Wizard Guilds'     => 'wizard_guild',
        'Temples'             => 'temple',
        'Diamond Mines'     => 'diamond_mine',
        'Schools'             => 'school',
        'Lumber Yards'         => 'lumberyard',
        'Forest Havens'     => 'forest_haven',
        'Factories'         => 'factory',
        'Guard Towers'         => 'guard_tower',
        'Shrines'             => 'shrine',
        'Barracks'             => 'barrack',
        'Docks'             => 'dock',
    );
    
    const unitstodomparamsmap = array(
    
        /* Human */
        'Spearman'            => 'unit1',
        'Archer'            => 'unit2',
        'Knight'            => 'unit3',
        'Cavalry'            => 'unit4',
    
        /* Nomad */
        'Fighter'            => 'unit1',
        'Crossbowman'        => 'unit2',
        'Blademaster'        => 'unit3',
        'Valkyrie'            => 'unit4',
    
        /* Wood Elf */
        'Soldier'            => 'unit1',
        'Miner'                => 'unit2',
        'Cleric'            => 'unit3',
        'Warrior'            => 'unit4',
    
        /* Halfling */
        'Slinger'            => 'unit1',
        'Defender'            => 'unit2',
        'Staff Master'        => 'unit3',
        'Master Thief'        => 'unit4',
    
        /* Gnome */
        'Suicide Squad'        => 'unit1',
        'Tinker'            => 'unit2',
        'Rockapult'            => 'unit3',
        'Juggernaut'        => 'unit4',
    
        /* Merfolk */
        'Mermen'            => 'unit1',
        'Sirens'            => 'unit2',
        'Kraken'            => 'unit3',
        'Leviathan'            => 'unit4',
    
        /* Sylvan */
        'Satyr'                => 'unit1',
        'Sprite'            => 'unit2',
        'Dryad'                => 'unit3',
        'Centaur'            => 'unit4',
    
        /* Goblin */
        'Raider'            => 'unit1',
        'Shaman'            => 'unit2',
        'Hobgoblin'            => 'unit3',
        'Wolf Rider'        => 'unit4',
    
        /* Troll */
        'Brute'                => 'unit1',
        'Ogre'                => 'unit2',
        'Basher'            => 'unit3',
        'Smasher'            => 'unit4',
    
        /* Dark Elf */
        'Swordsman'            => 'unit1',
        'Gargoyle'            => 'unit2',
        'Adept'                => 'unit3',
        'Spirit Warrior'     => 'unit4',
    
        /* Undead */
        'Skeleton'            => 'unit1',
        'Ghoul'                => 'unit2',
        'Progeny'            => 'unit3',
        'Vampire'            => 'unit4',
    
        /* Spirit */
        'Phantom'            => 'unit1',
        'Banshee'            => 'unit2',
        'Ghost'                => 'unit3',
        'Spectre'            => 'unit4',
        
        /* Lycanthrope */
        'Scavenger'            => 'unit1',
        'Ratman'            => 'unit2',
        'Werewolf'            => 'unit3',
        'Garou'                => 'unit4',
    
        /* Kobold */
        'Grunt'                => 'unit1',
        'Underling'            => 'unit2',
        'Beast'                => 'unit3',
        'Overlord'            => 'unit4',
    
        /* Lizardfolk */
        'Reptile'            => 'unit1',
        'Serpent'            => 'unit2',
        'Chameleon'            => 'unit3',
        'Lizardman'            => 'unit4',
    
        /* Icekin */
        'Ice Beast'            => 'unit1',
        'Snow Witch'        => 'unit2',
        'Frost Mage'        => 'unit3',
        'Ice Elemental'        => 'unit4',
    
        /* Firewalker */
        'Fire Spirit'        => 'unit1',
        'Flamewolf'            => 'unit2',
        'Phoenix'            => 'unit3',
        'Salamander'        => 'unit4',
        
        /* Orc */
        'Savage'            => 'unit1',
        'Guard'                => 'unit2',
        'Voodoo Magi'        => 'unit3',
        'Bone Breaker'        => 'unit4',
    
        /* The Nox */
        'Imp'                => 'unit1',
        'Fiend'                => 'unit2',
        'Nightshade'        => 'unit3',
        'Lich'                => 'unit4',
        
        /* Generic */
        'Spies'                => 'spies',
        'Wizards'            => 'wizards',
        'Archmages'            => 'archmages'
    );

    public function parselogfile($data): array
    {
        // TODO: Add support for other date format
        preg_match_all('/=+ Protection Hour: (\d+)  \( Local Time: (\d{1,2}:\d{1,2}:\d{1,2} \d{4}-\d{1,2}\-\d{1,2}).*\)  \( Domtime: (\d{1,2}:\d{1,2}:\d{2} \d{4}-\d{1,2}\-\d{1,2}).*\) =+/im', $data, $results);
        
        $returnResults = array();

        foreach ($results[0] as $k => $string) {
            $returnResults[$results[1][$k]] = array();

            $nextpos = isset($results[0][$k+1]) ? strpos($data, $results[0][$k+1]) : strlen($data)-1;
            $hourlog = trim(substr($data, $begin=strpos($data, $string)+strlen($string), $nextpos-$begin));
            $types = array('release', 'daily', 'bank', 'destruction', 'rezone', 'construction', 'explore', 'magic', 'train');
            foreach ($types as $type) {
                $func = "parse_{$type}";
                if ($result = $this->$func($hourlog)) {
                    // $this->saveAction($type, $result, $results[1][$k]);
                    foreach($result as $key => $value)
                    {
                        $returnResults[$results[1][$k]][$key] = $value;
                    }
                }
            }
        }

        return $returnResults;
    }
    
    function parse_explore ($string) {
        if (preg_match_all('/Exploration for ((\s*\d+ [a-z]+,?)*) begun at a cost of (\d+) platinum and (\d+) draftees./im', $string, $pp)) {
            foreach (self::simtodomlandtypes as $sim => $dom) {
                $explores['explore'][$dom] = 0;
                foreach ($pp[1] as $key => $val) {
                    $pp[1][$key] = str_replace($sim, $dom, $val);
                }
            }
                

            foreach ($pp[0] as $k => $val) {
                foreach (explode(',', $pp[1][$k]) as $xplorland) {
                    list ($land, $type) = explode(' ', trim($xplorland));
                    $explores['explore'][$type] = $land;
                }
            }
            return $explores;
        }
    }
    function parse_construction ($string) {
        if (preg_match_all('/Construction of ,*((\s*\d+ [a-z\s]+,?)*) started at a cost of (\d+) platinum and (\d+) lumber./im', $string, $pp)) {
            foreach (self::simtodombuildings as $sim => $dom) {
                $construction['construction'][$dom] = 0;
                $pp[1][0] = str_replace($sim, $dom, $pp[1][0]);
            }
            foreach (explode(',', trim($pp[1][0])) as $p) {
                list ($count, $building) = explode(' ', trim($p));
                $construction['construction'][$building] = $count;
            }
            return $construction;
        }
    }
    function parse_destruction ($string) {
        if (preg_match_all('/Destruction of ,*((\s*\d+ [a-z\s]+,?)*) is complete./im', $string, $pp)) {
            foreach (self::simtodombuildings as $sim => $dom) {
                $construction['destruction'][$dom] = 0;
                $pp[1][0] = str_replace($sim, $dom, $pp[1][0]);
            }   
            foreach (explode(',', trim($pp[1][0])) as $p) {
                list ($count, $building) = explode(' ', trim($p));
                $destruction['destruction'][$building] = $count;
            }
            return $destruction;
        }
    }
    function parse_rezone ($string) {
                           //Rezoning begun at a cost of 15000 platinum. The changes in land are as following: ((\s*\d+ [a-z\s]+,?)*)
        if (preg_match_all('/Rezoning begun at a cost of (\d+) platinum. The changes in land are as following: ((\s*-?\d+ [a-z]+,?)*)/i', $string, $pp)) {
            foreach (self::simtodomlandtypes as $sim => $dom) {
                $rezone['rezone']['remove'][$dom] = 0;
                $rezone['rezone']['add'][$dom] = 0;
                $pp[2][0] = str_replace($sim, $dom, $pp[2][0]);
            }
            foreach (explode(',', trim($pp[2][0])) as $p) {
                list ($count, $building) = explode(' ', trim($p));
                if($count < 0)
                {
                    $rezone['rezone']['remove'][$building] = ($count * -1);
                }
                else
                {
                    $rezone['rezone']['add'][$building] = $count;
                }
            }
            return $rezone;
        }
    }
    function parse_bank ($string) {
        if (preg_match_all('/(\d+) ([a-z]+) have been traded for (\d+) ([a-z]+)./i', $string, $pp)) {

            foreach ($pp[0] as $key => $val)
                foreach (self::simtodombanktypes as $sim => $dom) {
                    $pp[2][$key] = str_replace($sim, $dom, $pp[2][$key]);
                    $pp[4][$key] = str_replace($sim, $dom, $pp[4][$key]);
                }
            foreach ($pp[0] as $k => $dummy) {
                $bank['bank'] =
                    array(
                        'source' => $pp[2][0],
                        'target' => $pp[4][0],
                        'amount' => $pp[1][0]
                    );
                // $bank[] =
                //     array(
                //         $pp[2][0].'_'.$pp[4][0] => $pp[1][0]);
            }
            return $bank;
        }
    }

    function parse_magic ($string) {
        if (preg_match_all('/Your wizards successfully cast ([a-z\']+\s{0,1}[a-z\']+) at a cost of (\d+) mana./i', $string, $pp)) {
            $spells = array();
            foreach ($pp[0] as $k => $v) {
                foreach (self::simtodomspells as $sim => $dom)
                    $pp[1][$k] = str_replace($sim, $dom, $pp[1][$k]);
                $spells['magic'][] = $pp[1][$k];
            }
            return $spells;
        }
    }

    function parse_daily ($string) {
        $dailies = array();
        if (preg_match_all('/You have been awarded with (\d+) ([a-zA-Z0-9]+)\./i', $string, $pp)) {
            foreach ($pp[0] as $k => $v) {
                $dailies['daily'][] = $pp[1][$k] == 20 ? 'land' : 'plat';
            }
            return $dailies;
        }
    }

    function parse_train ($string) {

        if (preg_match_all('/Training of ((\s*\d+ [a-z\s]+,?)*) begun at a cost of (\d*) platinum, (\d*) ore, (\d+) draftees, and (\d*) wizards\./i', $string, $pp)) {
            foreach (self::unitstodomparamsmap as $unit => $domparam) {
                $train['train'][$domparam] = 0;
                $pp[1][0] = str_replace($unit, $domparam, $pp[1][0]);
            }
            foreach (explode(',', trim($pp[1][0])) as $v) {
                list ($units, $unit) = explode(' ', trim($v));
                $train['train'][$unit] = $units;
            }
            return $train;
        }
    }

    function parse_release ($string) {

        $release = array();
        if (preg_match_all('/You sucessfully released ((\s*\d+ [a-z\s]+,?)*)\./i', $string, $pp)) {
            
            foreach ($pp[1] as $k => $ppp)
                foreach (self::unitstodomparamsmap as $unit => $domparam) {
                    $train['release'][$domparam] = 0;
                    $pp[1][$k] = str_replace($unit, $domparam, $pp[1][$k]);
                }

            foreach ($pp[1] as $k => $ppp) {
                foreach (explode(',', trim($ppp)) as $eachunit) {
                    list ($num, $unit) = explode(' ', trim($eachunit));
                    $release['release'][str_replace(' ', '', $unit)] = $num;
                }
            }
        }
        return $release;
    }
}
