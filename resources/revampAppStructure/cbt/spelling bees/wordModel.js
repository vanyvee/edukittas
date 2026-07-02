
// app/Models/Word.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Word extends Model
{
    use HasFactory;

    protected $fillable = [
        'word', 'phonetic', 'frequency', 'morphology',
        'letter_pattern', 'length_modifier', 'total_score', 'difficulty'
    ];
}
