<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('phone')->unique()->nullable();
            $table->string('email')->unique()->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('password')->nullable();
            $table->boolean('is_asker')->default(true); // true = سائل (asker)، false = مجيب (responder)
            $table->boolean('is_active')->default(true); // الحساب مفعل أو لا
            $table->text('description')->nullable();
            $table->timestamps();
            $table->rememberToken();

        });

         // ✅ تحديث الـ description للمستخدمين الموجودين
        DB::table('users')->where('is_asker', true)
            ->update(['description' => 'سائل']);
            
        DB::table('users')->where('is_asker', false)
            ->update(['description' => 'متخصص في الردود الميدانية للمؤسسات الحكومية بالرياض.']);
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};