<div class="point form-group" id={{ "pointLayout" . $course_no }}>
    <legend>
        @php
        if (isset($course_list)) {
            $course = $course_list;
        }
        @endphp
        <a id={{ "point" . $course_no }}></a>ポイント{{ isset($course[$course_no]) ? ' - ' . $course[$course_no]['course_name']. ' - ' : '' }}
        @if (isset($program['id'])
            && ($program['multi_course'] == 0 || ($program['multi_course'] == 1 && isset($course[$course_no]['id'])))) 
        {{ Tag::link(route('points.create', ['program' => $program['id'], 'course' => $course[$course_no] ?? null ]), '追加', ['class' => 'btn btn-small btn-info']) }}
        @endif
    </legend>

    {{-- 広告登録済み　もしくは　コース登録済みの場合表示 --}}
    @if (isset($program['id'])
        && ($program['multi_course'] == 0 || ($program['multi_course'] == 1 && isset($course[$course_no]['id'])))) 
    <table cellpadding="0" cellspacing="0" class="table table-bordered table-hover">
        <tr>
            <th>操作</th>
            <th>実施期間</th>
            <th>成果タイプ</th>
            <th>ボーナス</th>
            <th>報酬額</th>
            <th>ポイント</th>
            <th>100%還元</th>
            <th>タイムセール</th>
            <th>最終更新者</th>
            <th>更新日</th>
        </tr>
        @foreach ($point_list[$course_no] as $point)
        <tr{!! $loop->iteration > 5 ? ' style="display:none" class="PointMore"' : '' !!}>
            <td class="actions" style="white-space:nowrap">
                {{ Tag::link(route('points.edit', ['point' => $point]), '編集', ['class' => 'btn btn-small btn-success']) }}<br />
            </td>
            <td>
                {{ $point->start_at->format('Y-m-d H:i') }}～
                @if (!$point->stop_at->eq(\Carbon\Carbon::parse('9999-12-31')->endOfMonth()))
                {{ $point->stop_at->format('Y-m-d H:i') }}
                @if (isset($point->sale_stop_at))
                ({{ $point->sale_stop_at->format('Y-m-d H:i') }})
                @endif
                @endif
            </td>
            <td>{{ config('map.fee_type')[$point->fee_type] }}</td>
            <td>{{ config('map.target')[$point->bonus] }}</td>
            <td>
                @if ($point->fee_type == 2)
                {{ $point->rewards }}%
                @else
                {{ number_format($point->rewards) }}P
                @endif
            </td>
            <td>
                @if ($point->fee_type == 2)
                {{ $point->fee }}%
                @else
                {{ number_format($point->fee) }}P
                @endif
            </td>
            <td>{{ config('map.target')[$point->all_back] }}</td>
            <td>{{ config('map.target')[$point->time_sale] }}</td>
            <td>{{ isset($point->admin->email) ? $point->admin->email : '' }}&nbsp;</td>
            <td>{{ isset($point->updated_at) ? $point->updated_at->format('Y-m-d H:i:s') : $point->created_at->format('Y-m-d H:i:s') }}&nbsp;</td>
        </tr>
        @endforeach
    </table>
    @if ($point_list[$course_no]->count() > 5)
    <div class="form-group">
        <a href="#" class="btn btn-small btn-info ShowMore" forShowMore="PointMore">もっと見る</a>
    </div>
    @endif
    @else
    <div class="form-group"><div class="form-inline">
        <div class="form-group">
            <label for="PointFeeType">成果タイプ</label><br />
            {{ Tag::formSelect('point[' . $course_no . '][fee_type]', ['' => '---'] + config('map.fee_type'), old('point[' . $course_no . '].fee_type', $point[$course_no]['fee_type'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'PointFeeType']) }}
        </div>
        <div class="form-group">
            <label for="PointRewards">報酬額</label><br />
            {{ Tag::formText('point[' . $course_no . '][rewards]', old('point[' . $course_no . '].rewards', $point[$course_no]['rewards'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'PointRewards']) }}<br />
        </div>
        <div class="form-group">
            <label for="PointFee">ユーザー報酬</label><br />
            {{ Tag::formText('point[' . $course_no . '][fee]', old('point[' . $course_no . '].fee', $point[$course_no]['fee'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'PointFee']) }}<br />
        </div>
        <div class="form-group">
            <label for="PointBonus">ボーナス</label><br />
            {{ Tag::formSelect('point[' . $course_no . '][bonus]', ['' => '---'] + config('map.target'), old('point.bonus', $point[$course_no]['bonus'] ?? ''), ['class' => 'form-control', 'required' => 'required', 'id' => 'PointBonus']) }}
        </div>
        <div class="form-group">
            <label for="PointAllBack">100%還元</label><br />
            {{ Tag::formSelect('point[' . $course_no . '][all_back]', config('map.target'), old('point[' . $course_no . '].all_back', $point[$course_no]['all_back'] ?? ''), ['class' => 'form-control', 'id' => 'PointAllBack']) }}<br />
        </div>
        <label class="form-group" id="rate"></label>
        <div class="form-group">
            {{ Tag::formHidden('statuspoint'. $course_no ,0 ,['id' => 'SaveStatusPoint']) }}
        </div>
    </div></div>

    <div class="form-group">
        <label for="ProgramScheduleRewardCondition">獲得条件・注意事項</label><br />
        {{ Tag::formTextarea('program_schedule[' . $course_no . '][reward_condition]', old('program_schedule[' . $course_no . '].reward_condition', $program_schedule['reward_condition'] ??
        "【ポイント配布の対象】<br>".
         "【ポイント配布の対象外】<br>".
         "【注意事項】"
        ), ['class' => 'form-control', 'rows' => 10, 'id' => 'ProgramScheduleRewardCondition' . $course_no]) }}<br />
    </div>
    @endif
</div>
<br/>
