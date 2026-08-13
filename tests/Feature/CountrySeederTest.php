<?php

use App\Models\Country;
use Database\Seeders\CountrySeeder;

test('country seeder provides the complete requested country list without duplicates', function () {
    $this->seed(CountrySeeder::class);
    $this->seed(CountrySeeder::class);

    expect(Country::count())->toBe(196)
        ->and(Country::distinct('code')->count('code'))->toBe(196)
        ->and(Country::where('code', 'NG')->value('name'))->toBe('Nigeria')
        ->and(Country::where('code', 'XK')->value('name'))->toBe('Kosovo')
        ->and(Country::where('code', 'TL')->value('name'))->toBe('East Timor')
        ->and(Country::where('code', 'CD')->value('name'))->toBe('Democratic Republic of the Congo')
        ->and(Country::where('code', 'VA')->value('name'))->toBe('Vatican City');
});
