@extends('layouts.master')

@section('title', '特集広告一覧')

@section('head.load')
<script type="text/javascript"><!--
$(document).on('change', ".AjaxContent", function(event) {
    var reviewElementId = $(this).attr('forRender');
    var ajaxUrl = $(this).attr('forUrl');
    var featureId = $("#FeatureProgramFeatureId option:selected").val()

    $.ajax({
        type: 'GET',
        data: {
            feature_id : featureId
        },
        url: ajaxUrl,
        scriptCharset: 'utf-8',
        dataType: 'json',
        beforeSend: function (xhr) {
            var token = $('meta[name="csrf_token"]').attr('content');
            if (token) {
                return xhr.setRequestHeader('X-XSRF-TOKEN', token);
            }
        },
        success: function(data) {
            // 特集サブカテゴリドロップダウンリスト更新
            $('#FeatureProgramSubCategory option').remove();
            $.each(data, function(key, array) {
                $('#FeatureProgramSubCategory').append($('<option>').text('---').attr('value', ''));
                $.each(array, function(id, title) {
                    $('#FeatureProgramSubCategory').append($('<option>').text(title).attr('value', id));
                });
            });
        },
        error:function(xhr) {
            $('#' + reviewElementId).html('データ取得に失敗しました');
        }
    });
});
//-->
</script>
@endsection

@section('menu')
<li class="active">{{ Tag::link(route('feature_programs.index'), '特集広告一覧') }}</li>
<li>{{ Tag::link(route('feature_programs.create'), '新規特集広告登録') }}</li>
@endsection

@section('menu.extra')
<div class="panel-heading">Search</div>
{{ Tag::formOpen(['url' => route('feature_programs.index'), 'method' => 'get']) }}
@csrf    
<div class="form-group AjaxContent" forUrl="{{ route('feature_programs.sub_category') }}" forRender="sub_category_list">
        <label for="FeatureProgramFeatureId">特集カテゴリ</label>
        {{ Tag::formSelect('feature_id', ['' => '---'] + $feature_category_map, $paginator->getQuery('feature_id') ?? '', ['class' => 'form-control', 'id' => 'FeatureProgramFeatureId']) }}<br />
    </div>

    <div class="form-group">
        <label for="FeatureProgramSubCategory">特集サブカテゴリ</label>
        {{ Tag::formSelect('sub_category_id', ['' => '---'] + $sub_category_map, $paginator->getQuery('feature_id') ?? '', ['class' => 'form-control', 'id' => 'FeatureProgramSubCategory']) }}<br />
    </div>

    <div class="form-group">{!! Tag::formSubmit('検索', ['class' => 'btn btn-default']) !!}</div>
{{ Tag::formClose() }}
@endsection

@section('content')
@if (WrapPhp::count($errors) > 0)
<div class="alert alert-danger"><ul>
    @foreach ($errors->all() as $error)
    <li>{{ $error }}</li>
    @endforeach
</ul></div>
@endif

<h2>特集広告</h2>
<table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
    <tr>
        <th>操作</th>
        <th>ID</th>
        <th>特集カテゴリ</th>
        <th>特集サブカテゴリ</th>
        <th>プログラム</th>
        <th>詳細</th>
        <th>表示順</th>
        <th>作成日時</th>
        <th>更新日時</th>
    </tr>
    @php
    $now = Carbon\Carbon::now();
    $status_map = [0 => ['class' => 'active', 'status' => '公開中'],
        4 => ['class' => 'danger', 'status' => '非公開']];
    @endphp
    @forelse ($paginator as $index => $feature_program)
    @php
    $state_id = 0;
    if ($feature_program->status == 1) {
        $state_id = 4;
    }
    @endphp
    <tr class="{{ $status_map[$state_id]['class'] }}">
        <td class="actions" style="white-space:nowrap">
            @if ($feature_program->status == 0)
            {{ Tag::link(route('feature_programs.edit', ['feature_program' => $feature_program]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            @endif
            @if ($feature_program->status != 0)
            {{ Tag::formOpen(['url' => route('feature_programs.enable', ['feature_program' => $feature_program])]) }}
            @csrf    
            {{ Tag::formSubmit('公開', ['class' => 'btn btn-success btn-small', 'onclick' => "return confirm('この特集広告を公開しますか？');"]) }}
            {{ Tag::formClose() }}
            @endif
            @if ($feature_program->status != 1)
            {{ Tag::formOpen(['url' => route('feature_programs.destroy', ['feature_program' => $feature_program])]) }}
            @csrf
            @method('DELETE')
            {{ Tag::formSubmit('非公開', ['class' => 'btn btn-danger btn-small', 'onclick' => "return confirm('この特集広告を非公開にしますか？');"]) }}
            {{ Tag::formClose() }}
            @endif
        </td>

        <td>{{ $feature_program->id }}&nbsp;</td>
        <td>{{ isset($feature_program->feature_id) ? $feature_category_map[$feature_program->feature_id] : '' }}&nbsp;</td>
        <td>{{ $sub_category_map[$feature_program->sub_category_id] ?? 'なし（ピックアップ）' }}</td-->
        <td>{{ isset($feature_program->program->title) ? $feature_program->program->title : '' }}&nbsp;</td>
        <td>{!! $feature_program->detail !!}&nbsp;</td>
        <td>{{ $feature_program->priority }}&nbsp;</td>
        <td>{{ $feature_program->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        <td>{{ $feature_program->updated_at->format('Y-m-d H:i:s') }}&nbsp;</td>
    </tr>
    @empty
    <tr><td colspan="9">特集広告は存在しません</td></tr>
    @endforelse
</table>

{!! $paginator->links() !!}

@endsection