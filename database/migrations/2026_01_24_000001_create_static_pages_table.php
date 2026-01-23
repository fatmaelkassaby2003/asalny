<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // جدول الشروط والأحكام
        Schema::create('terms_and_conditions', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->longText('content_ar');
            $table->longText('content_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول سياسة الخصوصية
        Schema::create('privacy_policy', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->longText('content_ar');
            $table->longText('content_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول الأسئلة الشائعة
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question_ar');
            $table->string('question_en');
            $table->text('answer_ar');
            $table->text('answer_en');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول عن التطبيق
        Schema::create('about_app', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en');
            $table->longText('content_ar');
            $table->longText('content_en');
            $table->string('app_version')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_and_conditions');
        Schema::dropIfExists('privacy_policy');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('about_app');
    }
};
