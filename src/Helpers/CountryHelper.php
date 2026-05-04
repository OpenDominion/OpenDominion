<?php

namespace OpenDominion\Helpers;

class CountryHelper
{
    /** @var array<string, string>|null */
    protected static $countries = null;

    /**
     * Returns ISO 3166-1 alpha-2 country code => display name.
     *
     * Sourced from flag-icons/country.json (filtered to entries where iso === true)
     * so the picker only contains real countries — not regional flags like EU/ASEAN.
     *
     * @return array<string, string>
     */
    public function getCountries(): array
    {
        if (self::$countries !== null) {
            return self::$countries;
        }

        $path = public_path('assets/vendor/flag-icons/country.json');
        $countries = [];

        if (is_readable($path)) {
            $data = json_decode(file_get_contents($path), true) ?: [];
            foreach ($data as $entry) {
                if (!empty($entry['iso']) && !empty($entry['code']) && !empty($entry['name'])) {
                    $countries[$entry['code']] = $entry['name'];
                }
            }
            asort($countries);
        }

        return self::$countries = $countries;
    }

    public function isValid(string $code): bool
    {
        return array_key_exists($code, $this->getCountries());
    }

    public function getName(string $code): ?string
    {
        return $this->getCountries()[$code] ?? null;
    }
}
