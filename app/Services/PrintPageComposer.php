<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PrintPageComposer
{
    public function compose(Collection $testimonials): array
    {
        $letters = [];
        $totalPages = 0;

        foreach ($testimonials as $testimonial) {
            $segments = $this->splitMessage($testimonial->message, (bool) $testimonial->photo_path);
            $pageCount = count($segments);
            $totalPages += $pageCount;
            $letters[] = [
                'testimonial' => $testimonial,
                'segments' => $segments,
                'page_count' => $pageCount,
            ];
        }

        return ['letters' => $letters, 'total_pages' => $totalPages];
    }

    private function splitMessage(string $message, bool $hasImage): array
    {
        $message = trim(preg_replace('/\R{3,}/u', "\n\n", $message) ?? $message);
        $firstCapacity = $hasImage ? 1150 : 2300;
        $nextCapacity = 2500;
        $segments = [];
        $remaining = $message;
        $capacity = $firstCapacity;

        while (mb_strlen($remaining) > $capacity) {
            $slice = mb_substr($remaining, 0, $capacity + 1);
            $break = max((int) mb_strrpos($slice, "\n"), (int) mb_strrpos($slice, ' '));
            $break = $break > (int) ($capacity * .65) ? $break : $capacity;
            $segments[] = trim(mb_substr($remaining, 0, $break));
            $remaining = trim(mb_substr($remaining, $break));
            $capacity = $nextCapacity;
        }

        $segments[] = $remaining;

        return $segments;
    }
}
