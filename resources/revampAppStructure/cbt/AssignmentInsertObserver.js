Step 4: Create an Observer

Run this in terminal:

php artisan make:observer AssignmentScoreObserver --model=AssignmentScore

Then open app/Observers/AssignmentScoreObserver.php and add:

namespace App\Observers;
use App\Models\Grade;

class GradeObserver
{
    /**
     * Handle the Grade "saving" event.
     */
    public function saving(Grade $grade)
    {
    // only recompute when assignments column changed
    if (! $grade->isDirty('assignments')) {
        return; // nothing relevant ch
    }
           // currentTerm get current term from redis  
        
    // Check if result is compile
    $isLocked = \DB::table('grade')
        ->where('currentTerm', $term)
        ->where('schoolId', $schId)
        ->where('compi', true)
        ->exists();

    if ($isLocked) {
        // Do nothing; results are finalized
        return;
    }
        
        // Skip if no assignments
        if (empty($grade->assignments)) {
            $grade->assignment = 0;
            return;
        }

        // Decode (Laravel will auto-cast if you used $casts)
        $assignments = $grade->assignments;

        $totalScore = 0;
        $totalPossible = 0;

        foreach ($assignments as $item) {
            // Each $item is like: [ 'score' => 8, 'total' => 10 ]
            $score = floatval($item['score'] ?? 0);
            $possible = floatval($item['total'] ?? 0);

            $totalScore += $score;
            $totalPossible += $possible;
        }

        // Avoid division by zero
        if ($totalPossible > 0) {
            $compressed = ($totalScore / $totalPossible) * 10;
            $grade->assignment = round($compressed, 2); // e.g. 7.43
        } else {
            $grade->assignment = 0;
        }
    }
}
    

---

🪄 Step 5: Register the Observer

In App\Providers\EventServiceProvider.php, inside boot():

use App\Models\AssignmentScore;
use App\Observers\AssignmentScoreObserver;

public function boot()
{
    AssignmentScore::observe(AssignmentScoreObserver::class);
}



