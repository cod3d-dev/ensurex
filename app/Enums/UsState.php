<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UsState: string implements HasLabel
{
    case ALABAMA = 'AL';
    case ALASKA = 'AK';
    case ARIZONA = 'AZ';
    case ARKANSAS = 'AR';
    case CALIFORNIA = 'CA';
    case COLORADO = 'CO';
    case CONNECTICUT = 'CT';
    case DELAWARE = 'DE';
    case FLORIDA = 'FL';
    case GEORGIA = 'GA';
    case HAWAII = 'HI';
    case IDAHO = 'ID';
    case ILLINOIS = 'IL';
    case INDIANA = 'IN';
    case IOWA = 'IA';
    case KANSAS = 'KS';
    case KENTUCKY = 'KY';
    case LOUISIANA = 'LA';
    case MAINE = 'ME';
    case MARYLAND = 'MD';
    case MASSACHUSETTS = 'MA';
    case MICHIGAN = 'MI';
    case MINNESOTA = 'MN';
    case MISSISSIPPI = 'MS';
    case MISSOURI = 'MO';
    case MONTANA = 'MT';
    case NEBRASKA = 'NE';
    case NEVADA = 'NV';
    case NEW_HAMPSHIRE = 'NH';
    case NEW_JERSEY = 'NJ';
    case NEW_MEXICO = 'NM';
    case NEW_YORK = 'NY';
    case NORTH_CAROLINA = 'NC';
    case NORTH_DAKOTA = 'ND';
    case OHIO = 'OH';
    case OKLAHOMA = 'OK';
    case OREGON = 'OR';
    case PENNSYLVANIA = 'PA';
    case RHODE_ISLAND = 'RI';
    case SOUTH_CAROLINA = 'SC';
    case SOUTH_DAKOTA = 'SD';
    case TENNESSEE = 'TN';
    case TEXAS = 'TX';
    case UTAH = 'UT';
    case VERMONT = 'VT';
    case VIRGINIA = 'VA';

    case VIRGINISLAND = 'VI';
    case WASHINGTON = 'WA';
    case WEST_VIRGINIA = 'WV';
    case WISCONSIN = 'WI';
    case WYOMING = 'WY';
    case DISTRICT_OF_COLUMBIA = 'DC';
    case PUERTO_RICO = 'PR';

    public function getLabel(): string
    {
        return match ($this) {
            self::ALABAMA => 'Alabama',
            self::ALASKA => 'Alaska',
            self::ARIZONA => 'Arizona',
            self::ARKANSAS => 'Arkansas',
            self::CALIFORNIA => 'California',
            self::COLORADO => 'Colorado',
            self::CONNECTICUT => 'Connecticut',
            self::DELAWARE => 'Delaware',
            self::FLORIDA => 'Florida',
            self::GEORGIA => 'Georgia',
            self::HAWAII => 'Hawaii',
            self::IDAHO => 'Idaho',
            self::ILLINOIS => 'Illinois',
            self::INDIANA => 'Indiana',
            self::IOWA => 'Iowa',
            self::KANSAS => 'Kansas',
            self::KENTUCKY => 'Kentucky',
            self::LOUISIANA => 'Louisiana',
            self::MAINE => 'Maine',
            self::MARYLAND => 'Maryland',
            self::MASSACHUSETTS => 'Massachusetts',
            self::MICHIGAN => 'Michigan',
            self::MINNESOTA => 'Minnesota',
            self::MISSISSIPPI => 'Mississippi',
            self::MISSOURI => 'Missouri',
            self::MONTANA => 'Montana',
            self::NEBRASKA => 'Nebraska',
            self::NEVADA => 'Nevada',
            self::NEW_HAMPSHIRE => 'New Hampshire',
            self::NEW_JERSEY => 'New Jersey',
            self::NEW_MEXICO => 'New Mexico',
            self::NEW_YORK => 'New York',
            self::NORTH_CAROLINA => 'North Carolina',
            self::NORTH_DAKOTA => 'North Dakota',
            self::OHIO => 'Ohio',
            self::OKLAHOMA => 'Oklahoma',
            self::OREGON => 'Oregon',
            self::PENNSYLVANIA => 'Pennsylvania',
            self::RHODE_ISLAND => 'Rhode Island',
            self::SOUTH_CAROLINA => 'South Carolina',
            self::SOUTH_DAKOTA => 'South Dakota',
            self::TENNESSEE => 'Tennessee',
            self::TEXAS => 'Texas',
            self::UTAH => 'Utah',
            self::VERMONT => 'Vermont',
            self::VIRGINIA => 'Virginia',
            self::VIRGINISLAND => 'Virgin Islands',
            self::WASHINGTON => 'Washington',
            self::WEST_VIRGINIA => 'West Virginia',
            self::WISCONSIN => 'Wisconsin',
            self::WYOMING => 'Wyoming',
            self::DISTRICT_OF_COLUMBIA => 'District of Columbia',
            self::PUERTO_RICO => 'Puerto Rico',
        };
    }
}
