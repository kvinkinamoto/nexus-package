<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The published App\Models\User (AppStubs/User.php.stub) declares these profile columns as
 * fields/fillable, but Laravel's stock users table has only name/email/password. Each column is
 * added only if missing, so a project that already has them (e.g. its own users migration) is
 * left untouched.
 */
return new class extends Migration
{
    /** @return array<string, callable(Blueprint): mixed> */
    private function columns(): array
    {
        return [
            'last_name' => fn (Blueprint $table) => $table->string('last_name')->nullable()->after('name'),
            'middle_name' => fn (Blueprint $table) => $table->string('middle_name')->nullable(),
            'phone' => fn (Blueprint $table) => $table->string('phone', 32)->nullable(),
            'gender' => fn (Blueprint $table) => $table->string('gender', 16)->nullable(),
            'birthday' => fn (Blueprint $table) => $table->date('birthday')->nullable(),
            'avatar' => fn (Blueprint $table) => $table->string('avatar')->nullable(),
        ];
    }

    public function up(): void
    {
        foreach ($this->columns() as $name => $definition) {
            if (! Schema::hasColumn('users', $name)) {
                Schema::table('users', fn (Blueprint $table) => $definition($table));
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->columns()) as $name) {
            if (Schema::hasColumn('users', $name)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($name));
            }
        }
    }
};
