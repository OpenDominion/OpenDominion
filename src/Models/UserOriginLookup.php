<?php

namespace OpenDominion\Models;

/**
 * OpenDominion\Models\UserOriginLookup
 *
 * @property int $id
 * @property string $ip_address
 * @property string|null $isp
 * @property string|null $organization
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property bool|null $proxy
 * @property bool|null $vpn
 * @property bool|null $tor
 * @property bool|null $active_vpn
 * @property bool|null $active_tor
 * @property float|null $score
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOriginLookup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOriginLookup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\OpenDominion\Models\UserOriginLookup query()
 * @mixin \Eloquent
 */
class UserOriginLookup extends AbstractModel
{
    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'proxy' => 'boolean',
        'vpn' => 'boolean',
        'tor' => 'boolean',
        'active_vpn' => 'boolean',
        'active_tor' => 'boolean',
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Anonymizer flag display definitions, most severe first.
     */
    public const ANONYMIZER_FLAGS = [
        'active_vpn' => ['label' => 'Active VPN', 'severity' => 'danger'],
        'active_tor' => ['label' => 'Active Tor', 'severity' => 'danger'],
        'vpn' => ['label' => 'VPN', 'severity' => 'warning'],
        'tor' => ['label' => 'Tor', 'severity' => 'warning'],
        'proxy' => ['label' => 'Proxy', 'severity' => 'secondary'],
    ];

    /**
     * Returns the anonymizer flags for display, most severe first.
     *
     * Active flags are confirmed and take precedence. Suspected flags are only
     * listed when no more specific flag already covers them: proxy is always
     * true alongside vpn or tor, and vpn/tor are implied by their active variants.
     *
     * @return array<int, array{label: string, severity: string}>
     */
    public function getAnonymizerFlags(): array
    {
        return array_values(array_intersect_key(self::ANONYMIZER_FLAGS, array_flip($this->getAnonymizerFlagKeys())));
    }

    /**
     * Combines the anonymizer flags of several lookups, listing each flag once, most severe first.
     *
     * Each lookup's flags are resolved on their own before combining, so a
     * proxy-only IP still contributes Proxy when another IP has an active VPN.
     *
     * @param iterable<UserOriginLookup> $lookups
     * @return array<int, array{label: string, severity: string}>
     */
    public static function combineAnonymizerFlags(iterable $lookups): array
    {
        $flagKeys = [];
        foreach ($lookups as $lookup) {
            $flagKeys = array_merge($flagKeys, $lookup->getAnonymizerFlagKeys());
        }

        return array_values(array_intersect_key(self::ANONYMIZER_FLAGS, array_flip($flagKeys)));
    }

    /**
     * Returns the keys of the anonymizer flags this lookup should display.
     *
     * @return array<int, string>
     */
    protected function getAnonymizerFlagKeys(): array
    {
        $flagKeys = [];

        if ($this->active_vpn) {
            $flagKeys[] = 'active_vpn';
        }
        if ($this->active_tor) {
            $flagKeys[] = 'active_tor';
        }
        if ($this->vpn && !$this->active_vpn) {
            $flagKeys[] = 'vpn';
        }
        if ($this->tor && !$this->active_tor) {
            $flagKeys[] = 'tor';
        }
        if ($this->proxy && empty($flagKeys)) {
            $flagKeys[] = 'proxy';
        }

        return $flagKeys;
    }

    /**
     * Determines whether any anonymizer flag was reported by the lookup.
     *
     * @return bool
     */
    public function hasAnonymizerData(): bool
    {
        return $this->proxy !== null
            || $this->vpn !== null
            || $this->tor !== null
            || $this->active_vpn !== null
            || $this->active_tor !== null;
    }
}
