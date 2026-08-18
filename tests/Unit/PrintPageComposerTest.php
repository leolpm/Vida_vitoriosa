<?php

namespace Tests\Unit;

use App\Models\Testimonial;
use App\Services\PrintPageComposer;
use PHPUnit\Framework\TestCase;

class PrintPageComposerTest extends TestCase
{
    public function test_it_counts_short_and_long_letters_without_losing_unicode(): void
    {
        $short = new Testimonial(['sender_name' => 'Curta', 'message' => 'Mensagem com fé 🙏 e carinho.', 'photo_path' => null]);
        $long = new Testimonial(['sender_name' => 'Longa', 'message' => str_repeat('Palavra com acento e emoji ❤️ ', 250), 'photo_path' => 'photo.jpg']);

        $result = (new PrintPageComposer)->compose(collect([$short, $long]));

        $this->assertSame(1, $result['letters'][0]['page_count']);
        $this->assertGreaterThan(1, $result['letters'][1]['page_count']);
        $this->assertStringContainsString('❤️', implode(' ', $result['letters'][1]['segments']));
        $this->assertSame(array_sum(array_column($result['letters'], 'page_count')), $result['total_pages']);
    }
}
