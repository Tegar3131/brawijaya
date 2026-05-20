@extends($layout)

@section('title', $title)

@section('content')
    <section class="card">
        <div class="eyebrow">{{ $eyebrow ?? 'SIMPB' }}</div>

        <h2>{{ $title }}</h2>

        <p>{{ $description }}</p>
    </section>

    @if (! empty($endpoints))
        <section class="card">
            <h2>Endpoint API Terkait</h2>

            <ul>
                @foreach ($endpoints as $endpoint)
                    <li>
                        <code>{{ $endpoint['method'] }} {{ $endpoint['path'] }}</code>
                        @if (! empty($endpoint['note']))
                            — {{ $endpoint['note'] }}
                        @endif
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (! empty($notes))
        <section class="card">
            <h2>Catatan Integrasi</h2>

            <ul>
                @foreach ($notes as $note)
                    <li>{{ $note }}</li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="card">
        <h2>Status Halaman</h2>

        <p>
            Ini adalah placeholder frontend. Data belum diambil dari API.
            Integrasi API client dan state handling akan dilakukan pada tahap berikutnya.
        </p>
    </section>
@endsection