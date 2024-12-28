◆友達紹介レポート
---------------------------------------------------
・登録数
<?php $month_user_total = array_sum($month_total_map); ?>
月次[{{ $start_month->format('Y年m月d日') }}から{{ $end->format('Y年m月d日') }}]総数：{{ number_format($month_user_total) }}
@foreach ($month_total_map as $key => $value)
{{ $key.'：'.number_format($value) }}
@endforeach

日次[{{ $end->format('Y年m月d日') }}]総数：{{ number_format(array_sum($day_total_map)) }}
@foreach($day_total_map as $key => $value)
{{ $key.'：'.number_format($value) }}
@endforeach
---------------------------------------------------
・稼働({{ $except }}以外)
月次稼働ユーザー数：{{ number_format($month_actioned_total) }}
月次稼働ユーザー発生金額：{{ number_format($month_actioned_amount) }}円
稼働率：{{ $month_user_total < 1 ? '-' : (number_format($month_actioned_total / $month_user_total * 100, 2).'%') }}
---------------------------------------------------
・ログイン
（過去に友達紹介経由で1年以内に入会したユーザー全ての）
月次ログインUU：{{ number_format($month_logined_total) }}
日次ログインUU：{{ number_format($day_logined_total) }}
---------------------------------------------------
・ティア配付
（過去１年以内に友達紹介経由で入会したユーザー全ての）
月次２ティア対象配付予定金額：{{ number_format($friend_program_amount) }}円
---------------------------------------------------
・優良ユーザー

月間[{{ $start_month->format('Y年m月d日') }}から{{ $end->format('Y年m月d日') }}]紹介数上位ユーザー（上位{{ number_format(WrapPhp::count($month_user_map)) }}件）
@foreach($month_user_map as $user_name => $value)
{{ $user_name.'：'.number_format($value) }}
@endforeach

1年以内[{{ $start_year->format('Y年m月d日') }}から{{ $end->format('Y年m月d日') }}]に紹介した人数上位ユーザー（上位{{ number_format(WrapPhp::count($year_user_map)) }}件）
@foreach($year_user_map as $user_name => $value)
{{ $user_name.'：'.number_format($value) }}
@endforeach

---------------------------------------------------
・入会サーバーIP

月間[{{ $start_month->format('Y年m月d日') }}から{{ $end->format('Y年m月d日') }}]
@foreach($ip_map as $ip => $value)
{{ $ip.'：'.number_format($value) }}
@endforeach

@if (!$lock_user_list->isEmpty())
---------------------------------------------------
・ロックユーザー
@foreach($lock_user_list as $user)
{{ $user->name }}
@endforeach
@endif
