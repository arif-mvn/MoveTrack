<?php

namespace Database\Seeders;

use App\Enums\Source\SourceFieldsEnum;
use App\Enums\Source\SourceTypeEnum;
use App\Enums\Source\SourceCodeEnum;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $rows = [
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::MOVEON,
                SourceFieldsEnum::NAME   => 'MoveOn (Authoritative)',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::INTERNAL,
                SourceFieldsEnum::CONFIG => [
                    'staleness' => [
                        'origin_procurement' => 'PT12H',
                        'linehaul'           => 'PT6H',
                        'destination_wh'     => 'PT6H',
                        'last_mile'          => 'PT1H',
                    ],
                ],
                SourceFieldsEnum::ENABLED => true,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::YUN_EXPRESS,
                SourceFieldsEnum::NAME   => 'YunExpress',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => [
                    'staleness' => ['last_mile' => 'PT1H', 'linehaul' => 'PT6H'],
                    'pull'      => ['base_url' => 'https://openapi.yuntrack.com', 'api_key' => 'dummy'],
                ],
                SourceFieldsEnum::ENABLED => true,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::IMILE,
                SourceFieldsEnum::NAME   => 'iMile',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => [
                    'staleness' => ['last_mile' => 'PT1H'],
                    'pull'      => ['base_url' => 'https://api.imile.com', 'api_key' => 'dummy'],
                ],
                SourceFieldsEnum::ENABLED => true,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::STEADFAST,
                SourceFieldsEnum::NAME   => 'Steadfast',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => [
                    'staleness' => ['last_mile' => 'PT1H'],
                    'pull'      => ['base_url' => 'https://portal.steadfast.com.bd', 'api_key' => 'dummy'],
                ],
                SourceFieldsEnum::ENABLED => true,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::REDX,
                SourceFieldsEnum::NAME   => 'REDX',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => ['staleness' => ['last_mile' => 'PT1H']],
                SourceFieldsEnum::ENABLED => false,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::ECOURIER,
                SourceFieldsEnum::NAME   => 'eCourier',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => ['staleness' => ['last_mile' => 'PT1H']],
                SourceFieldsEnum::ENABLED => false,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::PATHAO_COURIER,
                SourceFieldsEnum::NAME   => 'Pathao Courier',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => ['staleness' => ['last_mile' => 'PT1H']],
                SourceFieldsEnum::ENABLED => false,
            ],
            [
                SourceFieldsEnum::CODE   => SourceCodeEnum::CHINA_POST,
                SourceFieldsEnum::NAME   => 'China Post',
                SourceFieldsEnum::TYPE   => SourceTypeEnum::COURIER,
                SourceFieldsEnum::CONFIG => ['staleness' => ['linehaul' => 'PT12H']],
                SourceFieldsEnum::ENABLED => false,
            ],
        ];

        foreach ($rows as $row) {
            Source::query()->updateOrCreate(
                [SourceFieldsEnum::CODE => $row[SourceFieldsEnum::CODE]],
                array_merge($row, [
                    SourceFieldsEnum::WEBHOOK_SECRET => $row[SourceFieldsEnum::WEBHOOK_SECRET] ?? null,
                ])
            );
        }
    }
}
