<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->foreignId('client_id')->nullable()->constrained();
            $table->foreignId('contact_person_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->references('id')->on(config('database.connections.mysql.database') .'.users');
            $table->enum('type', ['lead', 'task'])->default('lead');
            $table->string('subject');
            $table->longText('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->enum('status', ['open', 'completed', 'invoiced', 'closed'])->default('open');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
