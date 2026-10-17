<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case CorrectionRequired = 'correction_required';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under review',
            self::CorrectionRequired => 'Correction required',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    /**
     * @return array<int, self>
     */
    public function officerTransitions(): array
    {
        return match ($this) {
            self::Submitted => [self::UnderReview],
            self::UnderReview => [self::CorrectionRequired, self::Approved, self::Rejected],
            self::CorrectionRequired, self::Approved, self::Rejected => [],
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, $this->officerTransitions(), true);
    }

    public function requiresRemark(): bool
    {
        return in_array($this, [self::CorrectionRequired, self::Rejected], true);
    }
}
