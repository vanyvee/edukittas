
<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    /**
     * One row per school a student has ever attended. A student returning
     * to a school they previously left gets a NEW row here, not a reused
     * one — see the StudentService discussion on why (a clean timeline,
     * no ambiguity about which class-level they're re-entering at).
     */
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->unsignedMediumInteger('school_id');
            $table->unsignedMediumInteger('class_id');
            $table->enum('status', ['0', '1', '2'])->default('1'); // 0=withdrawn, 1=active, 2=graduated
            $table->date('admission_date')->default(now());
            $table->date('graduation_date')->nullable();
            $table->enum('sub_type', ['s', 'a', 'b'])->default('b');
            $table->enum('arm', ['0', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'])->default('0');
 
            // Carried over from the old students.post / students.Fees columns —
            // both are per-school-stay concerns (a class rank, a fee amount at
            // THAT school), not identity. Confirm their real meaning before
            // relying on them; flagged as ambiguous when first reviewed.
            $table->smallInteger('post')->default(0);
            $table->mediumInteger('fees')->nullable();
 
            $table->timestamps();
 
            $table->foreign('school_id')->references('id')->on('schools');
            $table->foreign('class_id')->references('id')->on('my_classes');
 
            // Fast lookup for "does this student have history at this school",
            // and for the cross-school dedupe/trace-request queries — NOT
            // unique, since the same student can legitimately have multiple
            // enrollment rows at the same school across different periods.
            $table->index(['student_id', 'school_id']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
 