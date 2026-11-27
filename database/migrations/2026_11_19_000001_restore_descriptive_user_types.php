
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('user_type_id')
                ->nullable()
                ->constrained('user_types')
                ->nullOnDelete();
        });

        $labels = DB::table('users')
            ->whereNotNull('user_type_label')
            ->whereRaw("TRIM(user_type_label) <> ''")
            ->distinct()
            ->pluck('user_type_label');

        $typeIds = [];

        foreach ($labels as $label) {
            $name = trim((string) $label);
            $key = Str::lower($name);

            if ($name === '' || isset($typeIds[$key])) {
                continue;
            }

            $typeIds[$key] = DB::table('user_types')->insertGetId([
                'name' => $name,
                'description' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('users')
            ->select('id', 'user_type_label')
            ->whereNotNull('user_type_label')
            ->orderBy('id')
            ->chunkById(500, function ($users) use ($typeIds) {
                foreach ($users as $user) {
                    $key = Str::lower(trim((string) $user->user_type_label));

                    if (isset($typeIds[$key])) {
                        DB::table('users')
                            ->where('id', $user->id)
                            ->update([
                                'user_type_id' => $typeIds[$key],
                            ]);
                    }
                }
            });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_type_label');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type_label', 100)->nullable();
        });

        $users = DB::table('users')
            ->leftJoin('user_types', 'users.user_type_id', '=', 'user_types.id')
            ->select('users.id', 'user_types.name')
            ->orderBy('users.id')
            ->get();

        foreach ($users as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'user_type_label' => $user->name,
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_type_id');
        });

        Schema::dropIfExists('user_types');
    }
};
