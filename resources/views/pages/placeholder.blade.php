@extends($layout)

@section('title', $title)

@section('content')
<style>
    /* Scoped Custom CSS untuk Tampilan Placeholder Modern */
    .ph-wrapper {
        max-width: 800px;
        margin: 4rem auto;
        animation: fadeIn 0.4s ease-out;
        text-align: center;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .ph-header {
        text-align: center;
        margin-bottom: 3.5rem;
    }

    .ph-eyebrow {
        display: inline-block;
        color: var(--accent);
        font-family: var(--font-sans);
        font-weight: 700;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        font-size: 13px;
        margin-bottom: 1.5rem;
    }

    .ph-title {
        font-family: var(--font-heading);
        font-size: 3rem;
        color: var(--brand-dark);
        font-weight: 700;
        margin-bottom: 1.5rem;
        line-height: 1.2;
    }

    .ph-description {
        font-family: var(--font-serif);
        font-size: 1.25rem;
        color: var(--muted);
        line-height: 1.8;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .ph-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 4px;
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.02);
        text-align: left;
    }

    .ph-card-title {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--brand-dark);
        margin-top: 0;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        border-bottom: 1px solid var(--border);
        padding-bottom: 1rem;
    }
    
    /* Endpoints removed */

    /* Notes Styling */
    .ph-card.notes-card {
        background: #fefce8;
        border-color: #fef08a;
    }

    .ph-card.notes-card .ph-card-title {
        border-bottom-color: #fde047;
        color: #854d0e;
    }

    .ph-notes-list {
        margin: 0;
        padding-left: 1.25rem;
        color: #854d0e;
        font-family: var(--font-serif);
    }

    .ph-notes-list li {
        margin-bottom: 0.75rem;
        line-height: 1.6;
    }

    .ph-notes-list li:last-child {
        margin-bottom: 0;
    }
</style>

<div class="ph-wrapper">
    
    <div class="ph-header">
        @if(!empty($eyebrow))
            <span class="ph-eyebrow">{{ $eyebrow }}</span>
        @endif
        <h1 class="ph-title">{{ $title }}</h1>
        <p class="ph-description">{{ $description }}</p>
    </div>

    @if(!empty($notes) && count($notes) > 0)
    <div class="ph-card notes-card">
        <h2 class="ph-card-title">
            <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path></svg>
            Catatan Penting
        </h2>
        <ul class="ph-notes-list">
            @foreach($notes as $note)
                <li>{{ $note }}</li>
            @endforeach
        </ul>
    </div>
    @endif

</div>
@endsection