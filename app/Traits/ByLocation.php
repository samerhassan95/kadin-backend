<?php
declare(strict_types=1);

namespace App\Traits;

use App\Models\Language;
use Cache;
use DB;

/**
 * @property string|null $language
 */
trait ByLocation
{
    public function getShopIds(array $filter): array
    {
        $regionId   = data_get($filter, 'region_id');
        $countryId  = data_get($filter, 'country_id');
        $cityId     = data_get($filter, 'city_id');
        $areaId     = data_get($filter, 'area_id');

        $byLocation = $regionId || $countryId || $cityId || $areaId;

        if (!$byLocation) {
            return [];
        }

        return Cache::remember(
            "shop_locations_{$regionId}_{$countryId}_{$cityId}_$areaId",
            31536000,
            function () use ($regionId, $countryId, $cityId, $areaId) {
                return DB::table('shop_locations')
                    ->where('region_id', $regionId)
                    ->when($countryId,  fn($q) => $q->orWhere('country_id', $countryId))
                    ->when($cityId,     fn($q) => $q->orWhere('city_id', $cityId))
                    ->when($areaId,     fn($q) => $q->orWhere('area_id', $areaId))
                    ->pluck('shop_id')
                    ->unique()
                    ->values()
                    ->toArray();
            });
    }

    public function search(mixed $query, ?string $search): array
    {
        return $query->where(function ($query) use ($search) {
            $query
                ->whereHas('region.translation', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                })
                ->orWhereHas('country.translation', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                })
                ->orWhereHas('city.translation', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                })
                ->orWhereHas('area.translation', function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%$search%");
                });
        });
    }

    public function getWith(): array
    {
        $locale = Language::languagesList()->where('default', 1)->first()?->locale;

        if (!isset($this->language)) {
            $this->language = $locale;
        }

        return [
            'region.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'country.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'city.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
            'area.translation' => fn($query) => $query->when($this->language, fn($q) => $q->where(function ($q) use ($locale) {
                    $q->where('locale', $this->language)->orWhere('locale', $locale);
                })),
        ];
    }
}
