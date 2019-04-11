# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/). This project uses its own versioning system.

## [Unreleased]

## [0.6.1-1] - 2019-04-11
### Added
- Added barren land column to explore page

### Changed
- The 'current hour' counter in at the bottom now displays 1-24, instead of 0-23
- Dominions on the realm page are now also sorted by networth if land sizes are the same

### Fixed
- Fixed a bug where packies can close the pack they're in. Now only the pack creator can close it

## [0.6.1] - 2019-04-09
### Added
- Added the following races to Valhalla: Dwarves, Goblins, Firewalkers, and Lizardmen
- Added largest/strongest packs to Valhalla

### Changed
- Moved the 'Join the Discord'-button from status page to daily bonuses page

### Fixed
- Removed "round not yet started"-alert from homepage
- Fixed a bug where creating a pack is placed in a new realm, instead of an already existing and eligible realm
- Minor text fixes

## [0.6.0-2] - 2019-04-09
### Changed
- Updated info box on the magic page
- Added indicator for racial spells on magic page

### Fixed
- Fixed error when registering to a round with duplicate dominion name
- Fixed several tables with data not displaying properly on mobile
- Fixed rankings change column not visible on mobile

## [0.6.0-1] - 2019-04-09
### Fixed
- Fixed an error when registering to a round and creating a new pack

## [0.6.0] - 2019-04-08
### Added
- Added invasions!
- Added Town Crier page
- Clear Sight now mentions if the target was invaded recently, plus roughly how severely 
- Military units now have a role icon next to them
- Temples are now fully implemented, also reducing DP bonuses of targets you're invading
- Added current round information to the home page

### Changed
- Unit and building tooltips have been moved from the question mark icon, to on the name itself

### Fixed
- Fixed not getting a notification when preventing a hostile spell or spy operation

## [0.5.2-1] - 2019-01-27
### Fixed
- Fixed racial spells for Firewalker and Lizardfolk

## [0.5.2] - 2019-01-27
### Added
- Added new races: Firewalker and Lizardfolk.

## [0.5.1-4] to [0.5.1-8] - 2018-10-04
### Fixed
- Fix user IP resolving when behind Cloudflare DNS with trusted proxies.
- Dominion numbering on the realm page now correctly starts at 1, instead of 0.

### Other
- Maintenance work.

## [0.5.0-9] to [0.5.1-3] - 2018-10-02
### Fixed
- Trying to fix deploy errors.

### Other
- Maintenance work.

