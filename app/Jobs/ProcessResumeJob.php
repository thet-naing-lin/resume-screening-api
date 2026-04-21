<?php

namespace App\Jobs;

use App\Models\Resume;
use App\Models\Candidate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

// PDF parser
use Smalot\PdfParser\Parser as PdfParser;

// DOCX parser
use PhpOffice\PhpWord\IOFactory;

class ProcessResumeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // If job fails, retry up to 3 times
    public int $tries = 3;

    // Timeout after 2 minutes
    public int $timeout = 120;

    public function __construct(public Resume $resume) {}

    public function handle(): void
    {
        // Override memory limit for this job only
        ini_set('memory_limit', '512M');

        // ── STAGE 1: Mark as parsing ─────────────────────────────
        $this->resume->update(['status' => 'parsing']);

        try {
            // ── STAGE 2: Get the file from private storage ───────
            $filePath = Storage::disk('private')
                ->path('resumes/' . $this->resume->stored_filename);

            if (!file_exists($filePath)) {
                throw new \Exception("File not found: {$this->resume->stored_filename}");
            }

            // ── STAGE 3: Extract raw text based on file type ─────
            $rawText = match ($this->resume->file_type) {
                'pdf'  => $this->extractFromPdf($filePath),
                'docx' => $this->extractFromDocx($filePath),
                default => throw new \Exception("Unsupported file type: {$this->resume->file_type}"),
            };

            // ── STAGE 4: Clean up the extracted text ─────────────
            $cleanText = $this->cleanText($rawText);

            if (empty(trim($cleanText))) {
                throw new \Exception("No readable text could be extracted from this file.");
            }

            // ── STAGE 5: Parse candidate info from text ──────────
            $parsedData = $this->parseCandidate($cleanText);

            // ── STAGE 6: Find or create Candidate record ─────────
            $candidate = $this->upsertCandidate($parsedData);

            // ── STAGE 7: Save everything to the resume record ────
            $this->resume->update([
                'raw_text'    => $cleanText,
                'parsed_data' => $parsedData,
                'candidate_id' => $candidate?->id,
                'status'      => 'parsed',
                'parse_error' => null,
            ]);

            // Kick off scoring pipeline
            // ComputeResumeScoreJob::dispatch($resume)->delay(now()->addSeconds(2));
            ComputeResumeScoreJob::dispatch($this->resume)->delay(now()->addSeconds(2));

            Log::info("Resume #{$this->resume->id} parsed successfully.");
        } catch (\Exception $e) {
            // ── ON FAILURE: Save error, mark as failed ───────────
            $this->resume->update([
                'status'      => 'failed',
                'parse_error' => $e->getMessage(),
            ]);

            Log::error("Resume #{$this->resume->id} parsing failed: " . $e->getMessage());

            // Re-throw so Laravel marks the job as failed in the DB
            throw $e;
        }
    }

    // ── PDF EXTRACTOR ─────────────────────────────────────────────
    private function extractFromPdf(string $filePath): string
    {
        // Warn if file is suspiciously large (over 10MB — likely not a resume)
        $fileSizeMB = filesize($filePath) / 1024 / 1024;
        if ($fileSizeMB > 10) {
            throw new \Exception("PDF file is too large ({$fileSizeMB}MB). Maximum allowed is 10MB.");
        }

        $parser   = new PdfParser();
        $pdf      = $parser->parseFile($filePath);
        $text     = $pdf->getText();

        return $text;
    }

    // ── DOCX EXTRACTOR ────────────────────────────────────────────
    private function extractFromDocx(string $filePath): string
    {
        // Warn if file is suspiciously large (over 10MB — likely not a resume)
        $fileSizeMB = filesize($filePath) / 1024 / 1024;
        if ($fileSizeMB > 10) {
            throw new \Exception("DOCX file is too large ({$fileSizeMB}MB). Maximum allowed is 10MB.");
        }

        $phpWord  = IOFactory::load($filePath);
        $text     = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->getElementText($element);
            }
        }

        return $text;
    }

    // Recursively extract text from DOCX elements (paragraphs, tables, etc.)
    private function getElementText($element): string
    {
        $text = '';

        // Text run inside a paragraph
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $child) {
                $text .= $this->getElementText($child);
            }
            $text .= "\n";
        }
        // Plain text element
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text .= $element->getText() . ' ';
        }
        // Paragraph
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Paragraph) {
            foreach ($element->getElements() as $child) {
                $text .= $this->getElementText($child);
            }
            $text .= "\n";
        }
        // Table — loop through rows and cells
        elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $cellElement) {
                        $text .= $this->getElementText($cellElement);
                    }
                    $text .= "\t";
                }
                $text .= "\n";
            }
        }

        return $text;
    }

    // ── TEXT CLEANER ──────────────────────────────────────────────
    private function cleanText(string $raw): string
    {
        // Remove null bytes and non-printable characters
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $raw);

        // Normalize multiple spaces to single space
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Normalize multiple newlines to max 2
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        // Trim each line
        $lines = array_map('trim', explode("\n", $text));
        $text  = implode("\n", array_filter($lines, fn($l) => $l !== ''));

        return trim($text);
    }

    // ── CANDIDATE PARSER ──────────────────────────────────────────
    // US-010 lives here — extract name, email, phone from raw text
    private function parseCandidate(string $text): array
    {
        return [
            'email'            => $this->extractEmail($text),
            'phone'            => $this->extractPhone($text),
            'name'             => $this->extractName($text),
            'raw_skills'       => $this->extractSkills($text),
            'experience_years' => $this->extractExperienceYears($text),
        ];
    }

    private function extractEmail(string $text): ?string
    {
        preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $text, $matches);
        return $matches[0] ?? null;
    }

    // private function extractEmail(string $text): ?string
    // {
    //     // Normalize — remove markdown link artifacts like [email](mailto:email)
    //     // PDF parsers sometimes produce these
    //     $text = preg_replace('/\[([^\]]+)\]\(mailto:[^\)]+\)/', '$1', $text);

    //     // Match all valid emails — standard regex
    //     preg_match_all(
    //         '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/',
    //         $text,
    //         $matches
    //     );

    //     if (empty($matches[0])) {
    //         return null;
    //     }

    //     foreach ($matches[0] as $email) {
    //         $atPos     = strpos($email, '@');
    //         $localPart = substr($email, 0, $atPos);
    //         $domain    = substr($email, $atPos + 1);

    //         // Walk the local part from right-to-left
    //         // Real email usernames don't start with an uppercase letter
    //         // when they're purely personal emails (gmail, yahoo, etc.)
    //         // Find where lowercase-only part begins
    //         $cleanLocal = $this->extractCleanLocal($localPart);

    //         $candidate = strtolower($cleanLocal . '@' . $domain);

    //         if (filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
    //             return $candidate;
    //         }
    //     }

    //     return null;
    // }

    // private function extractCleanLocal(string $localPart): string
    // {
    //     // Strategy: scan from left, find first position where the text
    //     // transitions from "looks like a word/name" to "looks like an email username"
    //     //
    //     // Signs of a merged prefix:
    //     // - Starts with uppercase followed by lowercase letters (proper noun = city/name)
    //     // - Then continues with more lowercase + numbers (email pattern)
    //     //
    //     // Example: "Yangoncholwinthant5"
    //     //           ^^^^^^ = proper noun prefix
    //     //                 ^^^^^^^^^^^^^^ = actual email local part

    //     // Pattern: one or more proper-noun-like words (Capital + lowercase)
    //     // followed by the actual email username (starts with lowercase or digit)
    //     if (preg_match('/^(?:[A-Z][a-z]+)+([a-z0-9][a-z0-9._%+\-]*)$/', $localPart, $m)) {
    //         // $m[1] is the part after all the proper noun prefix(es)
    //         if (strlen($m[1]) >= 4) {
    //             return $m[1];
    //         }
    //     }

    //     return $localPart;
    // }

    private function extractPhone(string $string): ?string
    {
        // Matches common formats: +95 9 123456789, 09-1234-5678, (09)12345678, etc.
        preg_match(
            '/(\+?\d{1,3}[\s\-]?)?(\(?\d{2,4}\)?[\s\-]?)(\d{3,4}[\s\-]?\d{3,4}[\s\-]?\d{0,4})/',
            $string,
            $matches
        );
        return isset($matches[0]) && strlen(trim($matches[0])) >= 7
            ? trim($matches[0])
            : null;
    }

    private function extractName(string $text): ?string
    {
        // Strategy: take the first non-empty line of the resume
        // Most resumes put the candidate's name at the very top
        $lines = explode("\n", trim($text));

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty, too short, or lines that look like emails/phones/URLs
            if (
                strlen($line) < 3 ||
                strlen($line) > 60 ||
                str_contains($line, '@') ||
                str_contains($line, 'http') ||
                preg_match('/\d{5,}/', $line)   // long number = not a name
            ) {
                continue;
            }

            // Only letters, spaces, dots, hyphens (a real name)
            if (preg_match('/^[a-zA-Z\s.\-\']+$/', $line)) {
                return $line;
            }
        }

        return null;
    }

    private function extractSkills(string $text): array
    {
        // Common tech skills keyword list — expand as needed for your domain
        $skillKeywords = [
            // Languages
            'PHP',
            'Python',
            'JavaScript',
            'TypeScript',
            'Java',
            'C++',
            'C#',
            'Ruby',
            'Swift',
            'Kotlin',
            'Go',
            'Rust',
            'R',
            'MATLAB',
            'SQL',
            // Frontend
            'React',
            'Vue',
            'Angular',
            'HTML',
            'CSS',
            'Tailwind',
            'Bootstrap',
            'Next.js',
            'Nuxt',
            'jQuery',
            'SASS',
            'SCSS',
            // Backend
            'Laravel',
            'Django',
            'Flask',
            'Express',
            'Node.js',
            'Spring',
            'FastAPI',
            'Rails',
            'CodeIgniter',
            'Symfony',
            // Databases
            'MySQL',
            'PostgreSQL',
            'MongoDB',
            'Redis',
            'SQLite',
            'Oracle',
            'MariaDB',
            'Firebase',
            'Supabase',
            'Elasticsearch',
            // DevOps / Tools
            'Docker',
            'Kubernetes',
            'AWS',
            'Azure',
            'GCP',
            'Git',
            'GitHub',
            'GitLab',
            'CI/CD',
            'Linux',
            'Nginx',
            'Apache',
            // AI / Data
            'TensorFlow',
            'PyTorch',
            'Pandas',
            'NumPy',
            'Scikit-learn',
            'Machine Learning',
            'Deep Learning',
            'NLP',
            'Computer Vision',
        ];

        $foundSkills = [];
        $textLower   = strtolower($text);

        foreach ($skillKeywords as $skill) {
            // Case-insensitive whole-word match
            if (preg_match('/\b' . preg_quote(strtolower($skill), '/') . '\b/', $textLower)) {
                $foundSkills[] = $skill;
            }
        }

        return array_values(array_unique($foundSkills));
    }

    // option - 1 | not accurate enough — just looks for "X years" patterns, but many resumes don't state experience like that
    // private function extractExperienceYears(string $text): ?int
    // {
    //     // Match patterns like: "5 years experience", "3+ years", "2 years of"
    //     preg_match(
    //         '/(\d+)\+?\s*years?\s*(of\s*)?(experience|work|professional)/i',
    //         $text,
    //         $matches
    //     );

    //     if (isset($matches[1])) {
    //         return (int) $matches[1];
    //     }

    //     // Fallback: count unique years mentioned (2019, 2020, 2021 = ~2 years exp)
    //     preg_match_all('/\b(19|20)\d{2}\b/', $text, $yearMatches);
    //     if (!empty($yearMatches[0])) {
    //         $years = array_unique($yearMatches[0]);
    //         sort($years);
    //         $span = (int) end($years) - (int) reset($years);
    //         return $span > 0 ? $span : null;
    //     }

    //     return null;
    // }

    // option - 2 | more accurate — looks for date ranges like "2019-2021", "Jan 2020 – Present" and sums them up (but seem like not accurate enough too)
    private function extractExperienceYears(string $text): ?int
    {
        // ── Strategy 1: Explicit statement ───────────────────────────
        // Matches: "5 years experience", "3+ years of work", "over 2 years"
        $patterns = [
            '/(\d+)\+?\s*years?\s*(of\s*)?(experience|work|professional|industry)/i',
            '/(over|more than|nearly|almost)\s+(\d+)\s*years?/i',
            '/(\d+)\s*[-–]\s*(\d+)\s*years?\s*(of\s*)?experience/i', // "3-5 years experience"
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // For range pattern "3-5 years", take the lower number
                $num = isset($matches[2]) && is_numeric($matches[2])
                    ? (int) $matches[2]
                    : (int) $matches[1];

                if ($num > 0 && $num < 50) { // sanity check
                    return $num;
                }
            }
        }

        // ── Strategy 2: Calculate from work history dates ────────────
        // Look for date ranges like "2019 - 2022", "Jan 2020 – Present"
        // This is more reliable than just finding loose year numbers

        $workYears = [];

        // Match year ranges: 2018 - 2021, 2020–2023, 2019 to 2022
        preg_match_all(
            '/\b(20\d{2}|19\d{2})\s*[-–—to]+\s*(20\d{2}|19\d{2}|present|current|now)\b/i',
            $text,
            $rangeMatches,
            PREG_SET_ORDER
        );

        $currentYear = (int) date('Y');

        foreach ($rangeMatches as $match) {
            $startYear = (int) $match[1];
            $endRaw    = strtolower(trim($match[2]));

            $endYear = in_array($endRaw, ['present', 'current', 'now'])
                ? $currentYear
                : (int) $match[2];

            if ($startYear >= 1980 && $endYear <= $currentYear + 1 && $endYear >= $startYear) {
                $workYears[] = ['start' => $startYear, 'end' => $endYear];
            }
        }

        if (!empty($workYears)) {
            // Add up all work periods (handles gaps in employment)
            $totalMonths = 0;
            foreach ($workYears as $period) {
                $totalMonths += ($period['end'] - $period['start']) * 12;
            }
            $totalYears = (int) round($totalMonths / 12);
            return $totalYears > 0 ? min($totalYears, 50) : null;
        }

        // ── Strategy 3: Give up — return null ────────────────────────
        // Better to return null than return a wrong number
        return null;
    }

    // ── CANDIDATE UPSERT ──────────────────────────────────────────
    // Find existing candidate by email or create a new one
    private function upsertCandidate(array $parsed): ?Candidate
    {
        if (empty($parsed['email'])) {
            // No email found — can't reliably identify candidate
            return null;
        }

        $candidate = Candidate::firstOrCreate(
            // Find by email (unique identifier)
            ['email' => $parsed['email']],
            // Only set these on CREATE — don't overwrite existing data
            [
                'name'             => $parsed['name'],
                'phone'            => $parsed['phone'],
                // 'extracted_skills' => $parsed['raw_skills'],
                // 'experience_years' => $parsed['experience_years'],
            ]
        );

        return $candidate;
    }
}
