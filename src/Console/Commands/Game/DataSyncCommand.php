<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Models\Race;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class DataSyncCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:data:sync';

    /** @var string The console command description. */
    protected $description = '';

    /** @var Filesystem */
    protected $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;

        //
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $this->syncRaces();
        //
    }

    protected function syncRaces()
    {
        $files = $this->filesystem->files(base_path('app/data/races'));

        foreach ($files as $file) {
            $data = Yaml::parse($file->getContents(), Yaml::PARSE_OBJECT_FOR_MAP);

            $this->info("Processing race {$data->name}");

            // Race
            $race = Race::firstOrNew(['name' => $data->name])
                ->fill([
                    'alignment' => object_get($data, 'alignment'),
                    'home_land_type' => object_get($data, 'home_land_type'),
                ]);

            if ($race->exists) {
                $newValues = $race->getDirty();

                foreach ($newValues as $key => $newValue) {
                    $originalValue = $race->getOriginal($key);

                    $this->info("Changing {$key} from {$originalValue} to {$newValue}");
                }
            }

//            $race->save();
//            $race->refresh();

            // Race Perks
            foreach (object_get($data, 'perks', []) as $perk => $value) {
                //
            }

            // Units
            //

            // Unit Perks
            //


            dd([
                $race->toArray(),
                $newValues,
                $race->id,
            ]);



            $race = Race::where('name', $data->name)->first();

            if ($race !== null) {

                $race->fill([
                    'alignment' => object_get($data, 'alignment'),
                    'home_land_type' => object_get($data, 'home_land_type'),
                ]);

                foreach (object_get($data, 'perks', []) as $perk => $value) {
                    // check if race_perk_types with key=$value exist
                        // if not, create it

                    // get race_perk_type

                    // check if race_perks exists with race_id=$race->id and race_perk_type_id = $race_perk_type
                        // if not, create it
                    dd([
                        $perk,
                        $value,
                    ]);
                }

                dd('test');

                // perks

                // units

                // unit perks

            }



            // perks

            // units
            // unit perks (+ type)

            // set unique db keys on:
            // - race_perks.[race_id + race_perk_type_id]
            // - races.name



            dd($race->toArray());

//            if (!object_get($race, 'enabled', true)) {
//                $this->info('Skipping disabled race ' . $race->name);
//                continue;
//            }

            //

            dd($data->name);
        }


        $path = base_path('app/data/races');
    }
}
