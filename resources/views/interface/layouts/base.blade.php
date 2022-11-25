@extends('mainframe')

@section('head')
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset("assets/interface/css/app.css") }}" rel="stylesheet">
@endsection

@section('body')
    <div class="flex-center position-ref full-height">
        <div class="content">
            @yield('content')
        </div>
    </div>
@endsection
