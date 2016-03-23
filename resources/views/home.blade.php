@extends('_layouts.default')

@section('title', '车123_全能车主工具')

@section('content')
<section class="main">
    <section class="banner">
        <img src="/img/banner.jpg">
    </section>
    @foreach ($categories as $k_cat => $cats)
        <section class="business
            business-@if ($k_cat % 3 == 0)
one
            @elseif ($k_cat % 3 == 1)
two
            @else
three
            @endif">
            <h2>{{ $cats->name }}</h2>
            @foreach ($cats->_child as $cat)
                <section class="business-sec">
                    <a href="" class="business-sec-title">{{ $cat->name }}</a>
                    <section class="business-sec-link">
                        @foreach ($cat->apps as $k_app => $app)
                            @if ($k_app < 3)
                                <a href="/appView/{{ $app->id }}">{{ $app->name }}</a>
                            @endif
                        @endforeach
                    </section>
                    <a href="{{ URL('rankingList/'. $cat->id) }}" class="business-sec-arrow"><img src="/img/arrow.png" alt=""></a>
                </section>
            @endforeach
        </section>
    @endforeach
</section>
@endsection