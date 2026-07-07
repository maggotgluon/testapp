<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use Illuminate\Http\Request;

class SurveyGate
{
    public function due(string $placement, Request $request, ?Event $event = null): ?Survey
    {
        return Survey::query()
            ->available()
            ->forPlacement($placement, $event?->id)
            ->orderByRaw('event_id is null')
            ->oldest()
            ->get()
            ->first(fn (Survey $survey) => ! $this->hasCompleted($survey, $request));
    }

    public function hasCompleted(Survey $survey, Request $request): bool
    {
        if ((bool) $request->session()->get('survey_completed.'.$survey->id, false)) {
            return true;
        }

        return $survey->responses()
            ->where('status', 'completed')
            ->where(function ($query) use ($request) {
                if ($request->user()) {
                    $query->where('user_id', $request->user()->id);
                }

                $query->orWhere('session_id', $request->session()->getId());
            })
            ->exists();
    }

    public function responseFor(Survey $survey, Request $request): SurveyResponse
    {
        $query = $survey->responses()->where('status', 'draft');

        if ($request->user()) {
            $query->where('user_id', $request->user()->id);
        } else {
            $query->where('session_id', $request->session()->getId());
        }

        $response = $query->latest()->first() ?: $survey->responses()->create([
            'user_id' => $request->user()?->id,
            'session_id' => $request->session()->getId(),
            'status' => 'draft',
            'answers' => [],
            'started_at' => now(),
        ]);

        if (! $request->user()) {
            $ids = collect($request->session()->get('guest_survey_response_ids', []))
                ->push($response->id)
                ->unique()
                ->values()
                ->all();
            $request->session()->put('guest_survey_response_ids', $ids);
        }

        return $response;
    }

    public function rememberReturn(Survey $survey, Request $request, ?string $returnTo = null): void
    {
        $request->session()->put($this->returnKey($survey), $this->safeReturn($returnTo ?: $request->fullUrl()));
    }

    public function returnTo(Survey $survey, Request $request): string
    {
        return $request->session()->pull($this->returnKey($survey), route('events.index'));
    }

    public function claimGuestResponses(User $user, Request $request): void
    {
        $responseIds = $request->session()->pull('guest_survey_response_ids', []);

        SurveyResponse::query()
            ->whereNull('user_id')
            ->where(function ($query) use ($request, $responseIds) {
                $query->where('session_id', $request->session()->getId());

                if ($responseIds !== []) {
                    $query->orWhereIn('id', $responseIds);
                }
            })
            ->update(['user_id' => $user->id]);
    }

    private function returnKey(Survey $survey): string
    {
        return 'survey_return.'.$survey->id;
    }

    private function safeReturn(string $returnTo): string
    {
        if (str_starts_with($returnTo, '/') && ! str_starts_with($returnTo, '//')) {
            return $returnTo;
        }

        $appHost = parse_url(config('app.url'), PHP_URL_HOST);
        $returnHost = parse_url($returnTo, PHP_URL_HOST);

        if ($appHost && $returnHost && hash_equals($appHost, $returnHost)) {
            return $returnTo;
        }

        return route('events.index');
    }
}
