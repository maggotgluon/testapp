<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: 'Noto Sans Thai';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/NotoSansThai-Regular.ttf') }}') format('truetype');
        }
        @font-face {
            font-family: 'Noto Sans Thai';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/NotoSansThai-Bold.ttf') }}') format('truetype');
        }
        body { font-family: 'Noto Sans Thai', DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a2e; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th { background: #f0f0f0; text-align: left; padding: 6px 8px; font-size: 9px; text-transform: uppercase; border: 1px solid #ddd; }
        td { padding: 5px 8px; border: 1px solid #ddd; vertical-align: top; }
        tr:nth-child(even) { background: #fafafa; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <h1>{{ $survey->title }}</h1>
    <p class="subtitle">{{ $survey->description }}<br>{{ count($responses) }} responses · {{ now()->format('Y-m-d H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Respondent / ผู้ตอบ</th>
                <th>Submitted / วันที่ส่ง</th>
                @foreach($questions as $question)
                    <th>{{ $question['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($responses as $i => $response)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $response->user?->name ?? 'Guest / แขก' }}</td>
                    <td>{{ $response->completed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    @foreach($questions as $question)
                        <td>
                            @php $answer = $response->answers[$question['key']] ?? ''; @endphp
                            {{ is_array($answer) ? implode(', ', $answer) : $answer }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
