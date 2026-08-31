<?php

namespace Nodex\Nexus\commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CreateSuperAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:create:superadmin {name?} {email?} {password?} {--name=} {--email=} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Super Admin User';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(

    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return string
     */
    public function handle(): string
    {
        Artisan::call('nexus:permission:init');

        $name = $this->argument('name') ?: ($this->option('name') ?: $this->ask("Ім'я користувача"));
        $email = $this->argument('email') ?: ($this->option('email') ?: $this->ask('Email'));
        $password = $this->argument('password') ?: ($this->option('password') ?: $this->secret('Пароль'));

        if (User::where('email', $email)->exists()) {
            $this->info('Користувач з таким email вже існує. Пропускаємо створення.');
            return Command::SUCCESS;
        }

        /** ---------------------------
         *  Створення користувача
         * ----------------------------*/
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        /** ---------------------------
         *  Створення / отримання ролі
         * ----------------------------*/
        $role = Role::firstOrCreate(
            ['name' => 'super-admin'],
            ['guard_name' => 'web']
        );

        /** ---------------------------
         *  Призначення всіх permissions ролі
         * ----------------------------*/
        $permissions = Permission::where('guard_name', $role->guard_name)->get();

        if ($permissions->isEmpty()) {
            $this->warn('У базі немає permissions');
        } else {
            $role->syncPermissions($permissions);
            $this->info('Ролі super-admin призначено всі permissions');
        }

        /** ---------------------------
         *  Призначення ролі користувачу
         * ----------------------------*/
        $user->assignRole($role);

        $this->info('Суперкористувача створено успішно');

        return Command::SUCCESS;
    }

    /**
     * @return void
     */
}
