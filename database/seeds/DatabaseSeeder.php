<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([	        
	        [	            
	            'first_name' => 'Vizon',
	            'last_name'	=>	'Admin',
	            'email' => 'admin@vizon.com',	            
	            'password' => bcrypt('Qwerty@1'),
	            'user_type' => 'admin',
                'mobile'    =>  '4587953256'
	        ]
        ]);
    }
}
