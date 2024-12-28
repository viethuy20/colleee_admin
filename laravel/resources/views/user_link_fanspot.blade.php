@extends('layouts.master')

@section('title', 'レポート')

@section('menu')
    @for ($i = 0; $i < 3; $i++)
        @php
            $t = \Carbon\Carbon::today()
                ->startOfMonth()
                ->subMonths($i);
        @endphp
        <li>{{ Tag::link(route('reports.list', ['ym' => $t->format('Ym')]), $t->format('n') . '月レポート') }}</li>
    @endfor
    @php
        $currentRoute = \Request::route()->getName();
        $classActive = '';
        $classFanspotActive = '';
        $classCpActive = '';
        
        switch ($currentRoute) {
            case 'reports.monthly':
                $classActive = 'class=active';
                break;
        
            case 'reports.user_link_fanspot':
                $classFanspotActive = 'class=active';
                break;
        
            case 'reports.user_link_cp':
                $classCpActive = 'class=active';
                break;
        
            default:
                break;
        }
    @endphp
    <li {{ $classActive }}><a href="{{ route('reports.monthly') }}">ポイント推移レポート</a></li>
    <li {{ $classFanspotActive }}>
        <a href="{{ route('reports.user_link_fanspot') }}">FanSpot連携会員情報</a>
    </li>
    <li {{ $classCpActive }}>
        <a href="{{ route('reports.user_link_cp') }}">ドットマネーCP会員情報</a>
    </li>
@endsection

@section('content')
    <h2>FanSpot連携会員情報</h2>
    {{ Tag::formOpen(['url' => route('reports.csvUser'), 'method' => 'post']) }}
    @csrf
    <div class="row content">
        <div class="message-box" style="margin: 20px;">
            @if($errors->any())
                @foreach ($errors->all() as $error)
                    <p style="color:red">{{ $error }}</p>
                @endforeach
            @endif
        </div>
        <div class="col-md-3">
            <div>
                レポートタイプ
            </div>
            <div>
                <select name="type" class="form-control">
                    <option value="1">ユーザー情報</option>
                    <option value="2">ユーザーログイン情報</option>
                    <option value="3">ユーザーポイント情報</option>
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div>
                開始日
            </div>
            <div>
                <div class="row">
                    <div class="col-md-1" style="margin-top: 4px;">
                        {{ Tag::image(url('image/calendar.png'), 'img', ['id' => 'openCalendarStartDate','class' => 'ui-datepicker-trigger' ,'width' => '25px']) }}
                    </div>
                    <div class="col-md-11">
                        <input class="form-control js-datepicker" id="start_at" name="start_at" type="text" value="{!! old('start_at', $data['start_at']) !!}">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div>
                終了日
            </div>
            <div>
                <div class="row">
                    <div class="col-md-1" style="margin-top: 4px;">{{ Tag::image(url('image/calendar.png'), 'img', ['id' => 'openCalendarEndDate', 'width' => '25px']) }}</div>
                    <div class="col-md-11">
                        <input class="form-control js-datepicker" id="end_at" name="end_at" type="text" value="{!! old('end_at', $data['end_at']) !!}">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div>
                <input type="hidden" name="user_promotion_id" value="8948837">
                <div class="form-group">{{ Tag::formSubmit('ダウンロード', ['id' => 'btn_export', 'class' => 'btn btn-export-user']) }}</div>
            </div>
        </div>
    </div>
    {{ Tag::formClose() }}
    {!! Tag::script('/js/user_link_fanspot_and_cp.js', ['type' => 'text/javascript']) !!}
@endsection
