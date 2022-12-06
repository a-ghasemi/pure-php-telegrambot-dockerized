<?php

namespace Database\Seeders;

use App\Models\BotConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BotConnectionSeeder extends Seeder
{
    public function run()
    {
//        DB::table('bot_connections')->truncate();//causes issue

        BotConnection::create([
            'title'           => 'Migration Helper Robot',
            'username'        => 'migration_robot',
            'robot_token'     => '5701451847:AAHWFV3yWg-64aCpULBVKm1mKUKUDrAeeQY2',
            'parameters'      => null,
            'webhook_token'   => 'fweLfk23',
            'active'          => true,
        ]);
    }
}
