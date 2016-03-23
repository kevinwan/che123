@extends('_layouts.default')

@section('title', "{$category->name}排行_车123_全能车主工具")

<style>
    .share-shade{width:100%;height:100%;position:fixed;left:0;top:0;background:#000;z-index:99;opacity:.7;display:none;z-index:100}
    .share-txt{position:fixed;top:0;right:10px;z-index:200;width:80%;display:none;z-index:200}
</style>

<section class="main">
    <section class="title">
        <a href="/"><img src="/img/title-turn-back.png" alt=""></a>
        <h2>{{ $category->name }}排行</h2>
    </section>
    @foreach ($appList as $app)
        <section class="list-sec">
            <a href="/appView/{{ $app->id }}" class="list-sec-img"><img src="{{ config('app.pic_path').$app->logo }}" class="list-img"></a>
            <a href="/appView/{{ $app->id }}" class="list-sec-txt">
                <h2>{{ $app->name }}</h2>
                <span>{{ $app->downloadShow }}下载</span>
                <p>{{ $app->slogan }}</p>
            </a>
            <a href="{{ $app->url }}" class="list-download">下载</a>
        </section>
    @endforeach
</section>

<!--IOS下WECHAT内 提示浮层-->
<section class="share-shade share-close"></section>
<img class="share-txt share-close" src="/img/download_ios.png">
<script src="/vendor/jQuery/jquery-2.1.4.min.js"></script>
<script src="/js/browser.js"></script>
<script>
    $('.list-download').on('click', function () {
        if(browser.versions.ios && browser.versions.wechat){
            $('.share-shade').show();
            $('.share-txt').show();
        }else
            window.location.href = "{{ $app->url }}";
    });
    $(document).on('click', '.share-txt', function () {
        $('.share-shade').hide();
        $('.share-txt').hide();
    })
    document.querySelector('.share-close').onclick = function () {
        $('.share-shade').hide();
        $('.share-txt').hide();
    }
</script>
