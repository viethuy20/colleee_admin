@extends('layouts.plane')

@section('layout.menu')
@if (\Auth::user()->role == \App\Admin::DRAFT_ROLE)

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">プログラム<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
        <li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
        <li>{{ Tag::link(route('app_driver_programs.index'), 'AppDriverプログラム一覧') }}</li>
        <li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
        <li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
        @php
        $sp_program_type_list = \App\SpProgramType::get();
        @endphp
        @foreach($sp_program_type_list as $sp_program_type)
        <li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
        @endforeach
        <li>{{ Tag::link(route('maintes.index'), 'メンテナンス管理') }}</li>
    </ul>
</li>
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">設定<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('admins.update_password'), 'パスワード変更') }}</li>
        <li>{{ Tag::link(route('logout'), 'ログアウト') }}</li>
    </ul>
</li>

@else

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">レポート<span class="caret"></span></a>
    <ul class="dropdown-menu">
        @for ($i = 0; $i < 3; $i++)
        @php
        $t = \Carbon\Carbon::today()->startOfMonth()->subMonths($i);
        @endphp
        <li>{{ Tag::link(route('reports.list', $t->format('Ym')), $t->format('n').'月レポート') }}</li>
        @endfor
        <li>
            <a href="{{ route('reports.monthly') }}">ポイント推移レポート</a>
        </li>
        <li>
            <a href="{{ route('reports.user_link_fanspot') }}">FanSpot連携会員情報</a>
        </li>
        <li>
            <a href="{{ route('reports.user_link_cp') }}">ドットマネーCP会員情報</a>
        </li>
    </ul>
</li>

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">プログラム<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('programs.index'), 'プログラム一覧') }}</li>
        <li>{{ Tag::link(route('credit_cards.index'), 'クレジットカード一覧') }}</li>
        <li>{{ Tag::link(route('app_driver_programs.index'), 'AppDriverプログラム一覧') }}</li>
        <li>{{ Tag::link(route('asps.index'), 'ASP一覧') }}</li>
        <li>{{ Tag::link(route('tags.index'), 'タグ一覧') }}</li>
        @php
        $sp_program_type_list = \App\SpProgramType::get();
        @endphp
        @foreach($sp_program_type_list as $sp_program_type)
        <li>{{ Tag::link(route('sp_programs.list', ['sp_program_type' => $sp_program_type]), $sp_program_type->title.'一覧') }}</li>
        @endforeach
        <li>{{ Tag::link(route('maintes.index'), 'メンテナンス管理') }}</li>
    </ul>
</li>

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">コンテンツ<span class="caret"></span></a>
    <ul class="dropdown-menu">
        @php
        $spot_list = \App\Spot::get();
        @endphp
        @foreach($spot_list as $spot)
        <li>{{ Tag::link(route('contents.list', ['spot' => $spot]), $spot->title) }}</li>
        @endforeach
        <li>{{ Tag::link(route('friends.index'), 'お友達紹介管理') }}</li>
        <li>{{ Tag::link(route('entries.index'), '会員登録画面：告知テキスト管理') }}</li>
        <li>{{ Tag::link(route('recommend_program.index'), 'おすすめ広告管理') }}</li>
        <li>{{ Tag::link(route('popup_ads.index'), 'トップポップアップ管理') }}</li>
    </ul>
</li>
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">アンケート管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('questions.index'), 'デイリーアンケート一覧') }}</li>
        <li>{{ Tag::link(route('user_answers.index'), 'アンケートコメント一覧') }}</li>
    </ul>
</li>
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">ポイント交換管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
        @include('partials.menu.point_exchange_menu', ['currentRoute' => ''])
    </ul>
</li>

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">成果管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('external_links.index'), 'クリック一覧') }}</li>
        <li>{{ Tag::link(route('aff_rewards.index'), '成果一覧') }}</li>
        @if (\Auth::user()->role <= \App\Admin::SUPPORT_ROLE)
        <li>{{ Tag::link(route('aff_rewards.import'), '成果インポート') }}</li>
        <li>{{ Tag::link(route('aff_rewards.achievement'), '成果レポート') }}</li>
        @endif
    </ul>
</li>

@if (\Auth::user()->role != \App\Admin::DRAFT_ROLE)
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">ユーザー管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
    	<li>{{ Tag::link(route('users.index'), 'ユーザー一覧') }}</li>
        <li>{{ Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') }}</li>
        <li>{{ Tag::link(route('users.csv'), 'ユーザーアカウント管理') }}</li>
    </ul>
</li>
@endif

<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">口コミ管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
    	<li>{{ Tag::link(route('reviews.index'), '全一覧') }}</li>
        @php
        $review_status_map = config('map.auth_status');
        @endphp
        @foreach($review_status_map as $i =>  $label)
        <li>{{ Tag::link(route('reviews.list', ['status' => $i]), $label.'一覧') }}</li>
        @endforeach
        <li><a href="{{ route('review_point_management.index') }}">配布ポイント管理</a></li>
    </ul>
</li>
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">特集管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('feature_sub_categories.index'), '特集サブカテゴリ一覧') }}</li>
        <li>{{ Tag::link(route('feature_programs.index'), '特集広告一覧') }}</li>
    </ul>
</li>
@if (\Auth::user()->role == \App\Admin::ADMIN_ROLE)
<li>{{ Tag::link(route('admins.index'), '管理者管理') }}</li>
@endif
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">ラベル管理<span class="caret"></span></a>
    <ul class="dropdown-menu">
        @php
        $label_type = config('map.label_type');
        @endphp
        @foreach($label_type as $key => $label)
        <li>{{ Tag::link(route('labels.list', ['type' => $key]), $label) }}</li>
        @endforeach
    </ul>
</li>
<li class="dropdown">
    <a class="dropdown-toggle" data-toggle="dropdown" href="#">設定<span class="caret"></span></a>
    <ul class="dropdown-menu">
        <li>{{ Tag::link(route('admins.update_password'), 'パスワード変更') }}</li>
        <li>{{ Tag::link(route('logout'), 'ログアウト') }}</li>
    </ul>
</li>

@endif

<!-- <li>{{ Tag::link(route('user_points.index'), 'ユーザーポイント補填') }}</li> -->
@endsection

@section('layout.content')
<div class="row-fluid">
    <div class="col-md-2"><div class="panel panel-default">
        <div class="panel-heading">操作</div>
        <ul class="nav nav-pills nav-stacked">
            @yield('menu')
        </ul>
        @yield('menu.extra')
    </div></div>

    <!-- main -->
    <div class="col-md-10">
        <!-- apply custom style -->
        <div class="page-header" style="margin-top:-30px;padding-bottom:0px;">
            <h1>
                <small>
                    @if (Session::has('message'))
                        <span>{{ Session::get('message') }}</span><br />
                    @endif

                    @if (Session::has("error-cv-in-database"))
                        <span style="color:red;" class="current-stock_cv">{{ Session::get("error-cv-in-database")['stock_cv_db'] ?? '' }}</span><br />
                        <span style="color:red;" class="current-updated_at">{{ Session::get("error-cv-in-database")['updated_at_db'] ?? '' }}</span><br />
                        <span style="color:red;" class="error-stock_cv">{{ Session::get("error-cv-in-database")['message'] ?? '' }}</span><br />
                    @endif
                </small>
            </h1>
        </div>
        @yield('content')
    </div>
</div><!--/row-->
@endsection
