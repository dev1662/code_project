<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        
        $client = new ClientRepository();

        $client->createPasswordGrantClient(null, 'Default password grant client', config('app.url'));
        $client->createPersonalAccessClient(null, 'Default personal access client', config('app.url'));
    }
}
