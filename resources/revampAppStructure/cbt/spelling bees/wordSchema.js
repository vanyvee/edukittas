Schema::create('words', function (Blueprint $table) {
    $table->id();
    $table->string('word')->unique();

    $table->tinyInteger('phonetic')->default(0);
    $table->tinyInteger('frequency')->default(0);
    $table->tinyInteger('morphology')->default(0);
    $table->tinyInteger('letter_pattern')->default(0);
    $table->tinyInteger('length_modifier')->default(0);
    $table->tinyInteger('total_score')->default(0);

    $table->string('difficulty')->default('Easy');
    $table->string('grade_bracket')->nullable();
    $table->string('age_bracket')->nullable();
$table->text('meaning')->nullable();
$table->text('sentence')->nullable(); // with * instead of the word
$table->string('audio_url')->nullable();
    $table->timestamps();
});