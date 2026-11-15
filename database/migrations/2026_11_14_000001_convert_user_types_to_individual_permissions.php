<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->string('user_type_label', 100)->nullable();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->primary(['user_id', 'permission_id']);
        });

        DB::table('users')->leftJoin('user_types', 'users.user_type_id', '=', 'user_types.id')
            ->select('users.id', 'user_types.is_admin', 'user_types.name')->orderBy('users.id')->chunk(500, function ($users) {
                foreach ($users as $user) {
                    DB::table('users')->where('id', $user->id)->update([
                        'is_admin' => (bool) $user->is_admin,
                        'user_type_label' => $user->name,
                    ]);
                }
            });
        DB::table('user_permissions')->insertUsing(['user_id', 'permission_id'], DB::table('users')
            ->join('user_type_permissions', 'users.user_type_id', '=', 'user_type_permissions.user_type_id')
            ->select('users.id', 'user_type_permissions.permission_id'));

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_type_id');
        });
        Schema::dropIfExists('user_type_permissions');
        Schema::dropIfExists('user_types');
    }

    public function down(): void
    {
        // The former shared assignments cannot be reconstructed without restoring the old tables.
        Schema::dropIfExists('user_permissions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'user_type_label']);
        });
    }
};
