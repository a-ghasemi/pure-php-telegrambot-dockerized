<!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie ie6" lang="{{ str_replace('_', '-', app()->getLocale()) }}"> <![endif]-->
<!--[if IE 7 ]><html class="ie ie7" lang="{{ str_replace('_', '-', app()->getLocale()) }}"> <![endif]-->
<!--[if IE 8 ]><html class="ie ie8" lang="{{ str_replace('_', '-', app()->getLocale()) }}"> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!-->
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<!--<![endif]-->
    <head>
        <meta charset="utf-8"/>
        <meta content="text/html; charset=utf-8" http-equiv="content-type"/>
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1"/>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>

        <title>@yield('page_title') | {{$brandings['site_title'] ?? config('app.title', 'Laravel') }}</title>

        {{-- CSRF Token --}}
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @stack('css')

        @yield('head')

{{-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries --}}
{{--       WARNING: Respond.js doesn't work if you view the page via file:// --}}
        <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>
    <body class="{{$BODY_CLASS ?? ''}}">
        <!--[if lt IE 8]>
        <p class="browserupgrade">You are using an <strong>outdated</strong> browser. Please <a href="http://browsehappy.com/">upgrade
            your browser</a> to improve your experience.</p>
        <![endif]-->

        @yield('body')

        @stack('mainjs')
        <script>
            ajaxSet : {
                if (typeof jQuery !== "function") {
                    console.log('jQuery not implemented yet!')
                }

                if (typeof $ !== "function") {
                    console.log('$ not implemented yet!')
                    break ajaxSet
                }

                if (typeof $.ajaxSetup !== "function") {
                    console.log('ajaxSetup not implemented yet!')
                    break ajaxSet
                }

                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
            }
        </script>
        @stack('js')
    </body>
</html>
