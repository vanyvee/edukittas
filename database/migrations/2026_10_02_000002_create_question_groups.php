<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Shared stimulus for a set of questions: a gap passage, a comprehension
        // passage, a diagram/image, an audio clip, etc.
        Schema::create('question_groups', function (Blueprint $table) {
            $table->id();

            // Tenant. NULL = platform-owned global bank. A school, a teacher's
            // organisation or an independent quiz host otherwise.
            // Add ->constrained('<your tenant table>') once its name is fixed.
        
            $table->foreignId('created_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete(); // null = company
            $table->boolean('vetted')->default(false)->index();
            $table->string('title');
            $table->text('introduction')->nullable();   // e.g. "Read the passage and answer questions 1-5"
            $table->longText('content');                // passage text, or a media path for diagram/audio
            $table->string('type', 20);                 // gap | passage | diagram | audio

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_groups');
    }
};