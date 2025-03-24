<?php

// namespace Modules\Users\Database\Seeders;

// use Illuminate\Database\Seeder;
// use Modules\Users\Models\User;
// use Modules\Users\Models\WorkType;

// class UserWorkTypeSeeder extends Seeder
// {
//     /**
//      * Run the database seeds.
//      */
//     public function run(): void
//     {
//         $user_hr = User::findorFail(1);
//         $workTypeSite = WorkType::findOrFail(1);
//         $workTypeHome = WorkType::findOrFail(2);

//         $user_hr->work_types()->attach($workTypeSite->id);
//         $user_hr->work_types()->attach($workTypeHome->id);

//         $user_admin = User::findorFail(2);
//         $user_admin->work_types()->attach($workTypeSite->id);

//     }
// }
