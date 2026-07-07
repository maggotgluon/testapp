<?php

namespace App\Http\Controllers;

use App\Models\Survey;
use App\Services\SurveyGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function show(Request $request, Survey $survey, SurveyGate $gate): View
    {
        abort_unless($survey->is_active, 404);

        if ($request->filled('return')) {
            $gate->rememberReturn($survey, $request, (string) $request->query('return'));
        }

        if ($gate->hasCompleted($survey, $request)) {
            return view('surveys.completed', [
                'survey' => $survey,
                'returnTo' => $gate->returnTo($survey, $request),
            ]);
        }

        $response = $gate->responseFor($survey, $request);

        // Pre-fill survey answers from checkout session or authenticated user
        if (empty($response->answers)) {
            $prefill = $this->buildPrefillAnswers($survey, $request);
            if (! empty($prefill)) {
                $response->answers = $prefill;
            }
        }

        return view('surveys.show', [
            'survey' => $survey,
            'response' => $response,
        ]);
    }

    public function store(Request $request, Survey $survey, SurveyGate $gate): RedirectResponse
    {
        abort_unless($survey->is_active, 404);

        $response = $gate->responseFor($survey, $request);
        $answers = $this->mergeOtherFields($survey, $request->input('answers', []));
        $answers = $this->cleanAnswers($survey, $answers);
        $isDraft = $request->input('action') === 'draft';

        if (! $isDraft) {
            $this->validateRequiredAnswers($survey, $answers);
        }

        $response->update([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'answers' => $answers,
            'status' => $isDraft ? 'draft' : 'completed',
            'completed_at' => $isDraft ? null : now(),
        ]);

        if ($isDraft) {
            return back()->with('status', 'Survey progress saved. / บันทึกคำตอบชั่วคราวแล้ว');
        }

        $request->session()->put('survey_completed.'.$survey->id, true);

        return redirect($gate->returnTo($survey, $request))->with('status', 'Survey completed. / ทำแบบสอบถามเสร็จแล้ว');
    }

    /**
     * Build pre-fill answers for a survey from checkout session data or authenticated user.
     * Matches question keys (or labels) that represent name, email, or phone fields.
     */
    private function buildPrefillAnswers(Survey $survey, Request $request): array
    {
        $userInfo = $request->session()->get('checkout_user_info', []);

        if (empty($userInfo) && $user = $request->user()) {
            $userInfo = [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ];
        }

        if (empty(array_filter($userInfo))) {
            return [];
        }

        $prefill = [];

        foreach ($survey->questions ?? [] as $question) {
            $key = $question['key'] ?? Str::slug($question['label'] ?? Str::random(8), '_');
            $label = strtolower((string) ($question['label'] ?? ''));
            $type = $question['type'] ?? 'text';

            // Skip multi-value types — we only pre-fill text/email fields
            if (in_array($type, ['checkboxes', 'radio', 'select'], true)) {
                continue;
            }

            $keyLower = strtolower($key);

            if (! empty($userInfo['name']) && (str_contains($keyLower, 'name') || str_contains($label, 'name') || str_contains($label, 'ชื่อ'))) {
                $prefill[$key] = $userInfo['name'];
            } elseif (! empty($userInfo['email']) && ($type === 'email' || str_contains($keyLower, 'email') || str_contains($label, 'email') || str_contains($label, 'อีเมล'))) {
                $prefill[$key] = $userInfo['email'];
            } elseif (! empty($userInfo['phone']) && (str_contains($keyLower, 'phone') || str_contains($keyLower, 'tel') || str_contains($keyLower, 'mobile') || str_contains($label, 'phone') || str_contains($label, 'โทร') || str_contains($label, 'เบอร์'))) {
                $prefill[$key] = $userInfo['phone'];
            }
        }

        return $prefill;
    }

    private function mergeOtherFields(Survey $survey, array $answers): array
    {
        foreach ($survey->questions ?? [] as $question) {
            $key = $question['key'] ?? '';
            if (empty($key) || empty($question['hasOther'])) {
                continue;
            }

            $otherKey = $key.'_other';
            $otherValue = trim((string) ($answers[$otherKey] ?? ''));
            unset($answers[$otherKey]);

            if (! isset($answers[$key])) {
                continue;
            }

            $type = $question['type'] ?? 'text';

            if ($type === 'checkboxes' && is_array($answers[$key])) {
                $answers[$key] = array_map(function ($value) use ($otherValue) {
                    return $value === 'Other:' && $otherValue !== '' ? 'Other: '.$otherValue : $value;
                }, $answers[$key]);
            } elseif ($answers[$key] === 'Other:' && $otherValue !== '') {
                $answers[$key] = 'Other: '.$otherValue;
            }
        }

        return $answers;
    }

    private function cleanAnswers(Survey $survey, array $input): array
    {
        return collect($survey->questions ?? [])
            ->mapWithKeys(function (array $question) use ($input) {
                $key = $question['key'] ?? Str::slug($question['label'] ?? Str::random(8), '_');
                $type = $question['type'] ?? 'text';
                $value = $input[$key] ?? null;

                if ($type === 'checkboxes') {
                    $value = collect(is_array($value) ? $value : [])
                        ->map(fn ($item) => trim((string) $item))
                        ->filter()
                        ->values()
                        ->all();
                } else {
                    $value = is_array($value) ? null : trim((string) $value);
                }

                return [$key => $value];
            })
            ->all();
    }

    private function validateRequiredAnswers(Survey $survey, array $answers): void
    {
        $missing = collect($survey->questions ?? [])
            ->filter(function (array $question) use ($answers) {
                if (empty($question['required'])) {
                    return false;
                }

                $key = $question['key'] ?? '';
                $answer = $answers[$key] ?? null;

                if (is_array($answer)) {
                    $hasOther = ! empty($question['hasOther']) && in_array('Other:', $answer, true);
                    return $answer === [] || ($hasOther && count($answer) === 1);
                }

                $trimmed = trim((string) $answer);
                if ($trimmed === '') {
                    return true;
                }

                if (! empty($question['hasOther']) && $trimmed === 'Other:') {
                    return true;
                }

                return false;
            })
            ->pluck('label')
            ->filter()
            ->values();

        if ($missing->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'answers' => 'Please answer: '.$missing->join(', '),
            ]);
        }
    }
}
