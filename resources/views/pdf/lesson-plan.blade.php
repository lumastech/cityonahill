{{--
    The printed CBC lesson plan. dompdf has no flexbox or grid, so the layout here
    is tables throughout — deliberately not a port of the Vue page's utility classes.
--}}
@php
    $statusLabels = [
        'draft' => 'Draft',
        'submitted' => 'Pending review',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'reverted' => 'Returned',
    ];

    $stream = $plan->stream
        ? trim(($plan->stream->grade?->name ? $plan->stream->grade->name.' - ' : '').$plan->stream->name)
        : null;

    $headerFields = [
        'General competence' => $plan->general_competence,
        'Specific competence' => $plan->specific_competence,
        'Lesson goal' => $plan->lesson_goal,
        'Reference' => $plan->reference,
        'Prior knowledge' => $plan->prior_knowledge,
        'Learning material' => $plan->learning_material,
        'Learning environment' => $plan->learning_environment,
    ];

    $facts = [
        'Teacher' => $plan->teacher?->name,
        'Subject' => $plan->subject?->name,
        'Class' => $stream,
        'Term' => trim(($plan->term?->name ?? '').($plan->week_number ? ' · Wk '.$plan->week_number : '')),
        'Date' => $plan->lesson_date?->format('d M Y'),
        'Duration' => $plan->duration_minutes ? $plan->duration_minutes.' min' : null,
        'Pupils' => $plan->total_pupils === null
            ? null
            : $plan->total_pupils.' ('.((int) $plan->boys_count).'B / '.((int) $plan->girls_count).'G)',
        'Submitted' => $plan->submitted_at?->format('d M Y H:i'),
    ];

    // Outcomes recorded on the plan, so a returned or rejected copy carries its reason.
    $decisions = array_values(array_filter([
        $plan->revert_reason ? ['Returned for changes', $plan->revert_reason, $plan->reverter?->name, $plan->reverted_at] : null,
        $plan->reject_reason ? ['Rejected', $plan->reject_reason, $plan->reviewer?->name, $plan->reviewed_at] : null,
        $plan->comment ? ["Reviewer's note", $plan->comment, $plan->reviewer?->name, $plan->reviewed_at] : null,
    ]));
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $plan->topic }}</title>
    <style>
        @page { margin: 14mm 12mm 16mm; }

        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5pt; color: #111827; line-height: 1.4; }

        h1 { font-size: 13pt; margin: 0; }
        h2 { font-size: 9pt; text-transform: uppercase; letter-spacing: .04em; color: #4b5563;
             margin: 14px 0 5px; border-bottom: 1px solid #d1d5db; padding-bottom: 3px; }

        .muted { color: #6b7280; }
        .small { font-size: 8pt; }

        .masthead { border-bottom: 2px solid #111827; padding-bottom: 6px; margin-bottom: 10px; }
        .masthead .school { font-size: 8.5pt; text-transform: uppercase; letter-spacing: .08em; color: #4b5563; }
        .status { float: right; border: 1px solid #9ca3af; border-radius: 9px;
                  padding: 2px 8px; font-size: 8pt; color: #374151; }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; text-align: left; }

        .facts td { padding: 3px 8px 3px 0; width: 25%; }
        .facts .label { display: block; font-size: 7.5pt; text-transform: uppercase;
                        letter-spacing: .04em; color: #6b7280; }

        .fields td { padding: 4px 10px 6px 0; width: 50%; }
        .fields .label { display: block; font-size: 7.5pt; text-transform: uppercase;
                         letter-spacing: .04em; color: #6b7280; margin-bottom: 1px; }

        .stages { border: 1px solid #9ca3af; }
        .stages th { background: #f3f4f6; font-size: 7.5pt; text-transform: uppercase;
                     letter-spacing: .04em; color: #374151; }
        .stages th, .stages td { border: 1px solid #d1d5db; padding: 5px 6px; }
        .stages tr { page-break-inside: avoid; }

        .note { border: 1px solid #d1d5db; border-left: 3px solid #6b7280;
                padding: 5px 8px; margin-bottom: 6px; page-break-inside: avoid; }

        .signatures td { padding-top: 26px; width: 50%; }
        .signatures .line { border-top: 1px solid #9ca3af; padding-top: 3px; width: 85%; }

        .footer { position: fixed; bottom: -10mm; left: 0; right: 0;
                  font-size: 7.5pt; color: #9ca3af; }
        /* dompdf resolves counter(page) per page; the total is not available here. */
        .footer .page:after { content: counter(page); }
    </style>
</head>
<body>

    <div class="footer">
        {{ $school?->name }} · Lesson plan · Page <span class="page"></span>
    </div>

    <div class="masthead">
        <span class="status">{{ $statusLabels[$plan->status] ?? ucfirst($plan->status) }}</span>
        <div class="school">{{ $school?->name }}</div>
        <h1>{{ $plan->topic }}</h1>
        @if ($plan->sub_topic)
            <div class="muted small">{{ $plan->sub_topic }}</div>
        @endif
    </div>

    <table class="facts">
        @foreach (array_chunk($facts, 4, true) as $row)
            <tr>
                @foreach ($row as $label => $value)
                    <td>
                        <span class="label">{{ $label }}</span>
                        {{ $value ?: '—' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    @if ($decisions)
        <h2>Review</h2>
        @foreach ($decisions as [$label, $text, $who, $when])
            <div class="note">
                <strong>{{ $label }}:</strong> {{ $text }}
                <div class="muted small">{{ $who ?? 'Reviewer' }} · {{ $when?->format('d M Y H:i') ?? '—' }}</div>
            </div>
        @endforeach
    @endif

    <h2>Lesson details</h2>
    <table class="fields">
        @foreach (array_chunk($headerFields, 2, true) as $row)
            <tr>
                @foreach ($row as $label => $value)
                    <td>
                        <span class="label">{{ $label }}</span>
                        {{ $value ?: '—' }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </table>

    <h2>Lesson development</h2>
    @if ($plan->stages)
        <table class="stages">
            <thead>
                <tr>
                    <th style="width: 15%">Stage</th>
                    <th style="width: 29%">Teacher activity</th>
                    <th style="width: 28%">Learners activity</th>
                    <th style="width: 28%">Assessment criteria</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($plan->stages as $stage)
                    <tr>
                        <td><strong>{{ $stage['stage'] ?: '—' }}</strong></td>
                        <td>{{ $stage['teacher_activity'] ?: '—' }}</td>
                        <td>{{ $stage['learner_activity'] ?: '—' }}</td>
                        <td>{{ $stage['assessment_criteria'] ?: '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="muted">No stages recorded.</p>
    @endif

    <table class="fields">
        <tr>
            <td>
                <span class="label">Conclusion</span>
                {{ $plan->conclusion ?: '—' }}
            </td>
            <td>
                <span class="label">Evaluation (after teaching)</span>
                {{ $plan->evaluation ?: '—' }}
            </td>
        </tr>
    </table>

    @if ($attachments->isNotEmpty())
        <h2>Attachments</h2>
        <ul class="small">
            @foreach ($attachments as $attachment)
                <li>{{ $attachment->file_name }}</li>
            @endforeach
        </ul>
    @endif

    <table class="signatures">
        <tr>
            <td>
                <div class="line">Teacher — {{ $plan->teacher?->name ?? '' }}</div>
            </td>
            <td>
                <div class="line">
                    Reviewer{{ $plan->reviewer ? ' — '.$plan->reviewer->name : '' }}
                    @if ($plan->reviewed_at)
                        <span class="muted small">({{ $plan->reviewed_at->format('d M Y') }})</span>
                    @endif
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
