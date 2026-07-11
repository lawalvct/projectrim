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
            ['name' => 'Algeria', 'code' => 'DZ'],
            ['name' => 'Angola', 'code' => 'AO'],
            ['name' => 'Benin', 'code' => 'BJ'],
            ['name' => 'Botswana', 'code' => 'BW'],
            ['name' => 'Burkina Faso', 'code' => 'BF'],
            ['name' => 'Burundi', 'code' => 'BI'],
            ['name' => 'Cabo Verde', 'code' => 'CV'],
            ['name' => 'Central African Republic', 'code' => 'CF'],
            ['name' => 'Chad', 'code' => 'TD'],
            ['name' => 'Comoros', 'code' => 'KM'],
            ['name' => 'Congo', 'code' => 'CG'],
            ['name' => 'Democratic Republic of the Congo', 'code' => 'CD'],
            ['name' => "Cote d'Ivoire", 'code' => 'CI'],
            ['name' => 'Djibouti', 'code' => 'DJ'],
            ['name' => 'Equatorial Guinea', 'code' => 'GQ'],
            ['name' => 'Eritrea', 'code' => 'ER'],
            ['name' => 'Eswatini', 'code' => 'SZ'],
            ['name' => 'Gabon', 'code' => 'GA'],
            ['name' => 'Gambia', 'code' => 'GM'],
            ['name' => 'Ghana', 'code' => 'GH'],
            ['name' => 'Guinea', 'code' => 'GN'],
            ['name' => 'Guinea-Bissau', 'code' => 'GW'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'Lesotho', 'code' => 'LS'],
            ['name' => 'Liberia', 'code' => 'LR'],
            ['name' => 'Libya', 'code' => 'LY'],
            ['name' => 'Madagascar', 'code' => 'MG'],
            ['name' => 'Malawi', 'code' => 'MW'],
            ['name' => 'Mali', 'code' => 'ML'],
            ['name' => 'Mauritania', 'code' => 'MR'],
            ['name' => 'Mauritius', 'code' => 'MU'],
            ['name' => 'Morocco', 'code' => 'MA'],
            ['name' => 'Mozambique', 'code' => 'MZ'],
            ['name' => 'Namibia', 'code' => 'NA'],
            ['name' => 'Niger', 'code' => 'NE'],
            ['name' => 'Sao Tome and Principe', 'code' => 'ST'],
            ['name' => 'Seychelles', 'code' => 'SC'],
            ['name' => 'Sierra Leone', 'code' => 'SL'],
            ['name' => 'Somalia', 'code' => 'SO'],
            ['name' => 'South Africa', 'code' => 'ZA'],
            ['name' => 'South Sudan', 'code' => 'SS'],
            ['name' => 'Sudan', 'code' => 'SD'],
            ['name' => 'Togo', 'code' => 'TG'],
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
            Country::updateOrCreate(['code' => $country['code']], ['name' => $country['name']]);
        }
    }
}
