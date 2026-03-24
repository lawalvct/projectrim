<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['name' => 'Nigeria', 'code' => 'NG'],
            ['name' => 'Ghana', 'code' => 'GH'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'South Africa', 'code' => 'ZA'],
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'India', 'code' => 'IN'],
            ['name' => 'Australia', 'code' => 'AU'],
            ['name' => 'Germany', 'code' => 'DE'],
            ['name' => 'France', 'code' => 'FR'],
            ['name' => 'Cameroon', 'code' => 'CM'],
            ['name' => 'Tanzania', 'code' => 'TZ'],
            ['name' => 'Uganda', 'code' => 'UG'],
            ['name' => 'Egypt', 'code' => 'EG'],
            ['name' => 'Ethiopia', 'code' => 'ET'],
            ['name' => 'Rwanda', 'code' => 'RW'],
            ['name' => 'Senegal', 'code' => 'SN'],
            ['name' => 'Zimbabwe', 'code' => 'ZW'],
            ['name' => 'Zambia', 'code' => 'ZM'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(['code' => $country['code']], $country);
        }
    }
}
