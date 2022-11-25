@extends("interface.layouts.base",['page_title' => 'Home','no_bread' => true])

@push('css')
    <style>
        .version {
            font-size: 18px;
        }

        .details {
            font-size: 16px;
        }
    </style>
@endpush

@section('content')
    <div class="title m-b-md">
        {{ config('app.title') }}
        <span class="headline m-b-md">{{ config('app.version') }}</span>
        <div class="desc">{{ config('app.description') }}</div>
    </div>

    <div class="links">
        Follow us at : <a href="https://github.com/a-ghasemi">Github <i class="fa fa-github"></i></a>
    </div>
@endsection