## [0.5.0-8] - 2018-10-01
### Changed
- Info gathering ops on Op Center page now show exact time upon hover. ([#337](https://github.com/WaveHack/OpenDominion/issues/337))
- Significantly reduced spy losses on failed ops.

### Fixed
- Fixed networth sometimes showing incorrect values on realm page. ([#310](https://github.com/WaveHack/OpenDominion/issues/310))
- Fixed construction cost calculation. As a result, construction costs are significantly higher than before. Time to start building factories. ([#347](https://github.com/WaveHack/OpenDominion/issues/347))
- Barracks Spy now shows number of draftees. ([#331](https://github.com/WaveHack/OpenDominion/issues/331))
- Fixed an division by zero error if you have 0 peasants. ([#349](https://github.com/WaveHack/OpenDominion/issues/349))
- Various other issues.

### Other
- Documentation update.
- Refactoring.
- Queue refactor!
- More refactoring.
- Seriously, a lot of refactoring.

## [0.5.0-7] - 2018-08-26
### Other
- Maintenance work.

## [0.5.0-6] - 2018-08-26
### Changed
- Switched Information and Under Protection sections around on status page.([#313](https://github.com/WaveHack/OpenDominion/issues/313))

### Other
- Maintenance work.
- Documentation update.

## [0.5.0-5] - 2018-08-11
### Fixed
- Fixed cost rounding issues on wizard cost multiplier when wizard guilds were built.
- Fixed a bug regarding population growth. ([#176](https://github.com/WaveHack/OpenDominion/issues/176))

## [0.5.0-4] - 2018-08-07
### Fixed
- Council pages now show ruler name instead of user name.

## [0.5.0-3] - 2018-08-05
### Fixed
- Fixed creating a pack sometimes giving an error. ([#321](https://github.com/WaveHack/OpenDominion/issues/321))

## [0.5.0-2] - 2018-08-04
### Fixed
- Realm page now shows ruler name instead of user name. ([#309](https://github.com/WaveHack/OpenDominion/issues/309))
- Limit amount of rows of show on realm page in case realm size is less than 12. ([#308](https://github.com/WaveHack/OpenDominion/issues/308))

### Other
- Refactoring.

## [0.5.0-1] - 2018-08-04
### Fixed
- Fix deploy error.

## [0.5.0] - 2018-08-04
### Added
- Added new races: Dwarf and Goblin.
- Added ruler name for round registration. ([#254](https://github.com/WaveHack/OpenDominion/issues/254))
- Added packs. ([#280](https://github.com/WaveHack/OpenDominion/issues/280))
- Added racial spells. ([#157](https://github.com/WaveHack/OpenDominion/issues/157))
- Added Op Center. ([#24](https://github.com/WaveHack/OpenDominion/issues/24))
- Added Espionage with info gathering operations: Barracks Spy, Castle Spy, Survey Dominion, and Land Spy. ([#21](https://github.com/WaveHack/OpenDominion/issues/21))
- Added Clear Sight spell. ([#220](https://github.com/WaveHack/OpenDominion/issues/220))
- Added Revelation spell. ([#221](https://github.com/WaveHack/OpenDominion/issues/221))
- Added unit OP/DP stats on military page ([#234](https://github.com/WaveHack/OpenDominion/issues/234))
- Added NYI/PI indicator and help text icon on Construction Advisor page
- Added Wizard Guild building, reducing wizard/AM plat cost and increasing wizard strength regen per hour. ([#305](https://github.com/WaveHack/OpenDominion/issues/305))
- Added failed spy op losses reduction on Forest Havens. ([#306](https://github.com/WaveHack/OpenDominion/issues/306))

### Changed
- Updated round registration page with better help texts and racial descriptions.
- Employment percentage on status screen now shows 2 decimals.
- Updated Re-zone Land icon in the sidebar.
- Net OP/DP now scales with morale, down to -10% at 0% morale.

## [0.4.2] - 2018-06-03
### Fixed
- Fixed dashboard page sometimes showing incorrect duration for round start/end dates.
- Fixed internal server error on realm page using invalid realm number. ([#270](https://github.com/WaveHack/OpenDominion/issues/270))

## [0.4.1] - 2018-05-23
### Changed
- Updated `version:update` command to support Git tags.

## 0.4.0 - 2018-05-22
### Added
- This CHANGELOG file.

[Unreleased]: https://github.com/WaveHack/OpenDominion/compare/0.6.1-1...HEAD
[0.6.1-1]: https://github.com/WaveHack/OpenDominion/compare/0.6.1...0.6.1-1
[0.6.1]: https://github.com/WaveHack/OpenDominion/compare/0.6.0-2...0.6.1
[0.6.0-2]: https://github.com/WaveHack/OpenDominion/compare/0.6.0-1...0.6.0-2
[0.6.0-1]: https://github.com/WaveHack/OpenDominion/compare/0.6.0...0.6.0-1
[0.6.0]: https://github.com/WaveHack/OpenDominion/compare/0.5.2-1...0.6.0
[0.5.2-1]: https://github.com/WaveHack/OpenDominion/compare/0.5.2...0.5.2-1
[0.5.2]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-8...0.5.2
[0.5.1-8]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-7...0.5.1-8
[0.5.1-7]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-6...0.5.1-7
[0.5.1-6]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-5...0.5.1-6
[0.5.1-5]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-4...0.5.1-5
[0.5.1-4]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-3...0.5.1-4
[0.5.1-3]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-2...0.5.1-3
[0.5.1-2]: https://github.com/WaveHack/OpenDominion/compare/0.5.1-1...0.5.1-2
[0.5.1-1]: https://github.com/WaveHack/OpenDominion/compare/0.5.1...0.5.1-1
[0.5.1]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-9...0.5.1
[0.5.0-9]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-8...0.5.0-9
[0.5.0-8]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-7...0.5.0-8
[0.5.0-7]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-6...0.5.0-7
[0.5.0-6]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-5...0.5.0-6
[0.5.0-5]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-4...0.5.0-5
[0.5.0-4]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-3...0.5.0-4
[0.5.0-3]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-2...0.5.0-3
[0.5.0-2]: https://github.com/WaveHack/OpenDominion/compare/0.5.0-1...0.5.0-2
[0.5.0-1]: https://github.com/WaveHack/OpenDominion/compare/0.5.0...0.5.0-1
[0.5.0]: https://github.com/WaveHack/OpenDominion/compare/0.4.2...0.5.0
[0.4.2]: https://github.com/WaveHack/OpenDominion/compare/0.4.1...0.4.2
[0.4.1]: https://github.com/WaveHack/OpenDominion/compare/0.4.0...0.4.1
