<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $fillable = [
        'resident_id',
        'branch_id',
        'assessor_id',
        'assessment_type',
        'assessment_date',
        'status',
        'notes',
        'scores',
        'recommendations',
        'completed_at',
        'reviewed_at',
        'approved_at',
    ];

    protected $casts = [
        'assessment_date' => 'date',
        'scores' => 'array',
        'recommendations' => 'array',
        'completed_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AssessmentSection::class);
    }

    // Scopes
    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('assessment_type', $type);
    }

    // Accessors
    public function getCompletionPercentageAttribute()
    {
        // Calculate based on answered questions for more accurate partial progress
        $totalQuestions = $this->sections()
            ->with('questions')
            ->get()
            ->flatMap(fn($section) => $section->questions)
            ->count();
            
        if ($totalQuestions === 0) {
            return 0;
        }
        
        $answeredQuestions = $this->sections()
            ->with('questions')
            ->get()
            ->flatMap(fn($section) => $section->questions)
            ->whereNotNull('response_value')
            ->where('response_value', '!=', '')
            ->count();
        
        return round(($answeredQuestions / $totalQuestions) * 100, 2);
    }

    public function getIsCompletedAttribute()
    {
        return $this->status === 'approved';
    }

    /**
     * Calculate scores based on assessment answers
     */
    public function calculateScores(): array
    {
        $scores = [
            'physical_health' => 0,
            'mental_health' => 0,
            'social_wellbeing' => 0,
            'cognitive_function' => 0,
            'mobility' => 0,
            'nutrition' => 0,
            'medication_compliance' => 0,
            'safety' => 0,
        ];

        $counts = [
            'physical_health' => 0,
            'mental_health' => 0,
            'social_wellbeing' => 0,
            'cognitive_function' => 0,
            'mobility' => 0,
            'nutrition' => 0,
            'medication_compliance' => 0,
            'safety' => 0,
        ];

        // Load sections with questions
        $this->load(['sections.questions']);

        foreach ($this->sections as $section) {
            $sectionType = $section->section_type;
            
            foreach ($section->questions as $question) {
                $responseValue = $question->response_value;
                
                if ($responseValue === null || $responseValue === '') {
                    continue; // Skip unanswered questions
                }

                // Map questions to score categories based on section type and question text
                $this->scoreQuestion($sectionType, $question, $responseValue, $scores, $counts);
            }
        }

        // Calculate averages for each category
        foreach ($scores as $category => $total) {
            if ($counts[$category] > 0) {
                $scores[$category] = round(($total / $counts[$category]), 0);
            } else {
                // If no answers for this category, set to neutral (75)
                $scores[$category] = 75;
            }
        }

        // Calculate overall score as weighted average
        $weights = [
            'physical_health' => 0.15,
            'mental_health' => 0.15,
            'cognitive_function' => 0.15,
            'mobility' => 0.15,
            'safety' => 0.15,
            'medication_compliance' => 0.10,
            'nutrition' => 0.10,
            'social_wellbeing' => 0.05,
        ];

        $overallScore = 0;
        foreach ($weights as $category => $weight) {
            $overallScore += $scores[$category] * $weight;
        }

        $scores['overall_score'] = round($overallScore, 0);

        return $scores;
    }

    /**
     * Score an individual question based on its answer
     */
    private function scoreQuestion(string $sectionType, $question, $responseValue, array &$scores, array &$counts): void
    {
        $questionText = strtolower($question->question_text);
        $responseType = $question->response_type;
        
        // Normalize response value
        $normalizedValue = $this->normalizeResponseValue($responseValue, $responseType);

        // Score based on section type
        switch ($sectionType) {
            case 'demographic':
                // Demographic info doesn't typically score, but can affect social wellbeing
                if (strpos($questionText, 'married') !== false || strpos($questionText, 'education') !== false) {
                    $this->addToScore('social_wellbeing', $normalizedValue, $scores, $counts, 10);
                }
                break;

            case 'medical_history':
                if (strpos($questionText, 'chronic condition') !== false || strpos($questionText, 'medication') !== false) {
                    $this->addToScore('physical_health', $normalizedValue === 'yes' ? 30 : 85, $scores, $counts, 100);
                    $this->addToScore('medication_compliance', $normalizedValue === 'yes' ? 80 : 90, $scores, $counts, 100);
                }
                if (strpos($questionText, 'allerg') !== false) {
                    $this->addToScore('safety', $normalizedValue === 'yes' ? 60 : 95, $scores, $counts, 100);
                }
                if (strpos($questionText, 'surger') !== false) {
                    $this->addToScore('physical_health', $normalizedValue === 'yes' ? 40 : 85, $scores, $counts, 100);
                }
                break;

            case 'functional':
                if (strpos($questionText, 'walk') !== false || strpos($questionText, 'mobility') !== false) {
                    $this->addToScore('mobility', $normalizedValue === 'yes' ? 85 : 30, $scores, $counts, 100);
                }
                if (strpos($questionText, 'adl') !== false || strpos($questionText, 'activities of daily living') !== false) {
                    $this->addToScore('physical_health', $normalizedValue === 'yes' ? 80 : 45, $scores, $counts, 100);
                    $this->addToScore('mobility', $normalizedValue === 'yes' ? 85 : 40, $scores, $counts, 100);
                }
                if (strpos($questionText, 'transfer') !== false) {
                    $this->addToScore('mobility', $normalizedValue === 'yes' ? 90 : 35, $scores, $counts, 100);
                    $this->addToScore('safety', $normalizedValue === 'yes' ? 85 : 50, $scores, $counts, 100);
                }
                if (strpos($questionText, 'bath') !== false) {
                    $this->addToScore('physical_health', $normalizedValue === 'yes' ? 85 : 50, $scores, $counts, 100);
                }
                if (strpos($questionText, 'dress') !== false) {
                    $this->addToScore('physical_health', $normalizedValue === 'yes' ? 80 : 45, $scores, $counts, 100);
                }
                break;

            case 'cognitive':
                if (strpos($questionText, 'alert') !== false || strpos($questionText, 'oriented') !== false) {
                    $this->addToScore('cognitive_function', $normalizedValue === 'yes' ? 90 : 40, $scores, $counts, 100);
                }
                if (strpos($questionText, 'memory') !== false || strpos($questionText, 'dementia') !== false || strpos($questionText, 'alzheimer') !== false) {
                    $this->addToScore('cognitive_function', $normalizedValue === 'yes' ? 35 : 85, $scores, $counts, 100);
                }
                if (strpos($questionText, 'decision') !== false || strpos($questionText, 'independently') !== false) {
                    $this->addToScore('cognitive_function', $normalizedValue === 'yes' ? 85 : 45, $scores, $counts, 100);
                    $this->addToScore('mental_health', $normalizedValue === 'yes' ? 80 : 50, $scores, $counts, 100);
                }
                break;

            case 'behavioral':
                if (strpos($questionText, 'challenging behavior') !== false || strpos($questionText, 'behavioral concern') !== false) {
                    $this->addToScore('mental_health', $normalizedValue === 'yes' ? 45 : 85, $scores, $counts, 100);
                }
                if (strpos($questionText, 'cooperative') !== false) {
                    $this->addToScore('mental_health', $normalizedValue === 'yes' ? 85 : 50, $scores, $counts, 100);
                    $this->addToScore('social_wellbeing', $normalizedValue === 'yes' ? 80 : 45, $scores, $counts, 100);
                }
                if (strpos($questionText, 'redirection') !== false) {
                    $this->addToScore('cognitive_function', $normalizedValue === 'yes' ? 50 : 80, $scores, $counts, 100);
                    $this->addToScore('mental_health', $normalizedValue === 'yes' ? 55 : 85, $scores, $counts, 100);
                }
                break;

            case 'nutritional':
                $this->addToScore('nutrition', $normalizedValue === 'yes' ? 85 : 50, $scores, $counts, 100);
                break;

            case 'environmental':
                $this->addToScore('safety', $normalizedValue === 'yes' ? 85 : 60, $scores, $counts, 100);
                break;

            case 'risk':
                if (strpos($questionText, 'fall') !== false) {
                    // For fall risk, map Low=95, Moderate=70, High=45, Very High=25
                    $fallScores = ['low' => 95, 'moderate' => 70, 'high' => 45, 'very high' => 25];
                    $score = $fallScores[strtolower($normalizedValue)] ?? 70;
                    $this->addToScore('safety', $score, $scores, $counts, 100);
                    $this->addToScore('mobility', $score, $scores, $counts, 100);
                }
                if (strpos($questionText, 'medication') !== false && strpos($questionText, 'compliance') !== false) {
                    $complianceScores = ['excellent' => 95, 'good' => 80, 'fair' => 60, 'poor' => 35];
                    $score = $complianceScores[strtolower($normalizedValue)] ?? 75;
                    $this->addToScore('medication_compliance', $score, $scores, $counts, 100);
                }
                break;
        }
    }

    /**
     * Normalize response value to a comparable format
     */
    private function normalizeResponseValue($value, string $responseType): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if ($responseType === 'yes_no' || $responseType === 'boolean') {
            if ($value === true || $value === 'true' || $value === '1' || strtolower($value) === 'yes') {
                return 'yes';
            }
            if ($value === false || $value === 'false' || $value === '0' || strtolower($value) === 'no') {
                return 'no';
            }
        }

        return strtolower(trim((string)$value));
    }

    /**
     * Add a score value to a category
     */
    private function addToScore(string $category, float $value, array &$scores, array &$counts, float $maxValue = 100): void
    {
        // Normalize value to 0-100 scale
        $normalizedValue = min(100, max(0, ($value / $maxValue) * 100));
        $scores[$category] += $normalizedValue;
        $counts[$category]++;
    }
}
