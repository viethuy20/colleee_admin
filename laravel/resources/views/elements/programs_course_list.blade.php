<legend>
コース

<a id="addCourse" class="btn btn-small btn-info" href="javascript:void(0);" onclick="addCourse()">
    追加
</a>
</legend>
<div id="courseDetail">
@foreach ($course_list as $course_no => $course)
    @include('elements.programs_course_detail')
@endforeach
</div>
