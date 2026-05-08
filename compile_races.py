#!/usr/bin/env python3

import yaml
import os
import glob

all_races = {}
os.chdir('/Users/codykinsey/www/OpenDominion/app/data/races')

for filename in glob.glob('*.yml'):
    with open(filename, 'r') as f:
        try:
            race_data = yaml.safe_load(f)
            # Only include if playable is not explicitly false
            if race_data.get('playable', True) != False:
                race_key = race_data.get('key', filename.replace('.yml', ''))
                all_races[race_key] = race_data
        except Exception as e:
            print(f'Error processing {filename}: {e}')

# Write to output file
with open('/Users/codykinsey/www/OpenDominion/all_races.yml', 'w') as f:
    yaml.dump(all_races, f, default_flow_style=False, indent=2, allow_unicode=True)

print(f'Compiled {len(all_races)} playable races')