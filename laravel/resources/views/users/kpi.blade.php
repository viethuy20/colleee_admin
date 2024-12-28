@extends('layouts.master')

@section('title', 'ユーザー管理')

@section('head.load')

@endsection

@section('menu')
<li>{!! Tag::link(route('users.index'), 'ユーザー一覧') !!}</li>
<li>{!! Tag::link(route('email_block_domains.index'), 'メールアドレスブロックドメイン一覧') !!}</li>
<li class="active">{!! Tag::link(route('users.kpi'), 'KPI') !!}</li>
@endsection

@section('menu.extra')

@section('content')

@if (WrapPhp::count($errors) > 0)
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (isset($kpi))
<div class="content_kpi">
    <h2>ユーザー数KPI</h2>
    <nav class="pageNav" id="pageNav">
        <ul class="pageNav-unit">
            <li class="pageNav-item">
                <button class="pageNav-link active btn day">日</button>
            </li>
            <li class="pageNav-item">
                <button class="pageNav-link btn week" >週</button>
            </li>
            <li class="pageNav-item">
                <button class="pageNav-link btn year" >月</button>
            </li>
        </ul>
    </nav>

    <div class="panelBody kpi_page mb-5">
        <div class="formSelect">
            <select class="formSelect-input year_select" name="year"></select>
        </div>
        <div class="formSelect">
            <select class="formSelect-input month_select" name="month"></select>
        </div>
        <input type="hidden" name="numberUserLogin" value="{{$numberUserLogin}}" id="numberUserLogin">
        <input type="hidden" name="weekUserLogin" value="{{$weekUserLogin}}" id="weekUserLogin">
        <input type="hidden" name="yearUserLogin" value="{{$yearUserLogin}}" id="yearUserLogin">
        <input type="hidden" name="login_total" value="{{$kpi_year['login_total']}}" id="login_total">
        <input type="hidden" name="unique_login_total" value="{{$kpi_year['unique_login_total']}}" id="unique_login_total">
        <input type="hidden" name="unique_action_total" value="{{$kpi_year['unique_action_total']}}" id="unique_action_total">
        <input type="hidden" name="unique_login_total" value="{{$kpi_year['unique_login_total'] > 0 ? number_format($kpi_year['unique_action_total'] * 100.0 / $kpi_year['unique_login_total'], 3) : '-'}}" id="unique_login_total">
        <input type="hidden" name="created_total" value="{{ number_format($kpi_year['created_total']) }}" id="created_total">
        <input type="hidden" name="created_action_total" value="{{ number_format($kpi_year['created_action_total'])}}" id="created_action_total">
        <input type="hidden" name="created_totals" value="{{$kpi_year['created_total'] > 0 ? number_format($kpi_year['created_action_total'] * 100.0 / $kpi_year['created_total'], 3) : '-'}}" id="created_totals">
        <input type="hidden" name="prohibited_total" value="{{ number_format($kpi_year['prohibited_total'])}}" id="prohibited_total">
        <input type="hidden" name="deleted_total" value="{{ number_format($kpi_year['deleted_total'])}}" id="deleted_total">
    </div>
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover table-month-year">
        <tr>
            <th>種別※UU:ユニークユーザー</th>
            <th>数字</th>
            <th>注意書き/説明</th>
        </tr>
        <tr>
            <td>ログイン数</td>
            <td>{{ number_format($kpi['login_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>ログイン人数(UU)</td>
            <td>{{ number_format($kpi['unique_login_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション人数(UU)</td>
            <td>{{ number_format($kpi['unique_action_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション率</td>
            <td >{{ $kpi['unique_login_total'] > 0 ? number_format($kpi['unique_action_total'] * 100.0 / $kpi['unique_login_total'], 3) : '-' }}
                %
            </td>
            <td>アクション人数 / ログイン人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者人数</td>
            <td >{{ number_format($kpi['created_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会アクション人数(UU)</td>
            <td >{{ number_format($kpi['created_action_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者アクション率</td>
            <td >{{ $kpi['created_total'] > 0 ? number_format($kpi['created_action_total'] * 100.0 / $kpi['created_total'], 3) : '-' }}
                %
            </td>
            <td>新規入会アクション人数 / 新規入会者人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>不正退会者人数</td>
            <td >{{ number_format($kpi['prohibited_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>退会者人数</td>
            <td >{{ number_format($kpi['deleted_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover table-year">
        <tr>
            <th>種別※UU:ユニークユーザー</th>
            <th>数字</th>
            <th>注意書き/説明</th>
        </tr>
        <tr>
            <td>ログイン数</td>
            <td >{{ number_format($kpi_year['login_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>ログイン人数(UU)</td>
            <td >{{ number_format($kpi_year['unique_login_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション人数(UU)</td>
            <td >{{ number_format($kpi_year['unique_action_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション率</td>
            <td>{{ $kpi_year['unique_login_total'] > 0 ? number_format($kpi_year['unique_action_total'] * 100.0 / $kpi_year['unique_login_total'], 3) : '-' }}
                %
            </td>
            <td>アクション人数 / ログイン人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者人数</td>
            <td >{{ number_format($kpi_year['created_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会アクション人数(UU)</td>
            <td >{{ number_format($kpi_year['created_action_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者アクション率</td>
            <td>{{ $kpi_year['created_total'] > 0 ? number_format($kpi_year['created_action_total'] * 100.0 / $kpi_year['created_total'], 3) : '-' }}
                %
            </td>
            <td>新規入会アクション人数 / 新規入会者人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>不正退会者人数</td>
            <td>{{ number_format($kpi_year['prohibited_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>退会者人数</td>
            <td>{{ number_format($kpi_year['deleted_total']) }}</td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover table-call-ajax">
        <tr>
            <th>種別※UU:ユニークユーザー</th>
            <th>数字</th>
            <th>注意書き/説明</th>
        </tr>
        <tr>
            <td>ログイン数</td>
            <td class="login_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>ログイン人数(UU)</td>
            <td class="unique_login_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション人数(UU)</td>
            <td class="unique_action_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>アクション率</td>
            <td class="unique_login_totals"></td>
            <td>アクション人数 / ログイン人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者人数</td>
            <td class="created_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会アクション人数(UU)</td>
            <td class="created_action_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>新規入会者アクション率</td>
            <td class="created_totals"></td>
            <td>新規入会アクション人数 / 新規入会者人数 * 100&nbsp;</td>
        </tr>
        <tr>
            <td>不正退会者人数</td>
            <td class="prohibited_total"></td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td>退会者人数</td>
            <td class="deleted_total"></td>
            <td>&nbsp;</td>
        </tr>
    </table>

    <div class="panelBody item_page mb-5">
        <div class="formSelect">
            <select class="formSelect-input item_select" name="item_select">
                <option value="login_total">ログイン数</option>
                <option value="unique_login_total">ログイン人数(UU)</option>
                <option value="unique_action_total">アクション人数(UU)</option>
                <option value="unique_login_totals">アクション率</option>
                <option value="created_total">新規入会者人数</option>
                <option value="created_action_total">新規入会アクション人数(UU)</option>
                <option value="created_totals">新規入会者アクション率</option>
                <option value="prohibited_total">不正退会者人数</option>
                <option value="deleted_total">退会者人数</option>
            </select>
        </div>
        <span>※表の指標から1項目を選択する</span>
    </div>

    <div class="grap-kpi" id="grap-kpi">
        <canvas id="dayChart"></canvas>
        <canvas id="weekChart"></canvas>
        <canvas id="monthChart"></canvas>
    </div>
</div>
@endif
@endsection
