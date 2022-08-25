<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOnboardingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('onboardings', function (Blueprint $table) {
            $table->id();
            $table->string('email', 64);
            $table->string('otp', 8)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('organization_name', 255)->nullable();
            $table->string('subdomain', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->enum('status', ['pending', 'completed'])->default('pending');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('onboardings');
    }
}
