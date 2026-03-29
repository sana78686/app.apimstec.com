<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Page;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContentOptimizationController extends Controller
{
    /**
     * Content Optimization Tools: keyword suggestions, readability & heading checks.
     */
    public function index(): Response
    {
        $pages = Page::orderBy('title')->get(['id', 'title', 'slug'])->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'slug' => $p->slug,
            'type' => 'page',
        ]);

        $blogs = Blog::orderBy('title')->get(['id', 'title', 'slug'])->map(fn ($b) => [
            'id' => $b->id,
            'title' => $b->title,
            'slug' => $b->slug,
            'type' => 'blog',
        ]);

        $items = $pages->concat($blogs)->sortBy('title')->values()->all();

        return Inertia::render('Seo/ContentOptimization/Index', [
            'items' => $items,
        ]);
    }

    /**
     * Analyze a page or blog: readability, headings, keyword.
     */
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'type' => ['required', 'string', 'in:page,blog'],
            'id' => ['required', 'integer', 'min:1'],
        ]);

        $type = $request->input('type');
        $id = (int) $request->input('id');

        if ($type === 'page') {
            $model = Page::find($id);
        } else {
            $model = Blog::find($id);
        }

        if (! $model) {
            return response()->json(['message' => 'Content not found.'], 404);
        }

        $content = $model->content ?? '';
        $title = $model->title ?? '';
        $metaTitle = $model->meta_title ?? $title;
        $metaDescription = $model->meta_description ?? '';
        $focusKeyword = $type === 'page' ? ($model->focus_keyword ?? '') : '';

        $plainText = strip_tags($content);
        $readability = $this->readability($plainText);
        $headings = $this->headings($content);
        $keyword = $this->keywordAnalysis($plainText, $title, $metaTitle, $metaDescription, $focusKeyword);

        return response()->json([
            'title' => $title,
            'type' => $type,
            'readability' => $readability,
            'headings' => $headings,
            'keyword' => $keyword,
        ]);
    }

    private function readability(string $text): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        $wordCount = $text === '' ? 0 : count(preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY));
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentenceCount = count(array_filter($sentences, fn ($s) => trim($s) !== ''));
        if ($sentenceCount === 0) {
            $sentenceCount = 1;
        }
        $avgWordsPerSentence = $wordCount / $sentenceCount;

        $syllables = 0;
        foreach (preg_split('/\s+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            $syllables += max(1, (int) preg_match_all('/[aeiouy]+/', $word));
        }
        $syllablesPerWord = $wordCount > 0 ? $syllables / $wordCount : 0;

        $flesch = 206.835 - (1.015 * $avgWordsPerSentence) - (84.6 * $syllablesPerWord);
        $flesch = round(max(0, min(100, $flesch)), 1);

        $level = 'Good';
        if ($flesch < 30) {
            $level = 'Hard';
        } elseif ($flesch < 50) {
            $level = 'Fairly hard';
        } elseif ($flesch < 60) {
            $level = 'Standard';
        } elseif ($flesch < 70) {
            $level = 'Fairly easy';
        } else {
            $level = 'Easy';
        }

        return [
            'word_count' => $wordCount,
            'sentence_count' => $sentenceCount,
            'avg_words_per_sentence' => round($avgWordsPerSentence, 1),
            'flesch_reading_ease' => $flesch,
            'readability_level' => $level,
            'suggestions' => $this->readabilitySuggestions($wordCount, $avgWordsPerSentence, $flesch),
        ];
    }

    private function readabilitySuggestions(int $wordCount, float $avgWords, float $flesch): array
    {
        $suggestions = [];
        if ($wordCount < 300) {
            $suggestions[] = 'Add more content (aim for at least 300 words for better SEO).';
        }
        if ($avgWords > 20) {
            $suggestions[] = 'Use shorter sentences for easier reading (aim for under 20 words per sentence).';
        }
        if ($flesch < 50 && $flesch > 0) {
            $suggestions[] = 'Consider simplifying language to improve readability score.';
        }
        if (empty($suggestions)) {
            $suggestions[] = 'Readability looks good.';
        }
        return $suggestions;
    }

    private function headings(string $html): array
    {
        $list = [];
        $issues = [];

        // Match H1–H6 even when rich text editor wraps content in <p> or other tags (e.g. <h1><p>Title</p></h1>)
        if (preg_match_all('/<h([1-6])(?:\s[^>]*)?>(.*?)<\/h\1>/is', $html, $m, PREG_SET_ORDER)) {
            $prevLevel = 0;
            foreach ($m as $i => $match) {
                $level = (int) $match[1];
                $text = trim(strip_tags($match[2]));
                $list[] = ['level' => $level, 'text' => $text];

                if ($level === 1 && $i > 0) {
                    $issues[] = 'Multiple H1 headings found. Use a single H1 per page.';
                }
                if ($prevLevel > 0 && $level > $prevLevel + 1) {
                    $issues[] = "Skipped heading level: H{$prevLevel} to H{$level}.";
                }
                $prevLevel = $level;
            }
            $h1Count = count(array_filter($list, fn ($h) => $h['level'] === 1));
            if ($h1Count === 0 && ! empty($list)) {
                $issues[] = 'No H1 heading. Add one main heading for the page.';
            }
        } else {
            $issues[] = 'No headings (H1–H6) found. Add structure with headings.';
        }

        return [
            'headings' => $list,
            'issues' => array_values(array_unique($issues)),
        ];
    }

    private function keywordAnalysis(string $plainText, string $title, string $metaTitle, string $metaDescription, string $focusKeyword): array
    {
        $suggestions = [];
        $inTitle = false;
        $inMeta = false;
        $density = null;
        $count = 0;

        if ($focusKeyword !== '') {
            $kw = strtolower($focusKeyword);
            $inTitle = str_contains(strtolower($title), $kw);
            $inMeta = str_contains(strtolower($metaTitle), $kw) || str_contains(strtolower($metaDescription), $kw);
            $text = strtolower($plainText);
            $count = preg_match_all('/\b'.preg_quote($kw, '/').'\b/', $text);
            $wordCount = count(preg_split('/\s+/', trim($plainText), -1, PREG_SPLIT_NO_EMPTY));
            $density = $wordCount > 0 ? round(($count / $wordCount) * 100, 2) : 0;

            if (! $inTitle && ! $inMeta) {
                $suggestions[] = 'Add focus keyword to title or meta description.';
            }
            if ($inTitle && ! $inMeta) {
                $suggestions[] = 'Consider including focus keyword in meta description.';
            }
            if ($count === 0) {
                $suggestions[] = 'Focus keyword does not appear in content. Use it naturally in the body.';
            } elseif ($density < 0.5) {
                $suggestions[] = 'Keyword density is low. Use focus keyword a few more times (naturally).';
            } elseif ($density > 2.5) {
                $suggestions[] = 'Keyword density is high. Avoid overstuffing; keep it natural.';
            }
            if (empty($suggestions)) {
                $suggestions[] = 'Keyword usage looks good.';
            }
        } else {
            $suggestions[] = 'Set a focus keyword in Meta Manager (pages) for keyword checks.';
        }

        return [
            'focus_keyword' => $focusKeyword,
            'in_title' => $inTitle,
            'in_meta' => $inMeta,
            'count_in_content' => $count,
            'density_percent' => $density,
            'suggestions' => $suggestions,
        ];
    }
}
