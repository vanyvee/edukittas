namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherTestComputation extends Model
{
    protected $fillable = [
    
       'schId', 'student_id', 'subject_id', 
    'answer','questionText',
    'assignment_id',
    'score', 'total'
    ];
}

php artisan make:observer AssignmentScoreObserver --model=AssignmentScore

namespace App\Observers;

use App\Models\AssignmentScore;
use App\Models\Grade;

class AssignmentScoreObserver
{
    public function recalculateGrade($assignmentScore)
    {
    // Check if result is locked
    $isLocked = \DB::table('term')->where('isCompiled', true)->exists();
    if ($isLocked) {
        // Do nothing; results are finalized
        return;
    }
    // Continue with normal recalculation...
        $studentId = $assignmentScore->student_id;
        $subjectId = $assignmentScore->subject_id;
        // Get all assignment scores for that student & subject
        $assignments = AssignmentScore::where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->get();

        $totalScore = $assignments->sum('score');
        $totalPossible = $assignments->sum('total');

        $compressed = 0;
        if ($totalPossible > 0) {
            $compressed = ($totalScore / $totalPossible) * 10; // weighted formula
        }

        // Update or create the grade record
        Grade::updateOrCreate(
            ['student_id' => $studentId, 'subject_id' => $subjectId],
            ['assignment' => round($compressed, 2)]
        );
    }

    public function created(AssignmentScore $assignmentScore)
    {
        $this->recalculateGrade($assignmentScore);
    }

    public function updated(AssignmentScore $assignmentScore)
    {
        $this->recalculateGrade($assignmentScore);
    }

    public function deleted(AssignmentScore $assignmentScore)
    {
        $this->recalculateGrade($assignmentScore);
    }
}





Step 5: Register the Observer

In App\Providers\EventServiceProvider.php, inside boot():

use App\Models\AssignmentScore;
use App\Observers\AssignmentScoreObserver;

public function boot()
{
    AssignmentScore::observe(AssignmentScoreObserver::class);
}



