<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Survey;
use App\Models\SurveyResponse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function index(Request $request): View
    {
        $events = $this->eventsForUser($request);
        $surveys = Survey::query()
            ->with(['event', 'responses'])
            ->when($request->user()->role !== 'super_admin', fn ($query) => $query->whereIn('event_id', $events->pluck('id')))
            ->when($request->filled('event_id'), fn ($query) => $query->where('event_id', $request->integer('event_id')))
            ->when($request->filled('placement'), fn ($query) => $query->where('placement', $request->string('placement')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.surveys.index', compact('surveys', 'events'));
    }

    public function create(Request $request): View
    {
        return view('admin.surveys.form', [
            'survey' => new Survey([
                'placement' => 'before_event_view',
                'questions' => [],
                'is_active' => true,
            ]),
            'events' => $this->eventsForUser($request),
            'placements' => Survey::PLACEMENTS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $this->authorizeSurveyEvent($request, $data);

        Survey::create($data + ['created_by' => $request->user()->id]);

        return redirect()->route('admin.surveys.index')->with('status', 'Survey created.');
    }

    public function edit(Request $request, Survey $survey): View
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);

        return view('admin.surveys.form', [
            'survey' => $survey,
            'events' => $this->eventsForUser($request),
            'placements' => Survey::PLACEMENTS,
        ]);
    }

    public function update(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);

        $data = $this->validated($request);
        $this->authorizeSurveyEvent($request, $data);
        $survey->update($data);

        return redirect()->route('admin.surveys.index')->with('status', 'Survey updated.');
    }

    public function destroy(Request $request, Survey $survey): RedirectResponse
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);
        $survey->delete();

        return redirect()->route('admin.surveys.index')->with('status', 'Survey deleted.');
    }

    public function responses(Request $request, Survey $survey): View
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);

        $responses = SurveyResponse::query()
            ->with('user')
            ->where('survey_id', $survey->id)
            ->where('status', 'completed')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.surveys.responses', compact('survey', 'responses'));
    }

    public function exportCsv(Request $request, Survey $survey): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);

        $questions = $survey->questions ?? [];
        $headers = array_merge(['Respondent', 'Submitted At'], array_column($questions, 'label'));

        $response = new \Symfony\Component\HttpFoundation\StreamedResponse(function () use ($survey, $questions, $headers) {
            $handle = fopen('php://output', 'w+b');

            fputcsv($handle, $headers);

            SurveyResponse::query()
                ->with('user')
                ->where('survey_id', $survey->id)
                ->where('status', 'completed')
                ->chunk(100, function ($responses) use ($handle, $questions) {
                    foreach ($responses as $r) {
                        $row = [
                            $r->user?->name ?? 'Guest',
                            $r->completed_at?->format('Y-m-d H:i:s') ?? '',
                        ];
                        foreach ($questions as $question) {
                            $row[] = $r->answers[$question['key']] ?? '';
                        }
                        fputcsv($handle, $row);
                    }
                });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="survey-responses-'.$survey->id.'.csv"',
        ]);

        return $response;
    }

    public function destroyResponse(Request $request, Survey $survey, SurveyResponse $response): RedirectResponse
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);
        abort_unless($response->survey_id === $survey->id, 404);

        $response->delete();

        return back()->with('status', 'Response deleted. / ลบคำตอบแล้ว');
    }

    public function exportPdf(Request $request, Survey $survey): \Illuminate\Http\Response
    {
        abort_unless($this->canManageSurvey($request, $survey), 403);

        $questions = $survey->questions ?? [];

        $responses = SurveyResponse::query()
            ->with('user')
            ->where('survey_id', $survey->id)
            ->where('status', 'completed')
            ->orderBy('completed_at')
            ->get();

        $pdf = Pdf::loadView('admin.surveys.pdf', compact('survey', 'questions', 'responses'));

        return $pdf->download('survey-responses-'.$survey->id.'.pdf');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'event_id' => ['nullable', 'exists:events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'description_format' => ['nullable', 'in:html,markdown'],
            'placement' => ['required', 'in:'.implode(',', array_keys(Survey::PLACEMENTS))],
            'questions' => ['nullable', 'array'],
            'questions.*.key' => ['nullable', 'string', 'max:80'],
            'questions.*.label' => ['nullable', 'string', 'max:255'],
            'questions.*.type' => ['nullable', 'in:text,textarea,email,number,date,select,radio,checkboxes'],
            'questions.*.help' => ['nullable', 'string', 'max:255'],
            'questions.*.options' => ['nullable', 'string', 'max:2000'],
            'questions.*.required' => ['nullable', 'boolean'],
            'questions.*.hasOther' => ['nullable', 'boolean'],
            'questions.*.otherLabel' => ['nullable', 'string', 'max:255'],
            'questions.*.searchable' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data['event_id'] = $data['event_id'] ?? null;
        $data['is_active'] = $request->boolean('is_active');
        $data['description_format'] = $data['description_format'] ?? 'html';
        $data['questions'] = $this->questionsFromRequest($request);

        return $data;
    }

    private function questionsFromRequest(Request $request): array
    {
        return collect($request->input('questions', []))
            ->filter(fn ($question) => is_array($question) && filled($question['label'] ?? null))
            ->map(function (array $question, int $index) {
                $label = trim((string) $question['label']);
                $key = trim((string) ($question['key'] ?? '')) ?: Str::slug($label, '_');

                $hasOther = (bool) ($question['hasOther'] ?? false);

                return [
                    'key' => $key ?: 'question_'.$index,
                    'label' => $label,
                    'type' => $question['type'] ?? 'text',
                    'help' => trim((string) ($question['help'] ?? '')) ?: null,
                    'required' => (bool) ($question['required'] ?? false),
                    'hasOther' => $hasOther,
                    'otherLabel' => $hasOther ? (trim((string) ($question['otherLabel'] ?? '')) ?: null) : null,
                    'searchable' => (bool) ($question['searchable'] ?? false),
                    'options' => collect(preg_split('/\r\n|\r|\n/', (string) ($question['options'] ?? '')))
                        ->map(fn ($option) => trim($option))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function eventsForUser(Request $request)
    {
        $events = Event::query()->orderBy('starts_at');

        if ($request->user()->role !== 'super_admin') {
            $events->whereHas('assignedUsers', fn ($query) => $query->whereKey($request->user()->id));
        }

        return $events->get();
    }

    private function authorizeSurveyEvent(Request $request, array $data): void
    {
        if ($request->user()->role === 'super_admin') {
            return;
        }

        abort_unless(! empty($data['event_id']) && $request->user()->canManageEvent((int) $data['event_id']), 403);
    }

    private function canManageSurvey(Request $request, Survey $survey): bool
    {
        return $request->user()->role === 'super_admin'
            || ($survey->event_id && $request->user()->canManageEvent($survey->event_id));
    }
}
